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
            ->whereIn('status', ['checked_in', 'inspection_requested']);

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
    public function show(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        abort_unless(in_array($booking->status, ['checked_in', 'inspection_requested'], true), 404);

        $booking->load([
            'customer',
            'roomCategory',
            'bookingRooms.room.category',
            'guests.bookingRoom.room.category',
            'guests.guardian.bookingRoom.room',
            'guests.roomHistories.fromBookingRoom.room',
            'guests.roomHistories.toBookingRoom.room',
            'staffAssignments.staff',
        ]);

        return view('admin.pages.staying-guests.show', compact('booking'));
    }

    private function guardCanAccessBooking(Booking $booking): void
    {
        $user = Auth::user();

        abort_unless($user && in_array($user->role, [
            'super_admin',
            'manager',
            'receptionist_lead',
            'receptionist',
        ], true), 403);
    }

}
