<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'extension',
        'size',
        'type',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }
}
