<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelReview extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'booking_id',
        'user_id',
        'customer_id',
        'room_category_id',
        'rating',
        'cleanliness_rating',
        'service_rating',
        'location_rating',
        'staff_rating',
        'comfort_rating',
        'value_rating',
        'title',
        'comment',
        'status',
        'approved_by',
        'approved_at',
        'hidden_by',
        'hidden_at',
        'hidden_reason',
        'admin_reply',
        'replied_by',
        'replied_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'cleanliness_rating' => 'integer',
        'service_rating' => 'integer',
        'location_rating' => 'integer',
        'staff_rating' => 'integer',
        'comfort_rating' => 'integer',
        'value_rating' => 'integer',
        'approved_at' => 'datetime',
        'hidden_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function roomCategory()
    {
        return $this->belongsTo(RoomCategory::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function hider()
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function replier()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /**
     * DB cũ dùng location_rating. Từ phiên bản hiện tại cột này được giữ lại
     * để tương thích lịch sử nhưng biểu diễn tiêu chí Chất lượng / tiện nghi phòng.
     */
    public function getRoomQualityRatingAttribute(): ?int
    {
        return $this->location_rating === null ? null : (int) $this->location_rating;
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeHidden($query)
    {
        return $query->where('status', self::STATUS_HIDDEN);
    }

    public function getGuestNameAttribute(): string
    {
        if ($this->customer) {
            $name = trim(($this->customer->first_name ?? '') . ' ' . ($this->customer->last_name ?? ''));

            if ($name !== '') {
                return $name;
            }
        }

        return $this->user->name ?? 'Khách hàng';
    }

    public function getGuestInitialsAttribute(): string
    {
        $name = trim($this->guest_name);

        if ($name === '') {
            return 'KH';
        }

        $words = preg_split('/\s+/u', $name);
        $first = mb_substr($words[0] ?? 'K', 0, 1);
        $last = mb_substr($words[count($words) - 1] ?? 'H', 0, 1);

        return mb_strtoupper($first . $last);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_HIDDEN => 'Đã ẩn',
            default => 'Chờ duyệt',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'text-bg-success',
            self::STATUS_HIDDEN => 'text-bg-secondary',
            default => 'text-bg-warning',
        };
    }

    public function getStarTextAttribute(): string
    {
        $rating = max(0, min(5, (int) $this->rating));

        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }
}
