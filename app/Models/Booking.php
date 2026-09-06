<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    public const DEFAULT_CLEANING_BUFFER_MINUTES = 0;

    public const RESCHEDULED_AFTER_G_POLICY_PREFIX = '[RESCHEDULED_AFTER_G]';
    public const RESCHEDULED_AFTER_G_GRACE_MINUTES = 120;

    protected $fillable = [
        'booking_code',
        'customer_id',
        'customer_name_snapshot',
        'customer_phone_snapshot',
        'customer_email_snapshot',
        'customer_cccd_snapshot',
        'customer_birthday_snapshot',
        'customer_gender_snapshot',
        'customer_address_snapshot',
        'created_by',
        'room_category_id',
        'booking_type',
        'booking_mode',
        'booking_source',
        'check_in_date',
        'check_out_date',
        'check_in_at',
        'check_out_at',
        'actual_check_in',
        'actual_check_out',
        'adult_count',
        'child_count',
        'room_quantity',
        'prefer_adjacent_rooms',
        'room_selection_mode',
        'room_selection_request',
        'room_selection_status',
        'room_selection_fee',
        'room_selection_handled_by',
        'room_selection_handled_at',
        'room_selection_handling_note',
        'room_selection_guest_decided_at',
        'refund_due_amount',
        'refund_status',
        'refund_reason',
        'refund_processed_at',
        'refund_processed_by',
        'refund_processed_note',
        'subtotal_amount',
        'discount_amount',
        'estimated_total',
        'final_total',
        'deposit_amount',
        'required_deposit_amount',
        'overpayment_amount',
        'payment_expires_at',
        'payment_status',
        'status',
        'note',
        'late_arrival_fee',
        'late_arrival_hours',
        'late_arrival_policy',
        'late_arrival_confirmed_at',
        'late_arrival_confirmed_by',
        'cleaning_buffer_minutes',
        'policy_snapshot',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'payment_expires_at' => 'datetime',
        'late_arrival_confirmed_at' => 'datetime',
        'room_selection_handled_at' => 'datetime',
        'room_selection_guest_decided_at' => 'datetime',
        'refund_processed_at' => 'datetime',
        'customer_birthday_snapshot' => 'date',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'final_total' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'required_deposit_amount' => 'decimal:2',
        'overpayment_amount' => 'decimal:2',
        'room_selection_fee' => 'decimal:2',
        'refund_due_amount' => 'decimal:2',
        'policy_snapshot' => 'array',
    ];


    /**
     * Booking còn hiệu lực vận hành: đã xác nhận/đang ở/chờ kiểm tra, hoặc đơn
     * pending vẫn còn thời hạn giữ phòng (hay đã có giao dịch thành công nhưng
     * callback chưa kịp chuyển trạng thái). Đơn pending hết hạn không được chặn
     * tồn phòng/khách chỉ vì scheduler chưa chạy.
     */
    public function scopeActiveForOperations(Builder $query): Builder
    {
        return $query->where(function (Builder $active) {
            $active->whereIn('status', ['confirmed', 'checked_in', 'inspection_requested'])
                ->orWhere(function (Builder $pending) {
                    $pending->where('status', 'pending')
                        ->where(function (Builder $validHold) {
                            $validHold->where('payment_expires_at', '>', now('Asia/Ho_Chi_Minh'))
                                ->orWhereHas('payments', fn (Builder $payment) => $payment->where('status', 'success'));
                        });
                });
        });
    }

    public static function customerSnapshotAttributes(?Customer $customer): array
    {
        if (!$customer) {
            return [];
        }

        return [
            'customer_name_snapshot' => trim(($customer->last_name ?? '') . ' ' . ($customer->first_name ?? '')),
            'customer_phone_snapshot' => $customer->phone,
            'customer_email_snapshot' => $customer->email,
            'customer_cccd_snapshot' => $customer->cccd,
            'customer_birthday_snapshot' => $customer->birthday,
            'customer_gender_snapshot' => $customer->gender,
            'customer_address_snapshot' => $customer->address,
        ];
    }

    public function getBookedCustomerNameAttribute(): string
    {
        $snapshot = trim((string) $this->customer_name_snapshot);

        if ($snapshot !== '') {
            return $snapshot;
        }

        return trim(($this->customer?->last_name ?? '') . ' ' . ($this->customer?->first_name ?? ''));
    }

    public function getBookedCustomerPhoneAttribute(): ?string
    {
        return $this->customer_phone_snapshot ?: $this->customer?->phone;
    }

    public function getBookedCustomerEmailAttribute(): ?string
    {
        return $this->customer_email_snapshot ?: $this->customer?->email;
    }

    public function getBookedCustomerCccdAttribute(): ?string
    {
        return $this->customer_cccd_snapshot ?: $this->customer?->cccd;
    }

    public function getBookedCustomerBirthdayAttribute()
    {
        return $this->customer_birthday_snapshot ?: $this->customer?->birthday;
    }

    public function getBookedCustomerGenderAttribute(): ?string
    {
        return $this->customer_gender_snapshot ?: $this->customer?->gender;
    }

    public function getBookedCustomerAddressAttribute(): ?string
    {
        return $this->customer_address_snapshot ?: $this->customer?->address;
    }

    public function cancellationRequests()
    {
        return $this->hasMany(BookingCancellationRequest::class);
    }

    public function roomIssueRequests()
    {
        return $this->hasMany(RoomIssueRequest::class);
    }

    public function roomChanges()
    {
        return $this->hasMany(BookingRoomChange::class);
    }

    public function pendingRoomIssueRequest()
    {
        return $this->hasOne(RoomIssueRequest::class)->where('status', 'pending')->latestOfMany();
    }

    public function pendingCancellationRequest()
    {
        return $this->hasOne(BookingCancellationRequest::class)->where('status', 'pending')->latestOfMany();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function roomSelectionHandler()
    {
        return $this->belongsTo(User::class, 'room_selection_handled_by');
    }

    public function roomCategory()
    {
        return $this->belongsTo(RoomCategory::class);
    }

    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class);
    }

    public function guests()
    {
        return $this->hasMany(BookingGuest::class);
    }

    public function roomInspections()
    {
        return $this->hasMany(RoomInspection::class);
    }

    public function serviceItems()
    {
        return $this->hasMany(BookingServiceItem::class);
    }

    public function logs()
    {
        return $this->hasMany(BookingLog::class)->latest();
    }

    public function hotelReview()
    {
        return $this->hasOne(HotelReview::class);
    }

    public function canBeReviewed(): bool
    {
        return in_array($this->status, ['checked_out', 'completed'], true)
            && $this->actual_check_in !== null
            && $this->actual_check_out !== null;
    }

    /**
     * Giới hạn phạm vi vận hành booking theo phân công lễ tân.
     * Trưởng lễ tân/quản lý có thể điều phối toàn bộ. Lễ tân thường chỉ thấy
     * booking đang được phân công trực tiếp cho chính họ.
     */
    public function scopeVisibleToOperationsUser(Builder $query, ?User $user): Builder
    {
        if (!$user || !in_array($user->role, ['super_admin', 'manager', 'receptionist_lead', 'receptionist'], true)) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->role !== 'receptionist') {
            return $query;
        }

        return $query->whereHas(
            'staffAssignments',
            fn (Builder $assignment) => $assignment
                ->where('staff_id', $user->id)
                ->whereIn('status', ['active', 'done'])
        );
    }

    public function canBeHandledBy(?User $user): bool
    {
        if (!$user || !in_array($user->role, ['super_admin', 'manager', 'receptionist_lead', 'receptionist'], true)) {
            return false;
        }

        if ($user->role !== 'receptionist') {
            return true;
        }

        return $this->staffAssignments()
            ->where('staff_id', $user->id)
            ->whereIn('status', ['active', 'done'])
            ->exists();
    }


    public function payments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    public function bookingPromotions()
    {
        return $this->hasMany(BookingPromotion::class);
    }

    public function promotionServiceOffers()
    {
        return $this->hasMany(BookingPromotionServiceOffer::class);
    }

    public function promotionRoomUpgrades()
    {
        return $this->hasMany(BookingPromotionRoomUpgrade::class);
    }


    public function staffAssignments()
    {
        return $this->hasMany(BookingStaffAssignment::class);
    }

    public function activeStaffAssignments()
    {
        return $this->hasMany(BookingStaffAssignment::class)->where('status', 'active');
    }

    public function assignedStaff()
    {
        return $this->belongsToMany(User::class, 'booking_staff_assignments', 'booking_id', 'staff_id')
            ->withPivot(['role_in_booking', 'assigned_by', 'status', 'note'])
            ->withTimestamps();
    }

    public function isAssignedTo(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return $this->staffAssignments()
            ->where('staff_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    public function rescheduledAfterCutoffAt(): ?\Carbon\Carbon
    {
        if (
            str_starts_with((string) $this->late_arrival_policy, self::RESCHEDULED_AFTER_G_POLICY_PREFIX)
            && $this->late_arrival_confirmed_at
        ) {
            return \Carbon\Carbon::parse($this->late_arrival_confirmed_at, 'Asia/Ho_Chi_Minh');
        }

        if (!$this->check_in_at || $this->booking_type === 'hourly') {
            return null;
        }

        $checkInAt = \Carbon\Carbon::parse(
            $this->getRawOriginal('check_in_at'),
            'Asia/Ho_Chi_Minh'
        );
        $latestChangeLog = $this->logs()
            ->where('action', 'change_stay_dates')
            ->latest('id')
            ->first();

        if (!$latestChangeLog) {
            return null;
        }

        $changedAt = \Carbon\Carbon::parse($latestChangeLog->created_at, 'Asia/Ho_Chi_Minh');
        $cutoffAt = $changedAt->copy()->setTimeFromTimeString($this->lateArrivalCutoffTime());

        // Tương thích cả các đơn đã đổi ngày trước khi cài bản sửa: log đổi ngày được tạo
        // đúng ngày nhận mới và sau giờ G của booking thì dùng thời điểm đổi lịch làm mốc mới.
        if (
            $checkInAt->toDateString() === $changedAt->toDateString()
            && $changedAt->greaterThanOrEqualTo($cutoffAt)
        ) {
            return $changedAt;
        }

        return null;
    }

    public function isRescheduledAfterCutoff(): bool
    {
        return $this->rescheduledAfterCutoffAt() !== null;
    }

    /**
     * Giờ G/no-show chỉ dành cho booking đặt trước qua đêm đã tồn tại trước giờ G.
     *
     * Không áp dụng cho:
     * - booking ở ngay (walk-in), kể cả ở ngay qua đêm;
     * - booking theo giờ;
     * - booking vừa được chuyển từ ngày tương lai về hôm nay sau giờ G;
     * - booking đặt trước được tạo mới sau giờ G của chính ngày nhận phòng.
     */
    public function usesLateArrivalNoShowPolicy(): bool
    {
        $bookingMode = (string) ($this->getRawOriginal('booking_mode') ?: $this->getAttribute('booking_mode'));
        $bookingType = (string) ($this->getRawOriginal('booking_type') ?: $this->getAttribute('booking_type'));
        $rawCheckInAt = $this->getRawOriginal('check_in_at');

        if ($bookingMode !== 'advance' || $bookingType !== 'overnight' || !$rawCheckInAt) {
            return false;
        }

        $timezone = 'Asia/Ho_Chi_Minh';
        $checkInAt = \Carbon\Carbon::parse($rawCheckInAt, $timezone);
        [$holdHour, $holdMinute] = array_map('intval', explode(':', $this->lateArrivalCutoffTime()));
        $cutoffAt = $checkInAt->copy()->setTime($holdHour, $holdMinute, 0);

        // Đơn được tạo tại quầy sau giờ G không thể bị coi là khách đã no-show
        // ngay tại thời điểm vừa tạo.
        $rawCreatedAt = $this->getRawOriginal('created_at');
        if ($rawCreatedAt) {
            $createdAt = \Carbon\Carbon::parse($rawCreatedAt, $timezone);
            if ($createdAt->greaterThanOrEqualTo($cutoffAt)) {
                return false;
            }
        }

        return !$this->isRescheduledAfterCutoff();
    }

    public function lateArrivalHoldLimitAt(): ?\Carbon\Carbon
    {
        if (!$this->usesLateArrivalNoShowPolicy()) {
            return null;
        }

        $checkInAt = \Carbon\Carbon::parse($this->getRawOriginal('check_in_at'), 'Asia/Ho_Chi_Minh');
        [$holdHour, $holdMinute] = array_map('intval', explode(':', $this->lateArrivalCutoffTime()));
        $standardHoldAt = $checkInAt->copy()->setTime($holdHour, $holdMinute, 0);

        // Khi lễ tân đã xác nhận khách sẽ đến sau giờ G, late_arrival_hours lưu
        // số giờ từ giờ G đến giờ khách dự kiến tới. Giữ thêm thời gian ân hạn theo policy để khách
        // làm thủ tục, nhưng không vượt quá giờ trả phòng dự kiến.
        $lateArrivalConfirmedAt = $this->getRawOriginal('late_arrival_confirmed_at');
        $lateArrivalHours = (float) ($this->getRawOriginal('late_arrival_hours') ?? 0);

        if ($lateArrivalConfirmedAt && $lateArrivalHours > 0) {
            $extendedHoldAt = $standardHoldAt->copy()
                ->addMinutes((int) round($lateArrivalHours * 60))
                ->addMinutes($this->lateArrivalGraceMinutes());

            $rawCheckOutAt = $this->getRawOriginal('check_out_at');
            if ($rawCheckOutAt) {
                $checkOutAt = \Carbon\Carbon::parse($rawCheckOutAt, 'Asia/Ho_Chi_Minh');
                return $extendedHoldAt->min($checkOutAt);
            }

            return $extendedHoldAt;
        }

        return $standardHoldAt;
    }

    public function policyValue(string $key, mixed $fallback = null): mixed
    {
        return app(\App\Services\HotelPolicyService::class)->forBooking($this, $key, $fallback);
    }

    public function standardCheckInTime(): string
    {
        return (string) $this->policyValue('stay.standard_check_in_time', '14:00') . ':00';
    }

    public function standardCheckOutTime(): string
    {
        return (string) $this->policyValue('stay.standard_check_out_time', '12:00') . ':00';
    }

    public function lateArrivalCutoffTime(): string
    {
        return (string) $this->policyValue('stay.late_arrival_cutoff_time', '18:00') . ':00';
    }

    public function directCancelCutoffTime(): string
    {
        return (string) $this->policyValue('booking.direct_cancel_cutoff_time', '14:00') . ':00';
    }

    public function hourlyCancelGraceMinutes(): int
    {
        return max(0, (int) $this->policyValue('booking.hourly_cancel_grace_minutes', 30));
    }

    public function lateArrivalGraceMinutes(): int
    {
        return max(0, (int) $this->policyValue('stay.late_arrival_grace_minutes', 30));
    }

    public function rescheduledAfterCutoffGraceMinutes(): int
    {
        return max(0, (int) $this->policyValue('stay.rescheduled_after_cutoff_grace_minutes', self::RESCHEDULED_AFTER_G_GRACE_MINUTES));
    }

    /**
     * Khách trả muộn khi thời điểm thực tế hiện tại/đã trả vượt quá giờ trả
     * đang có trên booking. Nếu booking đã được gia hạn thì check_out_at đã
     * mang mốc mới, vì vậy phần gia hạn không bị gắn nhãn trả muộn.
     */
    public function isLateCheckout($at = null): bool
    {
        if (!$this->check_out_at) {
            return false;
        }

        $timezone = 'Asia/Ho_Chi_Minh';
        $plannedCheckOut = \Carbon\Carbon::parse($this->check_out_at, $timezone);

        if (in_array($this->status, ['checked_out', 'completed'], true)) {
            if (!$this->actual_check_out) {
                return false;
            }
            $effectiveCheckOut = \Carbon\Carbon::parse($this->actual_check_out, $timezone);
        } elseif (in_array($this->status, ['checked_in', 'inspection_requested'], true)) {
            if ($at instanceof \DateTimeInterface) {
                $effectiveCheckOut = \Carbon\Carbon::instance($at)->timezone($timezone);
            } elseif ($at) {
                $effectiveCheckOut = \Carbon\Carbon::parse($at, $timezone);
            } else {
                $effectiveCheckOut = now($timezone);
            }
        } else {
            return false;
        }

        return $effectiveCheckOut->greaterThan($plannedCheckOut);
    }

    public function lateCheckoutMinutes($at = null): int
    {
        if (!$this->isLateCheckout($at)) {
            return 0;
        }

        $timezone = 'Asia/Ho_Chi_Minh';
        $plannedCheckOut = \Carbon\Carbon::parse($this->check_out_at, $timezone);

        if (in_array($this->status, ['checked_out', 'completed'], true) && $this->actual_check_out) {
            $effectiveCheckOut = \Carbon\Carbon::parse($this->actual_check_out, $timezone);
        } elseif ($at instanceof \DateTimeInterface) {
            $effectiveCheckOut = \Carbon\Carbon::instance($at)->timezone($timezone);
        } elseif ($at) {
            $effectiveCheckOut = \Carbon\Carbon::parse($at, $timezone);
        } else {
            $effectiveCheckOut = now($timezone);
        }

        return max(0, (int) $plannedCheckOut->diffInMinutes($effectiveCheckOut));
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'booking_promotions')
            ->withPivot([
                'code_snapshot',
                'promotion_type_snapshot',
                'discount_type_snapshot',
                'discount_value_snapshot',
                'money_discount_amount',
                'service_discount_amount',
                'room_upgrade_discount_amount',
                'discount_amount',
                'applied_by',
                'applied_channel',
                'note',
            ])
            ->withTimestamps();
    }
    public function customerRequests()
    {
        return $this->hasMany(CustomerRequest::class)->latest();
    }


}
