<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\BookingRoomChange;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BookingLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Support\Realtime;
use App\Services\BookingRepricingService;

class BookingRoomController extends Controller
{
    public function assignRooms(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Chỉ có thể đổi phòng khi booking chưa check-in.');
        }

        $data = $request->validate([
            'room_ids' => 'required|array',
            'room_ids.*' => 'exists:rooms,id',
        ]);

        if (count($data['room_ids']) != $booking->room_quantity) {
            return back()->with('error', 'Bạn phải chọn đúng ' . $booking->room_quantity . ' phòng cho booking này.');
        }

        $booking->load('bookingRooms.room', 'roomCategory');

        if (!$booking->check_in_at || !$booking->check_out_at) {
            return back()->with('error', 'Booking chưa có thời gian nhận/trả phòng nên không thể kiểm tra phòng trống.');
        }

        $selectedRoomIds = collect($data['room_ids'])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (count($selectedRoomIds) !== count($data['room_ids'])) {
            return back()->with('error', 'Không được chọn trùng phòng trong cùng một booking.');
        }

        $validRoomCount = Room::whereIn('id', $selectedRoomIds)
            ->where('room_category_id', $booking->room_category_id)
            ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
            ->count();

        if ($validRoomCount != $booking->room_quantity) {
            return back()->with(
                'error',
                'Danh sách phòng chọn không hợp lệ. Có phòng đã bị đặt trùng thời gian, đang trong thời gian dọn phòng hoặc không thuộc đúng hạng phòng.'
            );
        }

        DB::beginTransaction();

        try {
            $existingRows = $booking->bookingRooms->sortBy('id')->values();
            $selectedRooms = Room::whereIn('id', $selectedRoomIds)->get()->keyBy('id');

            // Không xóa booking_rooms vì mã và dịch vụ theo phòng đang gắn vào ID
            // của từng dòng này. Chỉ cập nhật room_id để giữ nguyên toàn bộ liên kết.
            foreach ($selectedRoomIds as $index => $roomId) {
                $bookingRoom = $existingRows->get($index);
                $newRoom = $selectedRooms->get($roomId);
                if (!$newRoom) {
                    throw new \RuntimeException('Không tìm thấy phòng đã chọn.');
                }

                if (!$bookingRoom) {
                    $bookingRoom = BookingRoom::create([
                        'booking_id' => $booking->id,
                        'room_id' => $newRoom->id,
                        'adult_count' => 0,
                        'child_count' => 0,
                        'price_at_booking' => $booking->roomCategory->price ?? 0,
                        'surcharge' => 0,
                        'surcharge_reason' => null,
                        'created_at' => now(),
                    ]);
                } elseif ((int) $bookingRoom->room_id !== (int) $newRoom->id) {
                    $oldRoom = $bookingRoom->room;
                    $bookingRoom->update(['room_id' => $newRoom->id]);

                    BookingRoomChange::create([
                        'booking_id' => $booking->id,
                        'booking_room_id' => $bookingRoom->id,
                        'old_room_id' => $oldRoom?->id,
                        'new_room_id' => $newRoom->id,
                        'old_room_category_id' => $oldRoom?->room_category_id,
                        'new_room_category_id' => $newRoom->room_category_id,
                        'old_room_price' => (float) $bookingRoom->price_at_booking,
                        'new_room_price' => (float) $bookingRoom->price_at_booking,
                        'night_count' => max(1, Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date))),
                        'price_difference_total' => 0,
                        'change_source' => 'front_desk',
                        'reason' => 'Gán/đổi phòng trước check-in.',
                        'changed_by' => Auth::id(),
                    ]);

                    if ($oldRoom && !in_array((int) $oldRoom->id, $selectedRoomIds, true)) {
                        $oldRoom->update(['status' => 'available']);
                    }
                }

                $newRoom->update(['status' => 'reserved']);

                // Snapshot của mã theo phòng phải đi theo phòng vật lý mới. Các dòng
                // dịch vụ vẫn giữ booking_room_id; room_id_snapshot cũ tiếp tục cho
                // biết dịch vụ đã thực tế phát sinh ở phòng nào.
                DB::table('booking_promotions')
                    ->where('booking_id', $booking->id)
                    ->where('booking_room_id', $bookingRoom->id)
                    ->where('scope', 'room')
                    ->update(['room_id_snapshot' => $newRoom->id]);
            }

            $booking->update([
                'status' => 'confirmed',
            ]);

            $newRoomNumbers = Room::whereIn('id', $selectedRoomIds)->orderBy('room_number')
                ->pluck('room_number')
                ->implode(', ');

            $this->addBookingLog(
                $booking,
                'assign_rooms',
                'Gán/cập nhật phòng cho booking: ' . $newRoomNumbers . '.'
            );

            DB::commit();

            return redirect()
                ->route('admin.bookings.show', $booking->id)
                ->with('success', 'Đổi phòng cho booking thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi đổi phòng: ' . $e->getMessage());
        }
    }

    public function changeRoom(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        $data = $request->validate([
            'old_room_id' => 'required|exists:rooms,id',
            'new_room_id' => 'required|exists:rooms,id|different:old_room_id',
            'old_room_new_status' => 'required|in:available,cleaning,maintenance',
            'change_reason' => 'required|string|max:255',
        ]);

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Trạng thái booking hiện tại không cho phép đổi phòng.');
        }

        $bookingRoom = BookingRoom::where('booking_id', $booking->id)
            ->where('room_id', $data['old_room_id'])
            ->first();

        if (!$bookingRoom) {
            return back()->with('error', 'Phòng cần đổi không thuộc booking này.');
        }

        $checkInAt = $booking->check_in_at;
        $checkOutAt = $booking->check_out_at;

        if (!$checkInAt || !$checkOutAt) {
            return back()->with('error', 'Booking chưa có thời gian check-in/check-out nên không thể kiểm tra phòng theo thời gian.');
        }

        $oldRoom = Room::findOrFail($data['old_room_id']);

        $newRoom = Room::where('id', $data['new_room_id'])
            ->where('room_category_id', $oldRoom->room_category_id)
            ->availableForPeriod($checkInAt, $checkOutAt, $booking->id)
            ->first();

        if (!$newRoom) {
            return back()->with(
                'error',
                'Không thể đổi phòng. Trong khoảng thời gian '
                . Carbon::parse($checkInAt)->format('d/m/Y H:i')
                . ' → '
                . Carbon::parse($checkOutAt)->format('d/m/Y H:i')
                . ', tất cả phòng cùng hạng đã được đặt hoặc đang sử dụng.'
            );
        }

        DB::beginTransaction();

        try {

            $bookingRoom->update([
                'room_id' => $newRoom->id,
                'surcharge_reason' => 'Đổi từ phòng ' . $oldRoom->room_number . ' sang phòng ' . $newRoom->room_number . '. Lý do: ' . $data['change_reason'],
            ]);

            DB::table('booking_promotions')
                ->where('booking_id', $booking->id)
                ->where('booking_room_id', $bookingRoom->id)
                ->where('scope', 'room')
                ->update(['room_id_snapshot' => $newRoom->id]);

            BookingRoomChange::create([
                'booking_id' => $booking->id,
                'booking_room_id' => $bookingRoom->id,
                'old_room_id' => $oldRoom->id,
                'new_room_id' => $newRoom->id,
                'old_room_category_id' => $oldRoom->room_category_id,
                'new_room_category_id' => $newRoom->room_category_id,
                'old_room_price' => (float) $bookingRoom->price_at_booking,
                'new_room_price' => (float) $bookingRoom->price_at_booking,
                'night_count' => max(1, Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date))),
                'price_difference_total' => 0,
                'change_source' => 'front_desk',
                'reason' => $data['change_reason'],
                'changed_by' => Auth::id(),
            ]);

            $oldRoom->update([
                'status' => $data['old_room_new_status'],
            ]);

            $newRoom->update([
                'status' => $booking->status === 'checked_in' ? 'occupied' : 'reserved',
            ]);

            $oldNote = $booking->note ? $booking->note . "\n" : '';

            $booking->update([
                'note' => $oldNote . now()->format('d/m/Y H:i') . ' - Đổi từ phòng ' . $oldRoom->room_number . ' sang phòng ' . $newRoom->room_number . '. Lý do: ' . $data['change_reason'],
            ]);

            $this->addBookingLog(
                $booking,
                'change_room',
                'Đổi từ phòng '
                . $oldRoom->room_number
                . ' sang phòng '
                . $newRoom->room_number
                . '. Lý do: '
                . $data['change_reason']
                . '. Trạng thái phòng cũ: '
                . $data['old_room_new_status']
                . '.'
            );

            DB::commit();

            return back()->with('success', 'Đổi phòng thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi đổi phòng: ' . $e->getMessage());
        }
    }

    private function guardCanAccessBooking(Booking $booking): void
    {
        $user = Auth::user();

        abort_unless($user && in_array($user->role, [
            'super_admin',
            'manager',
            'receptionist_lead',
            'receptionist',
        ], true), 403, 'Bạn không có quyền xử lý booking này.');
    }

    private function addBookingLog(Booking $booking, string $action, string $description): void
    {
        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
        ]);
    }
}