<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BookingIdentityGuard
{
    public const MIN_BOOKING_AGE = 18;
    public const ACTIVE_STATUSES = ['pending', 'confirmed', 'checked_in', 'inspection_requested'];

    public function assertEligible(Customer $customer, ?int $ignoreBookingId = null): void
    {
        $this->assertMinimumAge($customer);
        $this->assertNoActiveBookingForCccd((string) $customer->cccd, $ignoreBookingId);
        $this->assertAccountAllowed($customer);
    }

    public function assertMinimumAge(Customer $customer): void
    {
        if (!$customer->birthday) {
            throw ValidationException::withMessages([
                'birthday' => 'Vui lòng cung cấp ngày sinh để kiểm tra độ tuổi đặt phòng.',
            ]);
        }

        if (Carbon::parse($customer->birthday)->age < self::MIN_BOOKING_AGE) {
            throw ValidationException::withMessages([
                'birthday' => 'Người đứng tên đặt phòng phải đủ ' . self::MIN_BOOKING_AGE . ' tuổi.',
            ]);
        }
    }

    public function assertNoActiveBookingForCccd(string $cccd, ?int $ignoreBookingId = null): void
    {
        $normalized = preg_replace('/\D+/', '', $cccd);
        if ($normalized === '') {
            return;
        }

        $exists = Booking::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->when($ignoreBookingId, fn ($q) => $q->whereKeyNot($ignoreBookingId))
            ->where(function ($query) use ($normalized) {
                $query->where('customer_cccd_snapshot', $normalized)
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('cccd', $normalized));
            })
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'cccd' => 'Số CCCD này đang đứng tên một booking còn hoạt động. Phải hủy hoặc hoàn tất booking đó trước khi đặt đơn mới.',
            ]);
        }
    }

    public function assertAccountAllowed(Customer $customer): void
    {
        if (!config('account_restrictions.enabled', false)) {
            return;
        }

        $user = $customer->user;
        if (!$user) {
            return;
        }

        if (($user->status ?? 'active') === 'banned') {
            throw ValidationException::withMessages(['email' => 'Tài khoản đã bị cấm đặt phòng. Vui lòng liên hệ khách sạn.']);
        }

        if ($user->booking_locked_until && now()->lt($user->booking_locked_until)) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản đang bị tạm khóa đặt phòng đến ' . $user->booking_locked_until->format('d/m/Y H:i') . '.',
            ]);
        }
    }
}
