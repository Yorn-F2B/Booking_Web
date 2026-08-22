<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\RoomCategory;
use App\Models\RoomCategoryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class RoomCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $roomCategories = RoomCategory::query()
            ->with(['images', 'amenities'])
            ->withCount('rooms')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.pages.room-categories.index',
            compact('roomCategories', 'search', 'status')
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

            'delete_image_ids' => 'nullable|array',
            'delete_image_ids.*' => 'integer|exists:room_category_images,id',

            'status' => 'required|in:active,inactive',

            'amenities' => 'nullable|array',

            'amenities.*' => 'exists:amenities,id',

        ]);

        $amenityIds = $data['amenities'] ?? [];

        unset($data['amenities']);

        if ($request->hasFile('thumbnail')) {

            $data['thumbnail'] = $request->file('thumbnail')
                ->store('room-categories/thumbnails', 'public');
            $this->mirrorPublicFile($data['thumbnail']);
        }

        $roomCategory = RoomCategory::create($data);

        if (!empty($data['thumbnail'])) {
            $this->mirrorPublicFile($data['thumbnail']);
        }

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

                $this->mirrorPublicFile($path);
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

            'delete_image_ids' => 'nullable|array',
            'delete_image_ids.*' => 'integer|exists:room_category_images,id',

            'status' => 'required|in:active,inactive',

            'amenities' => 'nullable|array',

            'amenities.*' => 'exists:amenities,id',

        ]);

        $amenityIds = $data['amenities'] ?? [];
        $deleteImageIds = $data['delete_image_ids'] ?? [];

        unset($data['amenities'], $data['delete_image_ids']);

        if ($request->hasFile('thumbnail')) {

            if ($roomCategory->thumbnail) {
                Storage::disk('public')->delete($roomCategory->thumbnail);
            }

            $data['thumbnail'] = $request->file('thumbnail')
                ->store('room-categories/thumbnails', 'public');
            $this->mirrorPublicFile($data['thumbnail']);
        }

        $roomCategory->update($data);

        $roomCategory->amenities()->sync($amenityIds);

        if (!empty($deleteImageIds)) {
            $imagesToDelete = $roomCategory->images()->whereIn('id', $deleteImageIds)->get();
            foreach ($imagesToDelete as $imageToDelete) {
                Storage::disk('public')->delete($imageToDelete->image);
                File::delete(public_path('storage/' . ltrim($imageToDelete->image, '/')));
                $imageToDelete->delete();
            }
        }

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

                $this->mirrorPublicFile($path);
            }
        }

        return redirect()
            ->route('room-categories.index')
            ->with('success', 'Cập nhật loại phòng thành công');
    }

    private function mirrorPublicFile(string $relativePath): void
    {
        $source = storage_path('app/public/' . ltrim($relativePath, '/'));
        $target = public_path('storage/' . ltrim($relativePath, '/'));

        if (File::exists($source)) {
            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);
        }
    }

    public function destroy(RoomCategory $roomCategory)
    {
        if ($roomCategory->rooms()->exists() || $roomCategory->bookings()->exists()) {
            $roomCategory->update(['status' => 'inactive']);

            return redirect()
                ->route('room-categories.index')
                ->with('success', 'Hạng phòng đã có dữ liệu sử dụng nên hệ thống chỉ chuyển sang Ngừng hoạt động để bảo toàn lịch sử.');
        }

        $roomCategory->delete();

        return redirect()
            ->route('room-categories.index')
            ->with('success', 'Xóa loại phòng thành công');
    }
}