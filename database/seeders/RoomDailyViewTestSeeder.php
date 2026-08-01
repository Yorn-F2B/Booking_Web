<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomActionLog;
use App\Models\RoomCategory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder tạo test cases cho trang "Trạng thái phòng theo ngày"
 *
 * Chạy: php artisan db:seed --class=RoomDailyViewTestSeeder
 *
 * Phân bổ vào 4 tầng hiện có (1–4), 2 phòng mới mỗi tầng:
 *
 * Tầng 1: 104 = Trống          | 105 = Đã đặt (confirmed)
 * Tầng 2: 204 = Đang ở         | 205 = Đang ở + maintenance (bug fix)
 * Tầng 3: 303 = Kiểm tra booking | 304 = Kiểm tra thủ công
 * Tầng 4: 407 = Đang dọn       | 408 = Bảo trì
 */
class RoomDailyViewTestSeeder extends Seeder
{
    private const TZ = 'Asia/Ho_Chi_Minh';

    public function run(): void
    {
        $today = Carbon::now(self::TZ)->toDateString();

        $category = RoomCategory::firstOrCreate(
            ['name' => 'Test Category'],
            ['price' => 500000, 'adult_capacity' => 2, 'child_capacity' => 1, 'status' => 'active']
        );

        $customer = Customer::firstOrCreate(
            ['phone' => '0900000099'],
            ['first_name' => 'Test', 'last_name' => 'Khách', 'email' => 'test_daily@example.com']
        );

        // Xóa phòng tầng 9 đã tạo lần trước (xóa booking_rooms và bookings liên quan trước)
        $oldRoomIds = Room::withTrashed()->whereIn('room_number', ['901','902','903','904','905','906','907','908'])->pluck('id');
        if ($oldRoomIds->isNotEmpty()) {
            $oldBookingIds = BookingRoom::whereIn('room_id', $oldRoomIds)->pluck('booking_id');
            BookingRoom::whereIn('room_id', $oldRoomIds)->delete();
            Booking::withTrashed()->whereIn('booking_code', ['BK-TEST-002','BK-TEST-003','BK-TEST-004','BK-TEST-005'])->forceDelete();
            RoomActionLog::whereIn('room_id', $oldRoomIds)->delete();
            Room::withTrashed()->whereIn('id', $oldRoomIds)->forceDelete();
        }

        // ── Tầng 1 ──────────────────────────────────────────────────
        // 104: Trống — không có booking
        $this->upsertRoom('104', $category->id, 1, 'available', 'TEST: Trống');

        // 105: Đã đặt 3 ngày — booking confirmed từ hôm nay đến +2 ngày
        //      Xem ngày 2 hoặc 3 vẫn phải hiện "Đã đặt"
        $r105 = $this->upsertRoom('105', $category->id, 1, 'available', 'TEST: Đã đặt 3 ngày');
        $this->upsertBooking('BK-TEST-105', $customer->id, $category->id, $r105->id, 'confirmed', [
            'check_in_at'  => Carbon::parse($today . ' 14:00:00', self::TZ),
            'check_out_at' => Carbon::parse($today . ' 14:00:00', self::TZ)->addDays(3),
        ]);

        // ── Tầng 2 ──────────────────────────────────────────────────
        // 204: Đang ở 3 ngày — booking checked_in kéo dài 3 ngày
        //      Xem ngày 2 hoặc 3 vẫn phải hiện "Đang ở"
        $r204 = $this->upsertRoom('204', $category->id, 2, 'available', 'TEST: Đang ở 3 ngày');
        $this->upsertBooking('BK-TEST-204', $customer->id, $category->id, $r204->id, 'checked_in', [
            'check_in_at'  => Carbon::parse($today . ' 08:00:00', self::TZ),
            'check_out_at' => Carbon::parse($today . ' 08:00:00', self::TZ)->addDays(3),
        ]);

        // 205: Đang ở + room->status = maintenance (bug fix case)
        //      Hôm nay phải hiện "Bảo trì", không phải "Đang ở"
        $r205 = $this->upsertRoom('205', $category->id, 2, 'maintenance', 'TEST: checked_in + maintenance → Bảo trì (bug fix)');
        $this->upsertBooking('BK-TEST-205', $customer->id, $category->id, $r205->id, 'checked_in', [
            'check_in_at'  => Carbon::parse($today . ' 08:00:00', self::TZ),
            'check_out_at' => Carbon::parse($today . ' 20:00:00', self::TZ),
        ]);

        // ── Tầng 3 ──────────────────────────────────────────────────
        // 303: Kiểm tra — booking inspection_requested
        $r303 = $this->upsertRoom('303', $category->id, 3, 'available', 'TEST: Kiểm tra từ booking');
        $this->upsertBooking('BK-TEST-303', $customer->id, $category->id, $r303->id, 'inspection_requested', [
            'check_in_at'  => Carbon::parse($today . ' 08:00:00', self::TZ),
            'check_out_at' => Carbon::parse($today . ' 14:00:00', self::TZ),
        ]);

        // 304: Kiểm tra (2) — thủ công room->status = inspection
        $r304 = $this->upsertRoom('304', $category->id, 3, 'inspection', 'TEST: Kiểm tra thủ công');
        $this->upsertActionLog($r304->id, 'status_change', $today, 'Đặt kiểm tra thủ công (test case).');

        // ── Tầng 4 ──────────────────────────────────────────────────
        // 407: Đang dọn — thủ công
        $r407 = $this->upsertRoom('407', $category->id, 4, 'cleaning', 'TEST: Đang dọn thủ công');
        $this->upsertActionLog($r407->id, 'cleaning', $today, 'Bắt đầu dọn phòng thủ công (test case).');

        // 408: Bảo trì — thủ công
        $r408 = $this->upsertRoom('408', $category->id, 4, 'maintenance', 'TEST: Bảo trì thủ công');
        $this->upsertActionLog($r408->id, 'status_change', $today, 'Chuyển bảo trì do hỏng điều hòa (test case).');

        $this->command->info('✅ Hoàn thành. Kiểm tra tại /admin/room-daily');
        $this->command->table(
            ['Tầng', 'Phòng', 'Trạng thái kỳ vọng', 'Ghi chú'],
            [
                [1, '104', 'Trống',     'Không có booking'],
                [1, '105', 'Đã đặt',   'Confirmed 3 ngày — xem ngày 2,3 vẫn Đã đặt'],
                [2, '204', 'Đang ở',   'Checked_in 3 ngày — xem ngày 2,3 vẫn Đang ở'],
                [2, '205', 'Bảo trì',  'Bug fix: checked_in + room=maintenance → Bảo trì'],
                [3, '303', 'Kiểm tra', 'Booking inspection_requested'],
                [3, '304', 'Kiểm tra', 'Thủ công room->status=inspection'],
                [4, '407', 'Đang dọn', 'Thủ công room->status=cleaning'],
                [4, '408', 'Bảo trì',  'Thủ công room->status=maintenance'],
            ]
        );
    }

    private function upsertRoom(string $number, int $categoryId, int $floor, string $status, string $note): Room
    {
        return Room::withTrashed()->updateOrCreate(
            ['room_number' => $number],
            ['room_category_id' => $categoryId, 'floor_number' => $floor, 'status' => $status, 'note' => $note, 'deleted_at' => null]
        );
    }

    private function upsertBooking(string $code, int $customerId, int $categoryId, int $roomId, string $status, array $times): Booking
    {
        $booking = Booking::withTrashed()->updateOrCreate(
            ['booking_code' => $code],
            [
                'customer_id'      => $customerId,
                'room_category_id' => $categoryId,
                'check_in_date'    => $times['check_in_at']->toDateString(),
                'check_out_date'   => $times['check_out_at']->toDateString(),
                'check_in_at'      => $times['check_in_at'],
                'check_out_at'     => $times['check_out_at'],
                'adult_count'      => 2,
                'child_count'      => 0,
                'room_quantity'    => 1,
                'status'           => $status,
                'payment_status'   => 'unpaid',
                'estimated_total'  => 500000,
                'deleted_at'       => null,
            ]
        );

        BookingRoom::updateOrCreate(
            ['booking_id' => $booking->id, 'room_id' => $roomId],
            ['adult_count' => 2, 'child_count' => 0, 'price_at_booking' => 500000, 'created_at' => now()]
        );

        return $booking;
    }

    private function upsertActionLog(int $roomId, string $actionType, string $date, string $note): void
    {
        RoomActionLog::updateOrCreate(
            ['room_id' => $roomId, 'action_type' => $actionType, 'note' => $note],
            ['user_id' => null, 'action_time' => Carbon::parse($date . ' 09:00:00', self::TZ)]
        );
    }
}
