<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInspectionItem extends Model
{
    protected $fillable = [
        'room_inspection_id',
        'service_id',
        'type',
        'name',
        'unit',
        'price',
        'quantity',
        'total',
        'original_total',
        'status',
        'admin_note',
        'guest_response',
        'guest_response_note',
        'guest_claimed_quantity',
        'guest_responded_by',
        'guest_responded_at',
        'recheck_decision',
        'recheck_note',
        'rechecked_by',
        'rechecked_at',
        'detection_source',
        'detected_by',
        'detected_at',
        'detection_version',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'original_total' => 'decimal:2',
        'quantity' => 'integer',
        'guest_claimed_quantity' => 'integer',
        'guest_responded_at' => 'datetime',
        'rechecked_at' => 'datetime',
        'detected_at' => 'datetime',
        'detection_version' => 'integer',
    ];

    public function roomInspection()
    {
        return $this->belongsTo(RoomInspection::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function guestResponder()
    {
        return $this->belongsTo(User::class, 'guest_responded_by');
    }

    public function rechecker()
    {
        return $this->belongsTo(User::class, 'rechecked_by');
    }

    public function detector()
    {
        return $this->belongsTo(User::class, 'detected_by');
    }

    public function revisions()
    {
        return $this->hasMany(RoomInspectionRevision::class, 'room_inspection_item_id');
    }
}
