<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoomChange extends Model
{
    protected $fillable = [
        'booking_id', 'booking_room_id', 'room_issue_request_id',
        'old_room_id', 'new_room_id', 'old_room_category_id', 'new_room_category_id',
        'old_room_price', 'new_room_price', 'night_count', 'price_difference_total',
        'change_source', 'reason', 'changed_by',
    ];

    protected $casts = [
        'old_room_price' => 'decimal:2',
        'new_room_price' => 'decimal:2',
        'night_count' => 'integer',
        'price_difference_total' => 'decimal:2',
    ];

    public function booking(){ return $this->belongsTo(Booking::class); }
    public function bookingRoom(){ return $this->belongsTo(BookingRoom::class); }
    public function issue(){ return $this->belongsTo(RoomIssueRequest::class, 'room_issue_request_id'); }
    public function oldRoom(){ return $this->belongsTo(Room::class, 'old_room_id'); }
    public function newRoom(){ return $this->belongsTo(Room::class, 'new_room_id'); }
    public function oldCategory(){ return $this->belongsTo(RoomCategory::class, 'old_room_category_id'); }
    public function newCategory(){ return $this->belongsTo(RoomCategory::class, 'new_room_category_id'); }
    public function changer(){ return $this->belongsTo(User::class, 'changed_by'); }
}
