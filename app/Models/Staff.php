<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staffs';
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'cccd',
        'birthday',
        'gender',
        'address',
        'position',
        'salary',
        'hire_date',
        'avatar',
        'work_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}