<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomCategory;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::with('category');

        if ($request->filled('room_number')) {
            $rooms->where('room_number', 'like', '%' . $request->room_number . '%');
        }

        if ($request->filled('status')) {
            $rooms->where('status', $request->status);
        }

        if ($request->filled('floor_number')) {
            $rooms->where('floor_number', $request->floor_number);
        }

        $maxFloor = Room::max('floor_number') ?? 0;

        $rooms = $rooms
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->paginate(10);

        return view('admin.pages.rooms.index', compact('rooms', 'maxFloor'));
    }

    public function create()
    {
        $categories = RoomCategory::where('status', 'active')->get();

        return view('admin.pages.rooms.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_number' => 'required|max:20|unique:rooms,room_number',
            'room_category_id' => 'required|exists:room_categories,id',
            'floor_number' => 'nullable|integer|min:0',
            'status' => 'required|in:available,reserved,occupied,cleaning,maintenance',
            'note' => 'nullable|string',
        ]);

        Room::create($data);

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Thêm phòng thành công');
    }

    public function show(Room $room)
    {
        $room->load('category');

        return view('admin.pages.rooms.show', compact('room'));
    }

    public function edit(Room $room)
    {
        $categories = RoomCategory::where('status', 'active')->get();

        return view('admin.pages.rooms.edit', compact('room', 'categories'));
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'room_number' => 'required|max:20|unique:rooms,room_number,' . $room->id,
            'room_category_id' => 'required|exists:room_categories,id',
            'floor_number' => 'nullable|integer|min:0',
            'status' => 'required|in:available,reserved,occupied,cleaning,maintenance',
            'note' => 'nullable|string',
        ]);

        $room->update($data);

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Cập nhật phòng thành công');
    }

    public function updateStatus(Request $request, Room $room)
    {
        $data = $request->validate([
            'status' => 'required|in:available,reserved,occupied,inspection,cleaning,maintenance',
        ]);

        $room->update([
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Cập nhật trạng thái phòng thành công.');
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Xóa phòng thành công');
    }
}