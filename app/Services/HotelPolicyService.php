<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\HotelPolicy;
use Illuminate\Support\Facades\Schema;

class HotelPolicyService
{
    /**
     * Giá trị tại đây chỉ là fallback an toàn khi SQL patch chưa được cài.
     * Nguồn cấu hình vận hành chính thức là bảng hotel_policies.
     */
    public const DEFINITIONS = [
        'booking.min_age' => ['group' => 'booking', 'default' => 18, 'type' => 'integer', 'label' => 'Tuổi tối thiểu người đứng tên', 'description' => 'Áp dụng cho khách đứng tên booking ở mọi kênh.', 'sort' => 10],
        'booking.cleaning_buffer_minutes' => ['group' => 'booking', 'default' => 0, 'type' => 'integer', 'label' => 'Buffer dọn phòng giữa hai booking (phút)', 'description' => 'Khoảng đệm thêm khi kiểm tra phòng trống. Không thay thế ca dọn phòng thực tế.', 'sort' => 20],
        'booking.direct_cancel_cutoff_time' => ['group' => 'booking', 'default' => '14:00', 'type' => 'time', 'label' => 'Mốc hủy trực tiếp ngày nhận phòng', 'description' => 'Từ mốc này khách không tự hủy trực tiếp mà chuyển sang luồng xác nhận/đến muộn.', 'sort' => 21],
        'booking.hourly_cancel_grace_minutes' => ['group' => 'booking', 'default' => 30, 'type' => 'integer', 'label' => 'Ân hạn hủy booking theo giờ (phút)', 'description' => 'Khoảng thời gian sau giờ nhận dự kiến mà booking theo giờ còn áp dụng mốc hủy.', 'sort' => 22],
        'payment.deposit_percent' => ['group' => 'payment', 'default' => 30, 'type' => 'decimal', 'label' => 'Mức cọc tối thiểu (%)', 'description' => 'Tính trên tiền phòng sau ưu đãi áp vào phòng.', 'sort' => 30],
        'payment.vnpay_expire_minutes' => ['group' => 'payment', 'default' => 30, 'type' => 'integer', 'label' => 'Thời hạn link VNPay khi khách đặt online (phút)', 'description' => 'Hết hạn thì link không còn được dùng để thanh toán đơn đó.', 'sort' => 40],
        'payment.admin_vnpay_expire_minutes' => ['group' => 'payment', 'default' => 1440, 'type' => 'integer', 'label' => 'Thời hạn link VNPay do lễ tân gửi (phút)', 'description' => 'Link mới phải vô hiệu hóa link cũ còn hiệu lực của cùng mục đích.', 'sort' => 50],

        'stay.standard_check_in_time' => ['group' => 'stay', 'default' => '14:00', 'type' => 'time', 'label' => 'Giờ check-in tiêu chuẩn', 'description' => null, 'sort' => 60],
        'stay.standard_check_out_time' => ['group' => 'stay', 'default' => '12:00', 'type' => 'time', 'label' => 'Giờ check-out tiêu chuẩn', 'description' => null, 'sort' => 70],
        'stay.early_checkin_free_from' => ['group' => 'stay', 'default' => '12:00', 'type' => 'time', 'label' => 'Bắt đầu khung check-in sớm miễn phí', 'description' => 'Miễn phí khi phòng đã sẵn sàng.', 'sort' => 80],
        'stay.early_checkin_tier1_end' => ['group' => 'stay', 'default' => '06:00', 'type' => 'time', 'label' => 'Mốc check-in sớm mức 1', 'description' => null, 'sort' => 90],
        'stay.early_checkin_tier2_end' => ['group' => 'stay', 'default' => '09:00', 'type' => 'time', 'label' => 'Mốc check-in sớm mức 2', 'description' => null, 'sort' => 100],
        'stay.early_checkin_percent_1' => ['group' => 'stay', 'default' => 100, 'type' => 'decimal', 'label' => 'Phụ thu check-in sớm mức 1 (%)', 'description' => null, 'sort' => 110],
        'stay.early_checkin_percent_2' => ['group' => 'stay', 'default' => 50, 'type' => 'decimal', 'label' => 'Phụ thu check-in sớm mức 2 (%)', 'description' => null, 'sort' => 120],
        'stay.early_checkin_percent_3' => ['group' => 'stay', 'default' => 20, 'type' => 'decimal', 'label' => 'Phụ thu check-in sớm mức 3 (%)', 'description' => null, 'sort' => 130],

        'stay.late_checkout_free_minutes' => ['group' => 'stay', 'default' => 15, 'type' => 'integer', 'label' => 'Ân hạn check-out muộn (phút)', 'description' => null, 'sort' => 140],
        'stay.late_checkout_tier1_end' => ['group' => 'stay', 'default' => '13:00', 'type' => 'time', 'label' => 'Mốc check-out muộn mức 1', 'description' => null, 'sort' => 150],
        'stay.late_checkout_tier2_end' => ['group' => 'stay', 'default' => '14:00', 'type' => 'time', 'label' => 'Mốc check-out muộn mức 2', 'description' => null, 'sort' => 160],
        'stay.late_checkout_tier3_end' => ['group' => 'stay', 'default' => '15:00', 'type' => 'time', 'label' => 'Mốc check-out muộn mức 3', 'description' => null, 'sort' => 170],
        'stay.late_checkout_full_night_from' => ['group' => 'stay', 'default' => '18:00', 'type' => 'time', 'label' => 'Mốc check-out tính thêm một đêm', 'description' => null, 'sort' => 180],
        'stay.late_checkout_percent_1' => ['group' => 'stay', 'default' => 20, 'type' => 'decimal', 'label' => 'Phụ thu check-out muộn mức 1 (%)', 'description' => null, 'sort' => 190],
        'stay.late_checkout_percent_2' => ['group' => 'stay', 'default' => 40, 'type' => 'decimal', 'label' => 'Phụ thu check-out muộn mức 2 (%)', 'description' => null, 'sort' => 200],
        'stay.late_checkout_percent_3' => ['group' => 'stay', 'default' => 60, 'type' => 'decimal', 'label' => 'Phụ thu check-out muộn mức 3 (%)', 'description' => null, 'sort' => 210],
        'stay.late_checkout_percent_4' => ['group' => 'stay', 'default' => 80, 'type' => 'decimal', 'label' => 'Phụ thu check-out muộn mức 4 (%)', 'description' => null, 'sort' => 220],
        'stay.late_checkout_percent_full' => ['group' => 'stay', 'default' => 100, 'type' => 'decimal', 'label' => 'Phụ thu từ mốc tính thêm đêm (%)', 'description' => null, 'sort' => 230],

        'stay.late_arrival_cutoff_time' => ['group' => 'stay', 'default' => '18:00', 'type' => 'time', 'label' => 'Giờ G giữ phòng', 'description' => 'Sau mốc này áp dụng luồng đến muộn/gia hạn thay vì giữ phòng vô hạn.', 'sort' => 240],
        'stay.late_arrival_tier1_end' => ['group' => 'stay', 'default' => '21:00', 'type' => 'time', 'label' => 'Mốc đến muộn mức 1', 'description' => null, 'sort' => 250],
        'stay.late_arrival_percent_1' => ['group' => 'stay', 'default' => 20, 'type' => 'decimal', 'label' => 'Phụ thu đến muộn mức 1 (%)', 'description' => null, 'sort' => 260],
        'stay.late_arrival_percent_2' => ['group' => 'stay', 'default' => 50, 'type' => 'decimal', 'label' => 'Phụ thu đến muộn mức 2 (%)', 'description' => null, 'sort' => 270],
        'stay.late_arrival_percent_next_day' => ['group' => 'stay', 'default' => 100, 'type' => 'decimal', 'label' => 'Phụ thu đến từ ngày hôm sau (%)', 'description' => null, 'sort' => 280],
        'stay.late_arrival_grace_minutes' => ['group' => 'stay', 'default' => 30, 'type' => 'integer', 'label' => 'Ân hạn sau giờ khách báo đến (phút)', 'description' => null, 'sort' => 290],
        'stay.rescheduled_after_cutoff_grace_minutes' => ['group' => 'stay', 'default' => 120, 'type' => 'integer', 'label' => 'Ân hạn đơn đổi lịch sau giờ G (phút)', 'description' => null, 'sort' => 300],
        'stay.priority_cleaning_start_time' => ['group' => 'stay', 'default' => '12:00', 'type' => 'time', 'label' => 'Mốc bắt đầu dọn phòng ưu tiên', 'description' => null, 'sort' => 310],
        'stay.priority_cleaning_window_minutes' => ['group' => 'stay', 'default' => 120, 'type' => 'integer', 'label' => 'Khoảng báo dọn gấp trước khách kế tiếp (phút)', 'description' => 'Khi phòng vừa trả và có booking kế tiếp trong khoảng này, hệ thống đánh dấu dọn gấp.', 'sort' => 311],
        'stay.late_arrival_form_expire_minutes' => ['group' => 'stay', 'default' => 1440, 'type' => 'integer', 'label' => 'Thời hạn form báo đến muộn gửi email (phút)', 'description' => 'Thời gian khách có thể dùng đường dẫn được lễ tân gửi để báo giờ dự kiến đến.', 'sort' => 312],

        'stay.short_stay_min_minutes' => ['group' => 'stay', 'default' => 30, 'type' => 'integer', 'label' => 'Thời lượng tối thiểu booking theo giờ (phút)', 'description' => 'Không cho tạo ca ở theo giờ ngắn hơn mốc này.', 'sort' => 315],
        'stay.short_stay_to_overnight_hours' => ['group' => 'stay', 'default' => 12, 'type' => 'integer', 'label' => 'Ngưỡng chuyển booking theo giờ sang qua đêm (giờ)', 'description' => 'Nếu thời lượng vượt mốc này, hệ thống tính theo chính sách qua đêm.', 'sort' => 316],
        'stay.short_stay_base_hours' => ['group' => 'stay', 'default' => 2, 'type' => 'integer', 'label' => 'Số giờ cơ bản của gói ở theo giờ', 'description' => null, 'sort' => 320],
        'stay.short_stay_base_percent' => ['group' => 'stay', 'default' => 50, 'type' => 'decimal', 'label' => 'Giá gói giờ cơ bản (% giá đêm)', 'description' => null, 'sort' => 330],
        'stay.short_stay_extra_hour_percent' => ['group' => 'stay', 'default' => 10, 'type' => 'decimal', 'label' => 'Mỗi giờ thêm (% giá đêm)', 'description' => null, 'sort' => 340],
        'stay.short_stay_max_percent' => ['group' => 'stay', 'default' => 80, 'type' => 'decimal', 'label' => 'Trần giá ở theo giờ (% giá đêm)', 'description' => null, 'sort' => 350],

        'room_issue.proposal_hold_minutes' => ['group' => 'room_issue', 'default' => 30, 'type' => 'integer', 'label' => 'Thời gian giữ phòng thay thế (phút)', 'description' => 'Giữ tạm phòng được đề xuất trong khi lễ tân trao đổi với khách.', 'sort' => 360],
        'housekeeping.slow_room_alert_minutes' => ['group' => 'housekeeping', 'default' => 120, 'type' => 'integer', 'label' => 'Mốc cảnh báo phòng chờ xử lý quá lâu (phút)', 'description' => 'Dashboard cảnh báo phòng ở trạng thái chờ dọn/chờ kiểm tra lâu hơn mốc này.', 'sort' => 365],
        'chat.archive_retention_days' => ['group' => 'chat', 'default' => 730, 'type' => 'integer', 'label' => 'Mốc lưu trữ hội thoại tham chiếu (ngày)', 'description' => 'Dùng để archive, không tự xóa hội thoại booking/tranh chấp đang cần tra cứu.', 'sort' => 370],
    ];

    private ?array $rows = null;

    public function get(string $key, mixed $fallback = null): mixed
    {
        $definition = self::DEFINITIONS[$key] ?? null;
        $fallback = $fallback ?? ($definition['default'] ?? null);

        if (!Schema::hasTable('hotel_policies')) {
            return $fallback;
        }

        if ($this->rows === null) {
            $this->rows = HotelPolicy::query()->where('active', true)->get()->keyBy('key')->all();
        }

        $row = $this->rows[$key] ?? null;
        if (!$row || $row->value === null) {
            return $fallback;
        }

        return $this->cast($row->value, $row->type ?: ($definition['type'] ?? 'string'));
    }

    public function forBooking(?Booking $booking, string $key, mixed $fallback = null): mixed
    {
        $snapshot = $booking?->policy_snapshot;
        if (is_array($snapshot) && array_key_exists($key, $snapshot)) {
            return $snapshot[$key];
        }

        return $this->get($key, $fallback);
    }

    public function snapshot(): array
    {
        $snapshot = [];
        foreach (array_keys(self::DEFINITIONS) as $key) {
            $snapshot[$key] = $this->get($key);
        }

        $snapshot['_captured_at'] = now('Asia/Ho_Chi_Minh')->toIso8601String();

        return $snapshot;
    }

    public function depositRate(?Booking $booking = null): float
    {
        return max(0, min(100, (float) $this->forBooking($booking, 'payment.deposit_percent', 30))) / 100;
    }

    public function definitions(): array
    {
        return self::DEFINITIONS;
    }

    public function flush(): void
    {
        $this->rows = null;
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'json' => json_decode((string) $value, true) ?: [],
            default => (string) $value,
        };
    }
}
