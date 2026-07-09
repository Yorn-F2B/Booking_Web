<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_code',
        'booking_id',
        'customer_name',
        'room_numbers',
        'check_in_date',
        'check_out_date',
        'actual_check_in',
        'actual_check_out',
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
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'issued_at' => 'datetime',
        'printed_at' => 'datetime',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentStatusLabelAttribute()
    {
        return match($this->payment_status) {
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Đã cọc',
            'paid' => 'Đã thanh toán',
            default => 'Chưa rõ',
        };
    }

    public function scopeFilter($query, $filters)
    {
        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        if (isset($filters['booking_code'])) {
            $query->whereHas('booking', function ($q) use ($filters) {
                $q->where('booking_code', 'like', '%' . $filters['booking_code'] . '%');
            });
        }

        if (isset($filters['customer_name'])) {
            $query->where('customer_name', 'like', '%' . $filters['customer_name'] . '%');
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query;
    }
}