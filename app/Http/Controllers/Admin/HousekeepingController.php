<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class HousekeepingController extends Controller
{
    public function index()
    {
        $rooms = Room::with('category')
            ->where('status', 'cleaning')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->paginate(10);

        return view('admin.pages.housekeeping.index', compact('rooms'));
    }

    public function markAvailable(Room $room)
    {
        if ($room->status !== 'cleaning') {
            return back()->with('error', 'Chỉ có thể hoàn tất dọn phòng với phòng đang dọn dẹp.');
        }

        $room->update([
            'status' => 'available',
            'note' => null,
        ]);

        return back()->with('success', 'Đã xác nhận dọn xong. Phòng đã chuyển về trạng thái còn trống.');
    }
}