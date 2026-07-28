<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffRoomAssignment extends Model
{
    protected $fillable = [
        'staff_id',
        'room_id',
        'work_date',
        'shift',
        'task_type',
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

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
