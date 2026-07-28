<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BookingLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Support\Realtime;

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

                DB::beginTransaction();

        try {
            // Lock rooms for update
            $rooms = Room::whereIn('id', $selectedRoomIds)
                ->lockForUpdate()
                ->get();

            // Recheck correct room category
            foreach ($rooms as $room) {
                if ($room->room_category_id !== $booking->room_category_id) {
                    throw new \Exception('Có phòng không thuộc đúng hạng phòng đã đặt.');
                }
            }

            // Check availability inside transaction
            $validRoomCount = Room::whereIn('id', $selectedRoomIds)
                ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
                ->count();

            if ($validRoomCount != $booking->room_quantity) {
                throw new \Exception('Danh sách phòng chọn không hợp lệ. Có phòng đã bị đặt trùng thời gian hoặc đang trong thời gian dọn phòng.');
            }
            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'available',
                    ]);
                }
            }

            BookingRoom::where('booking_id', $booking->id)->delete();

            foreach ($selectedRoomIds as $roomId) {
                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_id' => $roomId,
                    'adult_count' => 0,
                    'child_count' => 0,
                    'price_at_booking' => $booking->roomCategory->price ?? 0,
                    'surcharge' => 0,
                    'surcharge_reason' => null,
                    'created_at' => now(),
                ]);

                Room::where('id', $roomId)->update([
                    'status' => 'reserved',
                ]);
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

                DB::beginTransaction();

        try {
            // Lock old and new rooms for update
            $rooms = Room::whereIn('id', [$data['old_room_id'], $data['new_room_id']])
                ->lockForUpdate()
                ->get();

            $oldRoom = $rooms->firstWhere('id', $data['old_room_id']);
            $newRoom = $rooms->firstWhere('id', $data['new_room_id']);

            if (!$oldRoom || !$newRoom) {
                throw new \Exception('Không tìm thấy phòng tương ứng.');
            }

            if ($newRoom->room_category_id !== $oldRoom->room_category_id) {
                throw new \Exception('Phòng mới không cùng hạng với phòng cũ.');
            }

            // Check availability inside transaction
            $isAvailable = Room::where('id', $newRoom->id)
                ->availableForPeriod($checkInAt, $checkOutAt, $booking->id)
                ->exists();

            if (!$isAvailable) {
                throw new \Exception('Không thể đổi phòng. Phòng mới đã bị đặt hoặc đang sử dụng trong khoảng thời gian này.');
            }

            $bookingRoom->update([
                'room_id' => $newRoom->id,
                'surcharge_reason' => 'Đổi từ phòng ' . $oldRoom->room_number . ' sang phòng ' . $newRoom->room_number . '. Lý do: ' . $data['change_reason'],
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

        if (!$user || in_array($user->role, ['super_admin', 'manager'], true)) {
            return;
        }

        if ($user->role === 'receptionist') {
            $canAccess = (int) $booking->created_by === (int) $user->id
                || $booking->staffAssignments()
                    ->where('staff_id', $user->id)
                    ->where('status', 'active')
                    ->exists();

            abort_unless($canAccess, 403, 'Bạn không được phân công xử lý booking này.');

            return;
        }

        abort(403, 'Bạn không có quyền xử lý booking này.');
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