<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomCategoryController extends Controller
{
    public function index()
    {
        $categories = RoomCategory::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.room-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.room-categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'max_people' => 'required|integer|min:1',
            'area' => 'nullable|numeric|min:0',
            'bed_count' => 'nullable|integer|min:1',
            'bed_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $data = $request->except('thumbnail');

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('room_categories', 'public');
            $data['thumbnail'] = $path;
        }

        RoomCategory::create($data);

        return redirect()->route('admin.room-categories.index')->with('success', 'Thêm loại phòng thành công!');
    }

    public function edit(RoomCategory $roomCategory)
    {
        return view('admin.room-categories.edit', compact('roomCategory'));
    }

    public function update(Request $request, RoomCategory $roomCategory)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'max_people' => 'required|integer|min:1',
            'area' => 'nullable|numeric|min:0',
            'bed_count' => 'nullable|integer|min:1',
            'bed_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $data = $request->except('thumbnail');

        if ($request->hasFile('thumbnail')) {
            if ($roomCategory->thumbnail && Storage::disk('public')->exists($roomCategory->thumbnail)) {
                Storage::disk('public')->delete($roomCategory->thumbnail);
            }
            $path = $request->file('thumbnail')->store('room_categories', 'public');
            $data['thumbnail'] = $path;
        }

        $roomCategory->update($data);

        return redirect()->route('admin.room-categories.index')->with('success', 'Cập nhật loại phòng thành công!');
    }

    public function destroy(RoomCategory $roomCategory)
    {
        if ($roomCategory->thumbnail && Storage::disk('public')->exists($roomCategory->thumbnail)) {
            Storage::disk('public')->delete($roomCategory->thumbnail);
        }
        $roomCategory->delete();

        return redirect()->route('admin.room-categories.index')->with('success', 'Xóa loại phòng thành công!');
    }
}
