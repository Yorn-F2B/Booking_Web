<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\BookingServiceItem;
use App\Events\BookingCreatedRealtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingController extends Controller
{
    private const ONLINE_CHECK_IN_TIME = Booking::STANDARD_CHECK_IN_TIME;
    private const ONLINE_CHECK_OUT_TIME = Booking::STANDARD_CHECK_OUT_TIME;
    private const ONLINE_CHECK_IN_LABEL = '14:00';
    private const EARLY_CHECK_IN_LABEL = '13:00';
    private const ONLINE_CHECK_OUT_LABEL = '12:00';

    public function confirm(Request $request)
    {
        if (!Auth::check()) {
            session([
                'url.intended' => route('bookings.confirm', $request->only([
                    'room_category_id',
                    'check_in_date',
                    'check_out_date',
                    'adult_count',
                    'child_count',
                    'note',
                ])),
            ]);

            return redirect()
                ->route('login')
                ->with('error', 'Vui lòng đăng nhập để tiếp tục đặt phòng.');
        }

        $minOnlineCheckInDate = $this->getOnlineMinCheckInDate();

        $data = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:' . $minOnlineCheckInDate,
            'check_out_date' => 'required|date|after:check_in_date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:1000',
        ], [
            'check_in_date.after_or_equal' => 'Đã quá mốc giữ phòng online hôm nay lúc ' . self::ONLINE_CHECK_IN_LABEL . '. Vui lòng chọn ngày nhận phòng từ ' . Carbon::parse($minOnlineCheckInDate)->format('d/m/Y') . '.',
            'check_out_date.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
        ]);

        $checkInAt = $data['check_in_date'] . ' ' . self::ONLINE_CHECK_IN_TIME;
        $checkOutAt = $data['check_out_date'] . ' ' . self::ONLINE_CHECK_OUT_TIME;

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
                $checkInAt,
                $checkOutAt
            )
        ) {
            return back()
                ->withInput()
                ->with('error', 'Bạn đã có một booking đang hoạt động trong khoảng thời gian này. Nếu cần đặt thêm phòng hoặc đặt cho khách đoàn, vui lòng liên hệ lễ tân hoặc hotline để được hỗ trợ.');
        }

        $availableRoom = $this->findAvailableRoom(
            $roomCategory->id,
            $checkInAt,
            $checkOutAt
        );

        if (!$availableRoom) {
            $checkInText = date('d/m/Y', strtotime($data['check_in_date']));
            $checkOutText = date('d/m/Y', strtotime($data['check_out_date']));

            return back()
                ->withInput()
                ->with('error', 'Hạng phòng này không còn phòng trống từ ngày '
                    . $checkInText
                    . ' đến ngày '
                    . $checkOutText
                    . '. Vui lòng chọn ngày khác hoặc hạng phòng khác.');
        }

        $nightCount = $this->getNightCount(
            $data['check_in_date'],
            $data['check_out_date']
        );

        $estimatedTotal = $roomCategory->price * $nightCount;

        $services = Service::where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('user.pages.booking-confirm', [
            'bookingData' => $data,
            'roomCategory' => $roomCategory,
            'customer' => $customer,
            'nightCount' => $nightCount,
            'estimatedTotal' => $estimatedTotal,
            'services' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $minOnlineCheckInDate = $this->getOnlineMinCheckInDate();

        $data = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:' . $minOnlineCheckInDate,
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

            'services' => 'nullable|array',
            'services.*.service_id' => 'nullable|exists:services,id',
            'services.*.quantity' => 'nullable|integer|min:1',
            'services.*.note' => 'nullable|string|max:1000',
        ], [
            'check_in_date.after_or_equal' => 'Đã quá mốc giữ phòng online hôm nay lúc ' . self::ONLINE_CHECK_IN_LABEL . '. Vui lòng chọn ngày nhận phòng từ ' . Carbon::parse($minOnlineCheckInDate)->format('d/m/Y') . '.',
            'check_out_date.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
        ]);

        $checkInAt = $data['check_in_date'] . ' ' . self::ONLINE_CHECK_IN_TIME;
        $checkOutAt = $data['check_out_date'] . ' ' . self::ONLINE_CHECK_OUT_TIME;

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

        $booking = DB::transaction(function () use ($data, $roomCategory, $checkInAt, $checkOutAt) {
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
                    $checkInAt,
                    $checkOutAt
                )
            ) {
                return 'active_booking_exists';
            }

            $availableRoom = $this->findAvailableRoom(
                $roomCategory->id,
                $checkInAt,
                $checkOutAt
            );

            if (!$availableRoom) {
                return null;
            }

            $nightCount = $this->getNightCount(
                $data['check_in_date'],
                $data['check_out_date']
            );

            $serviceItems = $this->prepareServiceItems($data['services'] ?? []);
            $serviceItemTotal = collect($serviceItems)->sum('total');
            $estimatedTotal = ($roomCategory->price * $nightCount) + $serviceItemTotal;

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $customer->id,
                'created_by' => null,
                'room_category_id' => $roomCategory->id,
                'booking_type' => 'overnight',
                'booking_mode' => 'advance',
                'booking_source' => 'user_online',
                'cleaning_buffer_minutes' => 60,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
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

            foreach ($serviceItems as $item) {
                BookingServiceItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $item['service_id'],
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'used_quantity' => $item['used_quantity'],
                    'billing_status' => $item['billing_status'],
                    'total' => $item['total'],
                    'note' => $item['note'],
                ]);
            }

            $availableRoom->update([
                'status' => 'reserved',
            ]);

            return $booking;
        });
        if ($booking === 'active_booking_exists') {
            return redirect()
                ->route('rooms', [
                    'check_in_date' => $data['check_in_date'],
                    'check_out_date' => $data['check_out_date'],
                    'adult_count' => $data['adult_count'],
                    'child_count' => $data['child_count'] ?? 0,
                    'room_category_id' => $data['room_category_id'],
                ])
                ->withInput()
                ->with('error', 'Bạn đã có một đơn đặt phòng đang hoạt động trong khoảng thời gian này. Nếu cần đặt thêm phòng hoặc đặt cho khách đoàn, vui lòng liên hệ lễ tân hoặc hotline để được hỗ trợ.');
        }

        if (!$booking) {
            $checkInText = date('d/m/Y', strtotime($data['check_in_date']));
            $checkOutText = date('d/m/Y', strtotime($data['check_out_date']));

            return back()
                ->withInput()
                ->with('error', 'Hạng phòng này không còn phòng trống từ ngày '
                    . $checkInText
                    . ' đến ngày '
                    . $checkOutText
                    . '. Phòng có thể vừa được người khác đặt, vui lòng chọn ngày khác.');
        }

        event(new BookingCreatedRealtime($booking));

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
            'serviceItems.service',
        ]);

        $availableServices = Service::where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service', 'minibar'])
            ->orderByRaw("FIELD(type, 'service', 'minibar')")
            ->orderBy('name')
            ->get();

        return view(
            'user.pages.booking-detail',
            compact('booking', 'availableServices')
        );
    }



    public function storeCustomerService(Request $request, Booking $booking)
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer || $booking->customer_id != $customer->id) {
            abort(403);
        }

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Đơn này đã kết thúc hoặc đã hủy nên không thể thêm dịch vụ.');
        }

        if (in_array($booking->payment_status, ['paid', 'refunded'])) {
            return back()->with('error', 'Đơn này đã thanh toán hoặc đã hoàn tiền nên không thể tự thêm dịch vụ. Vui lòng liên hệ lễ tân để được hỗ trợ.');
        }

        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'quantity' => 'required|integer|min:1|max:50',
            'note' => 'nullable|string|max:1000',
        ], [
            'service_id.required' => 'Vui lòng chọn dịch vụ.',
            'service_id.exists' => 'Dịch vụ không hợp lệ hoặc đã bị ẩn.',
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.integer' => 'Số lượng không hợp lệ.',
            'quantity.min' => 'Số lượng phải lớn hơn 0.',
            'quantity.max' => 'Số lượng quá lớn, vui lòng liên hệ lễ tân.',
        ]);

        $service = Service::where('id', $data['service_id'])
            ->where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service', 'minibar'])
            ->first();

        if (!$service) {
            return back()->with('error', 'Dịch vụ không hợp lệ hoặc đã bị ẩn.');
        }

        DB::beginTransaction();

        try {
            $quantity = max(1, (int) $data['quantity']);
            $unitPrice = (float) $service->price;

            $billingStatus = $service->type == 'minibar' ? 'pending' : 'confirmed';
            $usedQuantity = $service->type == 'minibar' ? 0 : $quantity;
            $totalAdded = $unitPrice * $usedQuantity;

            $customerNote = trim($data['note'] ?? '');
            $newNote = 'Khách tự thêm trên website';

            if ($customerNote !== '') {
                $newNote .= ': ' . $customerNote;
            }

            $existingItem = BookingServiceItem::where('booking_id', $booking->id)
                ->where('service_id', $service->id)
                ->whereIn('type', ['service', 'minibar'])
                ->first();

            if ($existingItem) {
                $existingItem->quantity = (int) $existingItem->quantity + $quantity;

                if ($existingItem->type == 'minibar') {
                    $existingItem->used_quantity = 0;
                    $existingItem->billing_status = 'pending';
                    $existingItem->total = 0;
                } else {
                    $existingItem->used_quantity = (int) $existingItem->used_quantity + $quantity;
                    $existingItem->billing_status = 'confirmed';
                    $existingItem->total = (float) $existingItem->total + $totalAdded;
                }

                $existingItem->note = trim(($existingItem->note ? $existingItem->note . "
" : '') . $newNote);
                $existingItem->save();
            } else {
                BookingServiceItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'type' => $service->type,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'used_quantity' => $usedQuantity,
                    'billing_status' => $billingStatus,
                    'total' => $totalAdded,
                    'note' => $newNote,
                ]);
            }

            if ($totalAdded > 0) {
                $booking->estimated_total = (float) $booking->estimated_total + $totalAdded;
                $booking->save();
            }

            DB::commit();

            if ($service->type == 'minibar') {
                return back()->with('success', 'Đã ghi nhận yêu cầu minibar. Khách sạn sẽ xác nhận số lượng thực dùng khi trả phòng.');
            }

            return back()->with('success', 'Đã thêm dịch vụ vào đơn phòng. Tổng tiền tạm tính đã được cập nhật.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi thêm dịch vụ: ' . $e->getMessage());
        }
    }

    private function prepareServiceItems(array $items): array
    {
        $preparedItems = [];
        $usedServiceIds = [];

        foreach ($items as $item) {
            if (empty($item['service_id'])) {
                continue;
            }

            $serviceId = (int) $item['service_id'];

            if (in_array($serviceId, $usedServiceIds)) {
                continue;
            }

            $service = Service::where('id', $serviceId)
                ->where('status', 'active')
                ->whereIn('type', ['service', 'minibar'])
                ->first();

            if (!$service) {
                continue;
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) $service->price;
            $billingStatus = $service->type == 'minibar' ? 'pending' : 'confirmed';
            $usedQuantity = $service->type == 'minibar' ? 0 : $quantity;
            $total = $unitPrice * $usedQuantity;

            $preparedItems[] = [
                'service_id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'used_quantity' => $usedQuantity,
                'billing_status' => $billingStatus,
                'total' => $total,
                'note' => $item['note'] ?? null,
            ];

            $usedServiceIds[] = $serviceId;
        }

        return $preparedItems;
    }

    private function findAvailableRoom($roomCategoryId, $checkInAt, $checkOutAt)
    {
        return Room::where('room_category_id', $roomCategoryId)
            ->availableForPeriod($checkInAt, $checkOutAt)
            ->inRandomOrder()
            ->first();
    }

    private function hasActiveBookingInDateRange($customerId, $checkInAt, $checkOutAt)
    {
        return Booking::where('customer_id', $customerId)
            ->whereIn('status', [
                'pending',
                'confirmed',
                'checked_in',
                'inspection_requested',
            ])
            ->where('check_in_at', '<', $checkOutAt)
            ->where('check_out_at', '>', $checkInAt)
            ->exists();
    }

    private function getNightCount($checkInDate, $checkOutDate)
    {
        return max(
            1,
            (strtotime($checkOutDate) - strtotime($checkInDate)) / 86400
        );
    }

    private function getOnlineMinCheckInDate(): string
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $todayCheckInDeadline = $now->copy()->setTimeFromTimeString(self::ONLINE_CHECK_IN_TIME);

        if ($now->greaterThanOrEqualTo($todayCheckInDeadline)) {
            return $now->copy()->addDay()->toDateString();
        }

        return $now->toDateString();
    }

    private function getOnlineMinCheckOutDate(?string $checkInDate = null): string
    {
        $baseCheckInDate = $checkInDate ?: $this->getOnlineMinCheckInDate();

        return Carbon::parse($baseCheckInDate, 'Asia/Ho_Chi_Minh')
            ->addDay()
            ->toDateString();
    }

    private function isOnlineBookingClosedToday(): bool
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        return $now->greaterThanOrEqualTo(
            $now->copy()->setTimeFromTimeString(self::ONLINE_CHECK_IN_TIME)
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