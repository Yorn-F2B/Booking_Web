<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    public const STANDARD_CHECK_OUT_TIME = '12:00:00';
    public const PRIORITY_CLEANING_START_TIME = '12:00:00';
    public const EARLY_CHECK_IN_TIME = '13:00:00';
    public const STANDARD_CHECK_IN_TIME = '14:00:00';
    public const DEFAULT_CLEANING_BUFFER_MINUTES = 60;
    public const LATE_ARRIVAL_ONE_NIGHT_HOLD_TIME = '18:00:00';
    public const LATE_ARRIVAL_MULTI_NIGHT_HOLD_DAYS = 1;

    protected $fillable = [
        'booking_code',
        'customer_id',
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
        'subtotal_amount',
        'discount_amount',
        'estimated_total',
        'deposit_amount',
        'payment_status',
        'status',
        'note',
        'late_arrival_fee',
        'late_arrival_hours',
        'late_arrival_policy',
        'cleaning_buffer_minutes',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function roomCategory()
    {
        return $this->belongsTo(RoomCategory::class);
    }

    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class);
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
        return in_array($this->status, ['checked_out', 'completed'], true);
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
}
