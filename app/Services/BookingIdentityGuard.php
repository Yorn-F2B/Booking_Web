<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BookingIdentityGuard
{
    public const MIN_BOOKING_AGE = 18; // fallback khi SQL policy chưa được cài
    public const ACTIVE_STATUSES = ['pending', 'confirmed', 'checked_in', 'inspection_requested']; // compatibility/documentation only

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

        $minimumAge = max(0, (int) app(HotelPolicyService::class)->get('booking.min_age', self::MIN_BOOKING_AGE));

        if (Carbon::parse($customer->birthday)->age < $minimumAge) {
            throw ValidationException::withMessages([
                'birthday' => 'Người đứng tên đặt phòng phải đủ ' . $minimumAge . ' tuổi.',
            ]);
        }
    }

    public function assertNoActiveBookingForCccd(string $cccd, ?int $ignoreBookingId = null): void
    {
        $normalized = preg_replace('/\D+/', '', $cccd);
        if ($normalized === '') {
            return;
        }

        // Dùng cùng định nghĩa 'còn hiệu lực' với tồn phòng. Pending đã hết hạn
        // và chưa có payment success không được khóa CCCD chỉ vì scheduler chưa chạy.
        // Nếu hồ sơ CCCD tồn tại thì khóa row customer để tuần tự hóa các submit
        // cùng định danh trong transaction tạo booking.
        Customer::query()->where('cccd', $normalized)->lockForUpdate()->first();

        $exists = Booking::query()
            ->activeForOperations()
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

    /**
     * Không cho đổi CCCD của người đứng tên khi họ đang có booking hoạt động.
     * Booking lưu snapshot định danh; cho đổi tự do ở hồ sơ sẽ tạo đường lách
     * quy tắc một CCCD/một booking hoạt động và làm lịch sử khó đối chiếu.
     */
    public function assertIdentityUpdateAllowed(Customer $customer, ?string $newCccd, mixed $newBirthday = null): void
    {
        $oldCccd = preg_replace('/\D+/', '', (string) $customer->cccd);
        $normalizedNewCccd = preg_replace('/\D+/', '', (string) $newCccd);
        $hasActiveBooking = $customer->bookings()->activeForOperations()->exists();

        if ($hasActiveBooking && $oldCccd !== $normalizedNewCccd) {
            throw ValidationException::withMessages([
                'cccd' => 'Không thể đổi CCCD của người đứng tên khi đang có booking hoạt động. Hãy hoàn tất/hủy booking hoặc xử lý thay người đứng tên trong nghiệp vụ booking.',
            ]);
        }

        if (!$hasActiveBooking && $normalizedNewCccd !== '' && $oldCccd !== $normalizedNewCccd) {
            $this->assertNoActiveBookingForCccd($normalizedNewCccd);
        }

        if ($newBirthday !== null && $newBirthday !== '') {
            $candidate = clone $customer;
            $candidate->birthday = $newBirthday;
            $this->assertMinimumAge($candidate);
        } elseif ($hasActiveBooking) {
            // Booking đang hoạt động phải luôn còn dữ liệu ngày sinh hợp lệ của người đứng tên.
            $this->assertMinimumAge($customer);
        }
    }

    public function assertAccountAllowed(Customer $customer): void
    {
        if (($customer->status ?? 'active') === 'blacklist') {
            throw ValidationException::withMessages([
                'cccd' => 'Khách hàng đang nằm trong danh sách hạn chế. Vui lòng liên hệ khách sạn.',
            ]);
        }

        $user = $customer->user;
        if ($user && ($user->status ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'email' => $user->status === 'banned'
                    ? 'Tài khoản đã bị khóa. Vui lòng liên hệ khách sạn.'
                    : 'Tài khoản đang bị vô hiệu hóa.',
            ]);
        }

        if (!config('account_restrictions.enabled', false) || !$user) {
            return;
        }

        if ($user->booking_locked_until && now()->lt($user->booking_locked_until)) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản đang bị tạm khóa đặt phòng đến ' . $user->booking_locked_until->format('d/m/Y H:i') . '.',
            ]);
        }
    }
}
