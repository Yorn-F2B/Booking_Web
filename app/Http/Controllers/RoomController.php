<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomCategory;

class RoomController extends Controller
{
    public function home()
    {
        $featuredRooms = RoomCategory::where('status', 'active')->limit(3)->get();
        return view('user.pages.home', compact('featuredRooms'));
    }

    public function index()
    {
        $roomCategories = RoomCategory::where('status', 'active')->paginate(10);
        return view('user.pages.rooms', compact('roomCategories'));
    }
}
