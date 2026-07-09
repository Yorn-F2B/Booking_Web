<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = [
        'name',
        'icon',
    ];

    public function roomCategories()
    {
        return $this->belongsToMany(
            RoomCategory::class,
            'room_category_amenities',
            'amenity_id',
            'room_category_id'
        );
    }
}