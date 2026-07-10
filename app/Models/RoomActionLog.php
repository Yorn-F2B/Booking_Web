<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'user_id',
        'action_type',
        'action_time',
        'note',
    ];

    protected $casts = [
        'action_time' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
