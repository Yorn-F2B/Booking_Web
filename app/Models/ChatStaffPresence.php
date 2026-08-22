<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatStaffPresence extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'last_seen_at',
        'last_assigned_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_assigned_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
