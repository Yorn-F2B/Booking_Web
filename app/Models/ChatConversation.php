<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $fillable = [
        'customer_id',
        'booking_id',
        'assigned_staff_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'status',
        'priority_score',
        'last_message_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')
            ->orderBy('id');
    }

    public function assignmentLogs()
    {
        return $this->hasMany(ChatAssignmentLog::class, 'conversation_id')
            ->latest('id');
    }

    public function getCustomerDisplayNameAttribute(): string
    {
        return $this->customer?->name
            ?? $this->guest_name
            ?? 'Khách vãng lai';
    }
}
