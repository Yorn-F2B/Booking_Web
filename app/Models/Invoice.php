<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_code',
        'booking_id',
        'customer_id',
        'customer_name',
        'room_numbers',
        'check_in_date',
        'check_out_date',
        'actual_check_in',
        'actual_check_out',
        'room_total',
        'service_total',
        'inspection_total',
        'promotion_discount',
        'final_total',
        'total_paid',
        'overpayment_amount',
        'status',
        'issued_by',
        'room_charge',
        'service_charge',
        'minibar_charge',
        'extra_charge',
        'damage_fee',
        'deposit_amount',
        'remaining_amount',
        'total_amount',
        'payment_status',
        'issued_at',
        'printed_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'issued_at' => 'datetime',
        'printed_at' => 'datetime',
        'room_total' => 'decimal:2',
        'service_total' => 'decimal:2',
        'inspection_total' => 'decimal:2',
        'promotion_discount' => 'decimal:2',
        'final_total' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'overpayment_amount' => 'decimal:2',
        'room_charge' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'minibar_charge' => 'decimal:2',
        'extra_charge' => 'decimal:2',
        'damage_fee' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getResolvedCustomerNameAttribute(): string
    {
        $customerName = trim((string) ($this->customer_name ?? ''));

        if ($customerName !== '') {
            return $customerName;
        }

        $firstName = trim((string) ($this->customer->first_name ?? $this->booking?->customer?->first_name ?? ''));
        $lastName = trim((string) ($this->customer->last_name ?? $this->booking?->customer?->last_name ?? ''));
        $fullName = trim($lastName . ' ' . $firstName);

        return $fullName !== '' ? $fullName : 'Chưa có thông tin';
    }

    public function getResolvedRoomNumbersAttribute(): string
    {
        $roomNumbers = trim((string) ($this->room_numbers ?? ''));

        if ($roomNumbers !== '') {
            return $roomNumbers;
        }

        if ($this->relationLoaded('booking') && $this->booking) {
            $rooms = $this->booking->bookingRooms
                ->pluck('room.room_number')
                ->filter()
                ->implode(', ');

            if ($rooms !== '') {
                return $rooms;
            }
        }

        return 'Chưa gán phòng';
    }

    public function getResolvedCheckInDateAttribute()
    {
        return $this->check_in_date ?: $this->booking?->check_in_date;
    }

    public function getResolvedCheckOutDateAttribute()
    {
        return $this->check_out_date ?: $this->booking?->check_out_date;
    }

    public function getResolvedActualCheckInAttribute()
    {
        return $this->actual_check_in ?: $this->booking?->actual_check_in;
    }

    public function getResolvedActualCheckOutAttribute()
    {
        return $this->actual_check_out ?: $this->booking?->actual_check_out;
    }

    public function getResolvedRoomChargeAttribute(): float
    {
        return (float) ($this->room_charge ?? $this->room_total ?? 0);
    }

    public function getResolvedServiceChargeAttribute(): float
    {
        return (float) ($this->service_charge ?? $this->service_total ?? 0);
    }

    public function getResolvedInspectionChargeAttribute(): float
    {
        $explicitInspection = (float) ($this->inspection_total ?? 0);

        if ($explicitInspection > 0) {
            return $explicitInspection;
        }

        return (float) (($this->minibar_charge ?? 0) + ($this->damage_fee ?? 0));
    }

    public function getResolvedDiscountAmountAttribute(): float
    {
        return (float) ($this->promotion_discount ?? 0);
    }

    public function getResolvedFinalTotalAttribute(): float
    {
        if ($this->final_total !== null) {
            return (float) $this->final_total;
        }

        if ($this->total_amount !== null) {
            return (float) $this->total_amount;
        }

        return max(
            0,
            $this->resolved_room_charge
            + $this->resolved_service_charge
            + $this->resolved_inspection_charge
            - $this->resolved_discount_amount
        );
    }

    public function getResolvedTotalPaidAttribute(): float
    {
        if ($this->total_paid !== null) {
            return (float) $this->total_paid;
        }

        return (float) ($this->deposit_amount ?? 0);
    }

    public function getResolvedRemainingAmountAttribute(): float
    {
        if ($this->remaining_amount !== null) {
            return (float) $this->remaining_amount;
        }

        return max(0, $this->resolved_final_total - $this->resolved_total_paid);
    }

    public function getResolvedOverpaymentAmountAttribute(): float
    {
        if ($this->overpayment_amount !== null) {
            return (float) $this->overpayment_amount;
        }

        return max(0, $this->resolved_total_paid - $this->resolved_final_total);
    }

    public function getEffectivePaymentStatusAttribute(): string
    {
        if (!empty($this->payment_status)) {
            return $this->payment_status;
        }

        if ($this->resolved_remaining_amount <= 0) {
            return 'paid';
        }

        if ($this->resolved_total_paid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    public function getPaymentStatusLabelAttribute()
    {
        return match($this->effective_payment_status) {
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Đã cọc',
            'paid' => 'Đã thanh toán',
            default => 'Chưa rõ',
        };
    }

    public function scopeFilter($query, $filters)
    {
        if (!empty($filters['date'])) {
            $query->whereDate('issued_at', $filters['date']);
        }

        if (!empty($filters['booking_code'])) {
            $query->whereHas('booking', function ($q) use ($filters) {
                $q->where('booking_code', 'like', '%' . $filters['booking_code'] . '%');
            });
        }

        if (!empty($filters['customer_name'])) {
            $query->where(function ($subQuery) use ($filters) {
                $subQuery->where('customer_name', 'like', '%' . $filters['customer_name'] . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($filters) {
                        $customerQuery->where('first_name', 'like', '%' . $filters['customer_name'] . '%')
                            ->orWhere('last_name', 'like', '%' . $filters['customer_name'] . '%');
                    })
                    ->orWhereHas('booking.customer', function ($customerQuery) use ($filters) {
                        $customerQuery->where('first_name', 'like', '%' . $filters['customer_name'] . '%')
                            ->orWhere('last_name', 'like', '%' . $filters['customer_name'] . '%');
                    });
            });
        }

        if (!empty($filters['payment_status'])) {
            match ($filters['payment_status']) {
                'paid' => $query->where('remaining_amount', '<=', 0),
                'partial' => $query->where('remaining_amount', '>', 0)->where('total_paid', '>', 0),
                'unpaid' => $query->where('remaining_amount', '>', 0)->where('total_paid', '<=', 0),
                default => null,
            };
        }

        return $query;
    }
}
