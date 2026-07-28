<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInspectionRevision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_inspection_id',
        'room_inspection_item_id',
        'version',
        'event_type',
        'summary',
        'before_data',
        'after_data',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'before_data' => 'array',
        'after_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function inspection()
    {
        return $this->belongsTo(RoomInspection::class, 'room_inspection_id');
    }

    public function item()
    {
        return $this->belongsTo(RoomInspectionItem::class, 'room_inspection_item_id');
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
