<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class StayingGuestController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['customer', 'bookingRooms.room', 'guests'])
            ->where('status', 'checked_in');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('cccd', 'like', "%{$search}%");
            })->orWhere('booking_code', 'like', "%{$search}%")
              ->orWhereHas('bookingRooms.room', function ($q) use ($search) {
                  $q->where('room_number', 'like', "%{$search}%");
              });
        }

        // Sắp xếp theo ngày dự kiến trả phòng gần nhất lên đầu
        $bookings = $query->orderBy('check_out_at', 'asc')->paginate(15)->withQueryString();

        return view('admin.pages.staying-guests.index', compact('bookings'));
    }
}
