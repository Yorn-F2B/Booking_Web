<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RoomInspection;
use Illuminate\Support\Facades\DB;

class BookingLifecycleController extends Controller
{
    public function checkIn(Booking $booking)
    {
        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Chỉ có thể nhận phòng với booking đã xác nhận.');
        }

        $booking->load('bookingRooms.room');

        if ($booking->bookingRooms->count() == 0) {
            return back()->with('error', 'Booking này chưa được gán phòng nên không thể check-in.');
        }

        DB::beginTransaction();

        try {
            $booking->update([
                'status' => 'checked_in',
                'actual_check_in' => now(),
            ]);

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'occupied',
                    ]);
                }
            }

            DB::commit();

            return back()->with('success', 'Check-in thành công. Phòng đã chuyển sang trạng thái đang sử dụng.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi check-in: ' . $e->getMessage());
        }
    }

    public function requestInspection(Booking $booking)
    {
        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ có thể yêu cầu kiểm tra khi khách đang ở.');
        }

        $booking->load('bookingRooms.room');

        DB::beginTransaction();

        try {
            foreach ($booking->bookingRooms as $bookingRoom) {
                if (!$bookingRoom->room) {
                    continue;
                }

                $bookingRoom->room->update([
                    'status' => 'inspection',
                ]);

                RoomInspection::firstOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'room_id' => $bookingRoom->room_id,
                        'status' => 'pending',
                    ],
                    [
                        'has_damage' => false,
                        'damage_total' => 0,
                    ]
                );
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';

            $booking->update([
                'note' => $oldNote . now()->format('d/m/Y H:i') . ' - Đã yêu cầu kiểm tra phòng trước khi check-out.',
            ]);

            DB::commit();

            return back()->with('success', 'Đã tạo phiếu kiểm tra và chuyển phòng sang trạng thái chờ kiểm tra.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi yêu cầu kiểm tra phòng: ' . $e->getMessage());
        }
    }

    public function checkOut(Booking $booking)
    {
        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ có thể check-out booking đang ở.');
        }

        $booking->load([
            'bookingRooms.room',
            'roomInspections.items',
        ]);

        if ($booking->roomInspections->count() == 0) {
            return back()->with('error', 'Booking này chưa có phiếu kiểm tra phòng.');
        }

        $notConfirmedInspectionCount = $booking->roomInspections
            ->where('status', '!=', 'confirmed')
            ->count();

        if ($notConfirmedInspectionCount > 0) {
            return back()->with('error', 'Vẫn còn phiếu kiểm tra chưa được admin duyệt.');
        }

        DB::beginTransaction();

        try {
            $booking->update([
                'status' => 'checked_out',
                'actual_check_out' => now(),
                'payment_status' => 'paid',
            ]);

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'cleaning',
                    ]);
                }
            }

            DB::commit();

            return back()->with('success', 'Check-out thành công. Phòng đã chuyển sang trạng thái cần dọn.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi check-out: ' . $e->getMessage());
        }
    }
}