<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingRoomController extends Controller
{
    public function assignRooms(Request $request, Booking $booking)
    {
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

        $oldRoomIds = $booking->bookingRooms
            ->pluck('room_id')
            ->toArray();

        $validRoomCount = Room::whereIn('id', $data['room_ids'])
            ->where('room_category_id', $booking->room_category_id)
            ->where(function ($query) use ($oldRoomIds) {
                $query->where('status', 'available');

                if (!empty($oldRoomIds)) {
                    $query->orWhereIn('id', $oldRoomIds);
                }
            })
            ->count();

        if ($validRoomCount != $booking->room_quantity) {
            return back()->with('error', 'Danh sách phòng chọn không hợp lệ. Chỉ được chọn phòng trống hoặc phòng đang thuộc booking này.');
        }

        DB::beginTransaction();

        try {
            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'available',
                    ]);
                }
            }

            BookingRoom::where('booking_id', $booking->id)->delete();

            foreach ($data['room_ids'] as $roomId) {
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

        $newRoom = Room::where('id', $data['new_room_id'])
            ->where('room_category_id', $booking->room_category_id)
            ->where('status', 'available')
            ->first();

        if (!$newRoom) {
            return back()->with('error', 'Phòng thay thế không hợp lệ hoặc không còn trống.');
        }

        DB::beginTransaction();

        try {
            $oldRoom = Room::findOrFail($data['old_room_id']);

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

            DB::commit();

            return back()->with('success', 'Đổi phòng thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi đổi phòng: ' . $e->getMessage());
        }
    }
}