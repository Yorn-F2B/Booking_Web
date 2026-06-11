<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function confirm(Request $request)
    {
        $data = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        $roomCategory = RoomCategory::where('status', 'active')
            ->findOrFail($data['room_category_id']);

        if ($data['adult_count'] > $roomCategory->adult_capacity) {
            return back()
                ->withInput()
                ->with('error', 'Số người lớn vượt quá sức chứa của hạng phòng.');
        }

        if (($data['child_count'] ?? 0) > $roomCategory->child_capacity) {
            return back()
                ->withInput()
                ->with('error', 'Số trẻ em vượt quá sức chứa của hạng phòng.');
        }

        $customer = Customer::where('user_id', Auth::id())->first();

        if (
            $customer && $this->hasActiveBookingInDateRange(
                $customer->id,
                $data['check_in_date'],
                $data['check_out_date']
            )
        ) {
            return back()
                ->withInput()
                ->with('error', 'Bạn đã có một booking đang hoạt động trong khoảng thời gian này. Nếu cần đặt thêm phòng hoặc đặt cho khách đoàn, vui lòng liên hệ lễ tân hoặc hotline để được hỗ trợ.');
        }

        $availableRoom = $this->findAvailableRoom(
            $roomCategory->id,
            $data['check_in_date'],
            $data['check_out_date']
        );

        if (!$availableRoom) {
            return back()
                ->withInput()
                ->with('error', 'Không còn phòng trống phù hợp trong thời gian bạn chọn.');
        }

        $nightCount = $this->getNightCount(
            $data['check_in_date'],
            $data['check_out_date']
        );

        $estimatedTotal = $roomCategory->price * $nightCount;

        return view('user.pages.booking-confirm', [
            'bookingData' => $data,
            'roomCategory' => $roomCategory,
            'customer' => $customer,
            'nightCount' => $nightCount,
            'estimatedTotal' => $estimatedTotal,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'nullable|integer|min:0',

            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'cccd' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',

            'note' => 'nullable|string|max:1000',
        ]);

        $roomCategory = RoomCategory::where('status', 'active')
            ->findOrFail($data['room_category_id']);

        if ($data['adult_count'] > $roomCategory->adult_capacity) {
            return back()
                ->withInput()
                ->with('error', 'Số người lớn vượt quá sức chứa của hạng phòng.');
        }

        if (($data['child_count'] ?? 0) > $roomCategory->child_capacity) {
            return back()
                ->withInput()
                ->with('error', 'Số trẻ em vượt quá sức chứa của hạng phòng.');
        }

        $booking = DB::transaction(function () use ($data, $roomCategory) {
            $customer = Customer::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                ],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'cccd' => $data['cccd'] ?? null,
                    'email' => $data['email'] ?? Auth::user()->email,
                    'address' => $data['address'] ?? null,
                    'status' => 'active',
                ]
            );

            if (
                $this->hasActiveBookingInDateRange(
                    $customer->id,
                    $data['check_in_date'],
                    $data['check_out_date']
                )
            ) {
                return 'active_booking_exists';
            }

            $availableRoom = $this->findAvailableRoom(
                $roomCategory->id,
                $data['check_in_date'],
                $data['check_out_date']
            );

            if (!$availableRoom) {
                return null;
            }

            $nightCount = $this->getNightCount(
                $data['check_in_date'],
                $data['check_out_date']
            );

            $estimatedTotal = $roomCategory->price * $nightCount;

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $customer->id,
                'created_by' => null,
                'room_category_id' => $roomCategory->id,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'adult_count' => $data['adult_count'],
                'child_count' => $data['child_count'] ?? 0,
                'room_quantity' => 1,
                'prefer_adjacent_rooms' => 0,
                'estimated_total' => $estimatedTotal,
                'deposit_amount' => 0,
                'payment_status' => 'unpaid',
                'status' => 'confirmed',
                'note' => $data['note'] ?? null,
            ]);

            BookingRoom::create([
                'booking_id' => $booking->id,
                'room_id' => $availableRoom->id,
                'adult_count' => $data['adult_count'],
                'child_count' => $data['child_count'] ?? 0,
                'price_at_booking' => $roomCategory->price,
                'surcharge' => 0,
                'surcharge_reason' => null,
                'created_at' => now(),
            ]);

            $availableRoom->update([
                'status' => 'reserved',
            ]);

            return $booking;
        });

        if ($booking === 'active_booking_exists') {
            return redirect()
                ->route('rooms.show', $data['room_category_id'])
                ->withInput()
                ->with('error', 'Bạn đã có một don dat phong đang hoạt động trong khoảng thời gian này. Nếu cần đặt thêm phòng hoặc đặt cho khách đoàn, vui lòng liên hệ lễ tân hoặc hotline để được hỗ trợ.');
        }

        if (!$booking) {
            return back()
                ->withInput()
                ->with('error', 'Phòng vừa được người khác đặt. Vui lòng chọn lại thời gian khác.');
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Đặt phòng thành công. Booking của bạn đã được tạo.');
    }

    public function cancel(Booking $booking)
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer || $booking->customer_id != $customer->id) {
            abort(403);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return redirect()
                ->back()
                ->with('error', 'Không thể hủy đơn này vì đơn đã được xử lý.');
        }

        $booking->update([
            'status' => 'cancelled',
        ]);

        foreach ($booking->bookingRooms as $bookingRoom) {
            if ($bookingRoom->room) {
                $bookingRoom->room->update([
                    'status' => 'available',
                ]);
            }
        }

        return redirect()
            ->back()
            ->with('success', 'Hủy đơn đặt phòng thành công.');
    }

    public function show(Booking $booking)
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer || $booking->customer_id != $customer->id) {
            abort(403);
        }

        $booking->load([
            'roomCategory',
            'bookingRooms.room',
        ]);

        return view(
            'user.pages.booking-detail',
            compact('booking')
        );
    }

    private function findAvailableRoom($roomCategoryId, $checkInDate, $checkOutDate)
    {
        return Room::where('room_category_id', $roomCategoryId)
            ->where('status', 'available')
            ->whereDoesntHave('bookingRooms.booking', function ($query) use ($checkInDate, $checkOutDate) {
                $query->whereIn('status', [
                    'pending',
                    'confirmed',
                    'checked_in',
                ])
                    ->whereDate('check_in_date', '<', $checkOutDate)
                    ->whereDate('check_out_date', '>', $checkInDate);
            })
            ->inRandomOrder()
            ->first();
    }

    private function hasActiveBookingInDateRange($customerId, $checkInDate, $checkOutDate)
    {
        return Booking::where('customer_id', $customerId)
            ->whereIn('status', [
                'pending',
                'confirmed',
                'checked_in',
            ])
            ->whereDate('check_in_date', '<', $checkOutDate)
            ->whereDate('check_out_date', '>', $checkInDate)
            ->exists();
    }

    private function getNightCount($checkInDate, $checkOutDate)
    {
        return max(
            1,
            (strtotime($checkOutDate) - strtotime($checkInDate)) / 86400
        );
    }

    private function generateBookingCode()
    {
        do {
            $code = 'BK' . now()->format('YmdHis') . strtoupper(Str::random(3));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}