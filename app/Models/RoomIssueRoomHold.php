<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomIssueRoomHold extends Model
{
    protected $fillable = [
        'group_uuid', 'room_issue_request_id', 'booking_id', 'room_id', 'held_by',
        'held_at', 'expires_at', 'released_at', 'release_reason',
    ];

    protected $casts = [
        'held_at' => 'datetime',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function issue(){ return $this->belongsTo(RoomIssueRequest::class, 'room_issue_request_id'); }
    public function booking(){ return $this->belongsTo(Booking::class); }
    public function room(){ return $this->belongsTo(Room::class); }
    public function holder(){ return $this->belongsTo(User::class, 'held_by'); }
}
