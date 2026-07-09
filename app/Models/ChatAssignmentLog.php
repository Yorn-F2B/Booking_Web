<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAssignmentLog extends Model
{
    protected $fillable = [
        'conversation_id',
        'from_staff_id',
        'to_staff_id',
        'reason',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function fromStaff()
    {
        return $this->belongsTo(User::class, 'from_staff_id');
    }

    public function toStaff()
    {
        return $this->belongsTo(User::class, 'to_staff_id');
    }
}