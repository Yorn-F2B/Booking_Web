<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StayingGuestController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::query()
            ->with([
                'customer',
                'bookingRooms.room',
                'guests.bookingRoom.room',
            ])
            ->where('status', 'checked_in');

        $user = Auth::user();

        if ($user?->role === 'receptionist') {
            $query->where(function ($bookingQuery) use ($user) {
                $bookingQuery
                    ->where('created_by', $user->id)
                    ->orWhereHas('staffAssignments', function ($assignmentQuery) use ($user) {
                        $assignmentQuery
                            ->where('staff_id', $user->id)
                            ->where('status', 'active');
                    });
            });
        }

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('cccd', 'like', "%{$search}%");
                    })
                    ->orWhereHas('bookingRooms.room', function ($roomQuery) use ($search) {
                        $roomQuery->where('room_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('guests', function ($guestQuery) use ($search) {
                        $guestQuery
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('cccd', 'like', "%{$search}%");
                    });
            });
        }

        $bookings = $query
            ->orderBy('check_out_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pages.staying-guests.index', compact('bookings'));
    }
}
