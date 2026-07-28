<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerRequest extends Model
{
    protected $fillable = [
        'booking_id','type','source','status','customer_name','customer_email','reason','details',
        'requested_at','expected_arrival_at','requested_check_out_at','admin_note','receptionist_note',
        'reviewed_by','reviewed_at'
    ];

    protected $casts = [
        'details' => 'array',
        'requested_at' => 'datetime',
        'expected_arrival_at' => 'datetime',
        'requested_check_out_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function attachments() { return $this->hasMany(CustomerRequestAttachment::class); }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'late_arrival' => 'Đến sau giờ G',
            default => 'Yêu cầu đến sau giờ G',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Chờ admin duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'completed' => 'Hoàn tất',
            default => $this->status,
        };
    }
}
