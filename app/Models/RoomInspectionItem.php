<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInspectionItem extends Model
{
    protected $fillable = [
        'room_inspection_id',
        'service_id',
        'name',
        'unit',
        'price',
        'quantity',
        'total',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function roomInspection()
    {
        return $this->belongsTo(RoomInspection::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}