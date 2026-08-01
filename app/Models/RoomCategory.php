<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomCategory extends Model
{
    protected $fillable = [
        'name',
        'price',
        'adult_capacity',
        'child_capacity',
        'area',
        'bed_count',
        'description',
        'thumbnail',
        'status',
    ];

    public function images()
    {
        return $this->hasMany(RoomCategoryImage::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'room_category_id');
    }

    public function amenities()
    {
        return $this->belongsToMany(
            Amenity::class,
            'room_category_amenities',
            'room_category_id',
            'amenity_id'
        );
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}