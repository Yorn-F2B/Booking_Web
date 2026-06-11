<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RoomCategory;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $roomCategories = RoomCategory::with(['images', 'amenities', 'rooms'])
            ->where('status', 'active');

        if ($request->filled('room_category_id')) {
            $roomCategories->where('id', $request->room_category_id);
        }

        if ($request->filled('adult_count')) {
            $roomCategories->where('adult_capacity', '>=', $request->adult_count);
        }

        if ($request->filled('child_count')) {
            $roomCategories->where('child_capacity', '>=', $request->child_count);
        }

        $roomCategories = $roomCategories
            ->latest()
            ->get();

        return view('user.pages.rooms', [
            'roomCategories' => $roomCategories,
            'searchData' => $request->only([
                'check_in_date',
                'check_out_date',
                'adult_count',
                'child_count',
                'room_category_id',
            ]),
        ]);
    }

    public function show(RoomCategory $roomCategory)
    {
        $roomCategory->load(['images', 'amenities', 'rooms']);

        return view('user.pages.room-detail', compact('roomCategory'));
    }
}