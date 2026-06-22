<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\RoomCategory;
use App\Models\RoomCategoryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomCategoryController extends Controller
{
    public function index()
    {
        $roomCategories = RoomCategory::with(['images', 'amenities'])
            ->latest()
            ->paginate(10);

        return view(
            'admin.pages.room-categories.index',
            compact('roomCategories')
        );
    }

    public function create()
    {
        $amenities = Amenity::orderBy('name')->get();

        return view(
            'admin.pages.room-categories.create',
            compact('amenities')
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([

            'name' => 'required|max:100',

            'price' => 'required|numeric|min:0',

            'adult_capacity' => 'required|integer|min:1',

            'child_capacity' => 'nullable|integer|min:0',

            'area' => 'nullable|numeric|min:0',

            'bed_count' => 'nullable|integer|min:1',

            'description' => 'nullable',

            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'status' => 'required|in:active,inactive',

            'amenities' => 'nullable|array',

            'amenities.*' => 'exists:amenities,id',

        ]);

        $amenityIds = $data['amenities'] ?? [];

        unset($data['amenities']);

        if ($request->hasFile('thumbnail')) {

            $data['thumbnail'] = $request->file('thumbnail')
                ->store('room-categories/thumbnails', 'public');
        }

        $roomCategory = RoomCategory::create($data);

        $roomCategory->amenities()->sync($amenityIds);

        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $path = $image->store(
                    'room-categories/albums',
                    'public'
                );

                RoomCategoryImage::create([

                    'room_category_id' => $roomCategory->id,

                    'image' => $path,

                ]);
            }
        }

        return redirect()
            ->route('room-categories.index')
            ->with('success', 'Thêm loại phòng thành công');
    }

    public function show(RoomCategory $roomCategory)
    {
        $roomCategory->load(['images', 'amenities']);

        return view(
            'admin.pages.room-categories.show',
            compact('roomCategory')
        );
    }

    public function edit(RoomCategory $roomCategory)
    {
        $roomCategory->load(['images', 'amenities']);

        $amenities = Amenity::orderBy('name')->get();

        return view(
            'admin.pages.room-categories.edit',
            compact('roomCategory', 'amenities')
        );
    }

    public function update(
        Request $request,
        RoomCategory $roomCategory
    ) {
        $data = $request->validate([

            'name' => 'required|max:100',

            'price' => 'required|numeric|min:0',

            'adult_capacity' => 'required|integer|min:1',

            'child_capacity' => 'nullable|integer|min:0',

            'area' => 'nullable|numeric|min:0',

            'bed_count' => 'nullable|integer|min:1',

            'description' => 'nullable',

            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'status' => 'required|in:active,inactive',

            'amenities' => 'nullable|array',

            'amenities.*' => 'exists:amenities,id',

        ]);

        $amenityIds = $data['amenities'] ?? [];

        unset($data['amenities']);

        if ($request->hasFile('thumbnail')) {

            if ($roomCategory->thumbnail) {
                Storage::disk('public')->delete($roomCategory->thumbnail);
            }

            $data['thumbnail'] = $request->file('thumbnail')
                ->store('room-categories/thumbnails', 'public');
        }

        $roomCategory->update($data);

        $roomCategory->amenities()->sync($amenityIds);

        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $path = $image->store(
                    'room-categories/albums',
                    'public'
                );

                RoomCategoryImage::create([

                    'room_category_id' => $roomCategory->id,

                    'image' => $path,

                ]);
            }
        }

        return redirect()
            ->route('room-categories.index')
            ->with('success', 'Cập nhật loại phòng thành công');
    }

    public function destroy(RoomCategory $roomCategory)
    {
        $roomCategory->delete();

        return redirect()
            ->route('room-categories.index')
            ->with('success', 'Xóa loại phòng thành công');
    }
}