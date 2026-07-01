<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffFloorAssignment extends Model
{
    protected $fillable = [
        'staff_id',
        'floor_number',
        'work_date',
        'shift',
        'status',
        'assigned_by',
        'note',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
