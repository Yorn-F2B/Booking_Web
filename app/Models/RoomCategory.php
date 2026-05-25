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
}