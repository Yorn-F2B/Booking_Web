<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomCategory extends Model
{
    protected $table = 'room_categories';

    protected $fillable = [
        'name',
        'price',
        'max_people',
        'area',
        'bed_count',
        'bed_type',
        'description',
        'thumbnail',
        'status',
    ];
}
