<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomCategory extends Model
{
    use HasFactory;

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
