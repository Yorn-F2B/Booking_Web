<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    public function index()
    {
        $amenities = Amenity::latest()->paginate(10);

        return view(
            'admin.pages.amenities.index',
            compact('amenities')
        );
    }

    public function create()
    {
        return view('admin.pages.amenities.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:100',
            'icon' => 'nullable|max:255',
        ]);

        Amenity::create($data);

        return redirect()
            ->route('amenities.index')
            ->with('success', 'Thêm tiện ích thành công');
    }

    public function show(Amenity $amenity)
    {
        return view(
            'admin.pages.amenities.show',
            compact('amenity')
        );
    }

    public function edit(Amenity $amenity)
    {
        return view(
            'admin.pages.amenities.edit',
            compact('amenity')
        );
    }

    public function update(Request $request, Amenity $amenity)
    {
        $data = $request->validate([
            'name' => 'required|max:100',
            'icon' => 'nullable|max:255',
        ]);

        $amenity->update($data);

        return redirect()
            ->route('amenities.index')
            ->with('success', 'Cập nhật tiện ích thành công');
    }

    public function destroy(Amenity $amenity)
    {
        $amenity->delete();

        return redirect()
            ->route('amenities.index')
            ->with('success', 'Xóa tiện ích thành công');
    }
}