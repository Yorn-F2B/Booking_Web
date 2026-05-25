<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomCategoryImage extends Model
{
    protected $fillable = [

        'room_category_id',

        'image',

    ];

    public function roomCategory()
    {
        return $this->belongsTo(RoomCategory::class);
    }
}