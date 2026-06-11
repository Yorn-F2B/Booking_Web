<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingCreateController extends Controller
{
    public function create()
    {
        $roomCategories = RoomCategory::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.pages.bookings.create', compact('roomCategories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'required|string|max:20',
            'customer_cccd' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:150',
            'customer_address' => 'nullable|string|max:255',

            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'room_quantity' => 'required|integer|min:1',
            'prefer_adjacent_rooms' => 'nullable|boolean',

            'deposit_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ], [
            'customer_name.required' => 'Vui lòng nhập họ tên khách hàng.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại khách hàng.',
            'room_category_id.required' => 'Vui lòng chọn hạng phòng.',
            'check_in_date.required' => 'Vui lòng chọn ngày nhận phòng.',
            'check_out_date.required' => 'Vui lòng chọn ngày trả phòng.',
            'check_out_date.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
            'adult_count.required' => 'Vui lòng nhập số người lớn.',
            'room_quantity.required' => 'Vui lòng nhập số phòng.',
        ]);

        $roomCategory = RoomCategory::findOrFail($data['room_category_id']);

        $roomQuantity = (int) $data['room_quantity'];
        $preferAdjacentRooms = $request->boolean('prefer_adjacent_rooms') && $roomQuantity >= 2;

        $availableRooms = $preferAdjacentRooms
            ? $this->getAdjacentRooms(
                $data['room_category_id'],
                $roomQuantity,
                $data['check_in_date'],
                $data['check_out_date']
            )
            : $this->getAvailableRooms(
                $data['room_category_id'],
                $roomQuantity,
                $data['check_in_date'],
                $data['check_out_date']
            );

        $totalAvailableRooms = $this->countAvailableRooms(
            $data['room_category_id'],
            $data['check_in_date'],
            $data['check_out_date']
        );

        if ($availableRooms->count() < $roomQuantity) {
            if ($preferAdjacentRooms && $totalAvailableRooms >= $roomQuantity) {
                return back()
                    ->withInput()
                    ->with('error', 'Hiện còn đủ ' . $totalAvailableRooms . ' phòng trống, nhưng không có đủ ' . $roomQuantity . ' phòng cạnh nhau. Bạn có thể bỏ tùy chọn phòng cạnh nhau để tiếp tục.');
            }

            if ($totalAvailableRooms > 0) {
                return back()
                    ->withInput()
                    ->with('error', 'Hạng phòng này chỉ còn ' . $totalAvailableRooms . ' phòng trống trong thời gian đã chọn.');
            }

            return back()
                ->withInput()
                ->with('error', 'Hạng phòng này hiện không còn phòng trống trong thời gian đã chọn.');
        }

        $nightCount = max(
            1,
            (strtotime($data['check_out_date']) - strtotime($data['check_in_date'])) / 86400
        );

        $estimatedTotal = $roomCategory->price * $roomQuantity * $nightCount;
        $depositAmount = $data['deposit_amount'] ?? 0;

        if ($depositAmount > $estimatedTotal) {
            return back()
                ->withInput()
                ->with('error', 'Tiền cọc không được lớn hơn tổng tiền tạm tính.');
        }

        DB::beginTransaction();

        try {
            $customer = $this->createOrUpdateCustomer($data);

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $customer->id,
                'created_by' => Auth::id(),
                'room_category_id' => $roomCategory->id,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'adult_count' => $data['adult_count'],
                'child_count' => $data['child_count'] ?? 0,
                'room_quantity' => $roomQuantity,
                'prefer_adjacent_rooms' => $preferAdjacentRooms,
                'estimated_total' => $estimatedTotal,
                'deposit_amount' => $depositAmount,
                'payment_status' => $depositAmount > 0 ? 'partial' : 'unpaid',
                'status' => 'confirmed',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($availableRooms as $room) {
                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'adult_count' => 0,
                    'child_count' => 0,
                    'price_at_booking' => $roomCategory->price,
                    'surcharge' => 0,
                    'surcharge_reason' => null,
                    'created_at' => now(),
                ]);

                $room->update([
                    'status' => 'reserved',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.bookings.show', $booking->id)
                ->with('success', 'Tạo booking và gán phòng thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo booking: ' . $e->getMessage());
        }
    }

    private function createOrUpdateCustomer(array $data)
    {
        $nameParts = preg_split('/\s+/', trim($data['customer_name']));
        $firstName = array_pop($nameParts);
        $lastName = implode(' ', $nameParts);

        return Customer::updateOrCreate(
            [
                'phone' => $data['customer_phone'],
            ],
            [
                'first_name' => $firstName ?: $data['customer_name'],
                'last_name' => $lastName,
                'cccd' => $data['customer_cccd'] ?? null,
                'email' => $data['customer_email'] ?? null,
                'address' => $data['customer_address'] ?? null,
                'status' => 'active',
            ]
        );
    }

    private function generateBookingCode()
    {
        do {
            $code = 'BK' . now()->format('ymd') . strtoupper(Str::random(5));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    private function countAvailableRooms($roomCategoryId, $checkInDate, $checkOutDate)
    {
        return $this->availableRoomQuery($roomCategoryId, $checkInDate, $checkOutDate)->count();
    }

    private function getAvailableRooms($roomCategoryId, $quantity, $checkInDate, $checkOutDate)
    {
        return $this->availableRoomQuery($roomCategoryId, $checkInDate, $checkOutDate)
            ->inRandomOrder()
            ->take($quantity)
            ->get();
    }

    private function getAdjacentRooms($roomCategoryId, $quantity, $checkInDate, $checkOutDate)
    {
        $rooms = $this->availableRoomQuery($roomCategoryId, $checkInDate, $checkOutDate)
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get()
            ->groupBy('floor_number');

        $validGroups = collect();

        foreach ($rooms as $floorRooms) {
            $sortedRooms = $floorRooms
                ->sortBy(function ($room) {
                    return (int) preg_replace('/\D/', '', $room->room_number);
                })
                ->values();

            $sequence = collect();

            for ($i = 0; $i < $sortedRooms->count(); $i++) {
                if ($sequence->isEmpty()) {
                    $sequence->push($sortedRooms[$i]);
                } else {
                    $previousRoom = $sequence->last();

                    $previousNumber = (int) preg_replace('/\D/', '', $previousRoom->room_number);
                    $currentNumber = (int) preg_replace('/\D/', '', $sortedRooms[$i]->room_number);

                    if ($currentNumber == $previousNumber + 1) {
                        $sequence->push($sortedRooms[$i]);
                    } else {
                        $sequence = collect([$sortedRooms[$i]]);
                    }
                }

                if ($sequence->count() >= $quantity) {
                    $validGroups->push($sequence->take($quantity)->values());
                }
            }
        }

        if ($validGroups->isEmpty()) {
            return collect();
        }

        return $validGroups->random();
    }

    private function availableRoomQuery($roomCategoryId, $checkInDate, $checkOutDate)
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
            });
    }

}