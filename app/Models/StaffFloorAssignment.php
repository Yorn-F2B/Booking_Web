<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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


    public function scopeEffectiveOn(Builder $query, $date = null): Builder
    {
        $effectiveDate = $date
            ? Carbon::parse($date, 'Asia/Ho_Chi_Minh')->toDateString()
            : now('Asia/Ho_Chi_Minh')->toDateString();

        return $query
            ->whereDate('work_date', '<=', $effectiveDate)
            ->where('status', 'active');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
