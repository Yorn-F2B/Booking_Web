<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with([
            'customer',
            'roomCategory',
            'bookingRooms.room',
        ]);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $bookings->where(function ($query) use ($keyword) {
                $query->where('booking_code', 'like', '%' . $keyword . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                        $customerQuery->where('first_name', 'like', '%' . $keyword . '%')
                            ->orWhere('last_name', 'like', '%' . $keyword . '%')
                            ->orWhere('phone', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $bookings->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $bookings->where('payment_status', $request->payment_status);
        }

        if ($request->filled('check_in_from')) {
            $bookings->whereDate('check_in_date', '>=', $request->check_in_from);
        }

        if ($request->filled('check_in_to')) {
            $bookings->whereDate('check_in_date', '<=', $request->check_in_to);
        }

        $bookings = $bookings
            ->latest()
            ->paginate(10);

        return view('admin.pages.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $booking->load([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'roomInspections.items',
        ]);

        $assignedRoomIds = $booking->bookingRooms
            ->pluck('room_id')
            ->toArray();

        $availableRooms = Room::where('room_category_id', $booking->room_category_id)
            ->where(function ($query) use ($assignedRoomIds) {
                $query->where('status', 'available');

                if (!empty($assignedRoomIds)) {
                    $query->orWhereIn('id', $assignedRoomIds);
                }
            })
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();
        $hasInspection = $booking->roomInspections->count() > 0;

        $allInspectionsConfirmed = $booking->roomInspections->count() > 0
            && $booking->roomInspections->where('status', '!=', 'confirmed')->count() == 0;

        $approvedDamageTotal = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->sum('total');

        return view('admin.pages.bookings.show', compact(
            'booking',
            'availableRooms',
            'assignedRoomIds',
            'hasInspection',
            'allInspectionsConfirmed',
            'approvedDamageTotal'
        ));
    }

    public function edit(Booking $booking)
    {
        return view('admin.pages.bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,checked_in,checked_out,cancelled',
            'payment_status' => 'required|in:unpaid,partial,paid,refunded',
            'note' => 'nullable|string|max:1000',
        ]);

        $booking->update($data);

        $booking->load('bookingRooms.room');

        foreach ($booking->bookingRooms as $bookingRoom) {
            if (!$bookingRoom->room) {
                continue;
            }

            if ($booking->status == 'confirmed') {
                $bookingRoom->room->update([
                    'status' => 'reserved',
                ]);
            }

            if ($booking->status == 'checked_in') {
                $bookingRoom->room->update([
                    'status' => 'occupied',
                ]);
            }

            if ($booking->status == 'checked_out') {
                $bookingRoom->room->update([
                    'status' => 'cleaning',
                ]);
            }

            if ($booking->status == 'cancelled') {
                $bookingRoom->room->update([
                    'status' => 'available',
                ]);
            }
        }

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('success', 'Cập nhật booking và trạng thái phòng thành công.');
    }

    public function destroy(Booking $booking)
    {
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return redirect()
                ->back()
                ->with('error', 'Chỉ có thể hủy booking đang chờ xác nhận hoặc đã xác nhận.');
        }

        DB::beginTransaction();

        try {
            $booking->load('bookingRooms.room');

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'available',
                    ]);
                }
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';

            $booking->update([
                'status' => 'cancelled',
                'note' => $oldNote . now()->format('d/m/Y H:i') . ' - Booking đã được hủy bởi nhân viên.',
            ]);

            DB::commit();

            return redirect()
                ->route('admin.bookings.index')
                ->with('success', 'Hủy booking thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Có lỗi khi hủy booking: ' . $e->getMessage());
        }
    }

}