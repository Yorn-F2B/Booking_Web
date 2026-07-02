<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BookingLog;
use Illuminate\Support\Facades\Auth;
use App\Models\Service;
use App\Models\BookingServiceItem;
use App\Models\Promotion;
use App\Services\PromotionService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Support\Realtime;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'activeStaffAssignments.staff.staff',
        ]);

        if ($this->currentUserIsReceptionist()) {
            $bookings->where(function ($query) {
                $query->where('created_by', Auth::id())
                    ->orWhereHas('activeStaffAssignments', function ($assignmentQuery) {
                        $assignmentQuery->where('staff_id', Auth::id());
                    });
            });
        }

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

        if ($request->filled('payment_status') && in_array($request->payment_status, ['unpaid', 'partial', 'paid'], true)) {
            $bookings->where('payment_status', $request->payment_status);
        }

        if ($request->filled('filter_date') || $request->filled('date_from')) {
            $tz = 'Asia/Ho_Chi_Minh';

            // Support both old single-date filter and new date-range filter
            if ($request->filled('date_from')) {
                $dateFrom = $request->date_from;
                $dateTo   = $request->filled('date_to') ? $request->date_to : $dateFrom;
            } else {
                $dateFrom = $request->filter_date;
                $dateTo   = $dateFrom;
            }

            $timeFrom = $request->input('time_from', $request->input('filter_time_from', '00:00')) ?: '00:00';
            $timeTo   = $request->input('time_to',   $request->input('filter_time_to',   '23:59')) ?: '23:59';

            $filterStart = Carbon::parse($dateFrom . ' ' . $timeFrom . ':00', $tz);
            $filterEnd   = Carbon::parse($dateTo   . ' ' . $timeTo   . ':59', $tz);

            // If same day and end <= start, treat as overnight wrap
            if ($filterEnd->lessThanOrEqualTo($filterStart)) {
                $filterEnd->addDay();
            }

            $bookings->where('check_in_at', '<', $filterEnd)
                ->where('check_out_at', '>', $filterStart);
        }

        $bookings = $bookings
            ->latest()
            ->paginate(10);

        return view('admin.pages.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        $booking->load([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'roomInspections.items',
            'serviceItems.service',
            'bookingPromotions.user',
            'bookingPromotions.serviceOffers',
            'bookingPromotions.roomUpgradeOffers',
            'promotionServiceOffers',
            'promotionRoomUpgrades',
            'logs.user',
            'payments',
        ]);

        $assignedRoomIds = $booking->bookingRooms
            ->pluck('room_id')
            ->toArray();

        $availableRooms = Room::where('room_category_id', $booking->room_category_id)
            ->whereNotIn('id', $assignedRoomIds)
            ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();
        $hasInspection = $booking->roomInspections->count() > 0;

        $allInspectionsConfirmed = $booking->roomInspections->count() > 0
            && $booking->roomInspections->where('status', '!=', 'confirmed')->count() == 0;

        $approvedInspectionTotal = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->sum('total');

        $approvedDamageTotal = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->where('type', 'damage_fee')
            ->sum('total');

        $approvedMinibarTotal = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->where('type', 'minibar')
            ->sum('total');

        $serviceItemTotal = $booking->serviceItems
            ->where('billing_status', 'confirmed')
            ->sum('total');
        $availableServices = Service::where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service', 'minibar'])
            ->orderByRaw("FIELD(service_group, 'vehicle', 'food_drink', 'transport', 'laundry', 'wellness', 'room_support', 'general', 'other')")
            ->orderByRaw("FIELD(type, 'service', 'minibar')")
            ->orderBy('name')
            ->get();

        $nightCount = max(
            1,
            Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh')
                ->diffInDays(Carbon::parse($booking->check_out_date, 'Asia/Ho_Chi_Minh'))
        );

        $promotionSubtotal = (float) ($booking->subtotal_amount ?? 0);

        if ($promotionSubtotal <= 0) {
            $promotionSubtotal = (float) $booking->estimated_total + (float) ($booking->discount_amount ?? 0);
        }

        $availablePromotions = app(PromotionService::class)->availablePromotions([
            'customer_id' => $booking->customer_id,
            'subtotal_amount' => $promotionSubtotal,
            'check_in_at' => $booking->check_in_at,
            'check_out_at' => $booking->check_out_at,
            'night_count' => $nightCount,
            'room_quantity' => $booking->room_quantity,
        ], 'admin')->reject(function ($promotion) use ($booking) {
            return $booking->bookingPromotions
                ->pluck('code_snapshot')
                ->contains($promotion->code);
        })->values();

        return view('admin.pages.bookings.show', compact(
            'booking',
            'availableRooms',
            'assignedRoomIds',
            'hasInspection',
            'allInspectionsConfirmed',
            'approvedDamageTotal',
            'serviceItemTotal',
            'availableServices',
            'approvedInspectionTotal',
            'approvedMinibarTotal',
            'availablePromotions',
        ));
    }

    public function applyPromotions(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ có thể thêm mã cho booking đang chờ, đã xác nhận hoặc đang ở.');
        }

        if ($booking->payment_status === 'paid') {
            return back()->with('error', 'Booking đã thanh toán đủ nên không thể áp thêm mã giảm giá.');
        }

        $data = $request->validate([
            'promotion_codes' => 'required|array|min:1',
            'promotion_codes.*' => 'required|string|max:50',
            'promotion_note' => 'nullable|string|max:1000',
        ], [
            'promotion_codes.required' => 'Vui lòng chọn ít nhất một mã ưu đãi.',
            'promotion_codes.min' => 'Vui lòng chọn ít nhất một mã ưu đãi.',
        ]);

        DB::beginTransaction();

        try {
            $booking->load(['bookingPromotions', 'serviceItems']);

            $selectedCodes = collect($data['promotion_codes'])
                ->filter()
                ->map(fn ($code) => strtoupper(trim((string) $code)))
                ->unique()
                ->values();

            $existingCodes = $booking->bookingPromotions
                ->pluck('code_snapshot')
                ->map(fn ($code) => strtoupper(trim((string) $code)))
                ->values();

            $duplicatedCodes = $selectedCodes->intersect($existingCodes);

            if ($duplicatedCodes->isNotEmpty()) {
                throw new \Exception('Booking đã áp dụng mã: ' . $duplicatedCodes->implode(', ') . '.');
            }

            $nightCount = max(
                1,
                Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh')
                    ->diffInDays(Carbon::parse($booking->check_out_date, 'Asia/Ho_Chi_Minh'))
            );

            $currentDiscount = (float) ($booking->discount_amount ?? 0);
            $subtotalAmount = (float) ($booking->subtotal_amount ?? 0);

            if ($subtotalAmount <= 0) {
                $subtotalAmount = (float) $booking->estimated_total + $currentDiscount;
            }

            $promotionServiceItems = $booking->serviceItems
                ->filter(function ($item) {
                    return !in_array($item->billing_status, ['unused', 'cancelled']);
                })
                ->map(function ($item) {
                    return [
                        'service_id' => $item->service_id,
                        'name' => $item->name,
                        'type' => $item->type,
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => (int) $item->quantity,
                        'used_quantity' => (int) $item->used_quantity,
                        'billing_status' => $item->billing_status,
                        'total' => (float) $item->total,
                        'note' => $item->note,
                    ];
                })
                ->values()
                ->all();

            $promotionResult = app(PromotionService::class)->validateCodes(
                $selectedCodes->all(),
                [
                    'customer_id' => $booking->customer_id,
                    'subtotal_amount' => $subtotalAmount,
                    'service_items' => $promotionServiceItems,
                    'check_in_at' => $booking->check_in_at,
                    'check_out_at' => $booking->check_out_at,
                    'night_count' => $nightCount,
                    'room_quantity' => $booking->room_quantity,
                ],
                'admin',
                $data['promotion_note'] ?? null
            );

            if (!$promotionResult['ok']) {
                throw new \Exception(implode(' ', $promotionResult['messages']));
            }

            foreach (($promotionResult['auto_service_items'] ?? []) as $autoServiceItem) {
                $this->upsertPromotionServiceItem($booking, $autoServiceItem);
            }

            $subtotalAmount = (float) $promotionResult['subtotal_amount'];
            $addedMoneyDiscount = (float) ($promotionResult['money_discount_total'] ?? 0);
            $addedServiceDiscount = (float) ($promotionResult['service_discount_total'] ?? 0);
            $addedDiscount = (float) $promotionResult['discount_total'];
            $newDiscount = $currentDiscount + $addedDiscount;
            $newTotal = max(0, $subtotalAmount - $newDiscount);

            if ((float) $booking->deposit_amount > $newTotal) {
                throw new \Exception('Không thể áp mã vì tiền cọc hiện tại lớn hơn tổng tiền sau giảm.');
            }

            $booking->update([
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $newDiscount,
                'estimated_total' => $newTotal,
            ]);

            app(PromotionService::class)->storeUsages(
                $booking,
                $promotionResult['promotions'],
                'admin',
                $data['promotion_note'] ?? null,
                Auth::id()
            );

            $this->addBookingLog(
                $booking,
                'promotion_added',
                'Áp dụng mã ưu đãi sau khi tạo booking: '
                . $selectedCodes->implode(', ')
                . '. Giảm tiền thêm: '
                . number_format($addedMoneyDiscount, 0, ',', '.')
                . 'đ, ưu đãi dịch vụ thêm: '
                . number_format($addedServiceDiscount, 0, ',', '.')
                . 'đ, tổng ưu đãi thêm: '
                . number_format($addedDiscount, 0, ',', '.')
                . 'đ.'
                . (!empty($data['promotion_note']) ? ' Lý do: ' . $data['promotion_note'] : '')
            );

            DB::commit();

            return back()->with('success', 'Đã áp dụng mã ưu đãi vào booking.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể áp mã: ' . $e->getMessage());
        }
    }

    private function upsertPromotionServiceItem(Booking $booking, array $item): void
    {
        if (empty($item['service_id'])) {
            return;
        }

        $existingItem = BookingServiceItem::where('booking_id', $booking->id)
            ->where('service_id', $item['service_id'])
            ->where('billing_status', 'confirmed')
            ->first();

        if ($existingItem) {
            $existingItem->quantity = (int) $existingItem->quantity + (int) $item['quantity'];
            $existingItem->used_quantity = (int) $existingItem->used_quantity + (int) $item['used_quantity'];
            $existingItem->total = (float) $existingItem->total + (float) $item['total'];

            $extraNote = trim((string) ($item['note'] ?? ''));
            if ($extraNote !== '') {
                $existingItem->note = trim((string) ($existingItem->note ?? '')) !== ''
                    ? $existingItem->note . '; ' . $extraNote
                    : $extraNote;
            }

            $existingItem->save();
            return;
        }

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

    public function edit(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        return view('admin.pages.bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,checked_in,inspection_requested,checked_out,completed',
            'payment_status' => 'required|in:unpaid,partial,paid',
            'note' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $booking->status;
        $oldPaymentStatus = $booking->payment_status;

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
        }

        $changes = [];

        if ($oldStatus !== $booking->status) {
            $changes[] = 'trạng thái booking từ ' . $oldStatus . ' sang ' . $booking->status;
        }

        if ($oldPaymentStatus !== $booking->payment_status) {
            $changes[] = 'thanh toán từ ' . $oldPaymentStatus . ' sang ' . $booking->payment_status;
        }

        if (!empty($changes)) {
            $this->addBookingLog(
                $booking,
                'booking_update',
                'Cập nhật nhanh: ' . implode(', ', $changes) . '.'
            );
        }

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('success', 'Cập nhật booking và trạng thái phòng thành công.');
    }

    public function destroy(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        return back()->with(
            'error',
            'Admin không được hủy booking thường. Chỉ được xử lý hủy no-show từ trang chi tiết khi khách quá giờ check-in theo chính sách.'
        );
    }

    public function storeServiceItem(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Booking đã kết thúc hoặc đã hủy nên không thể thêm dịch vụ.');
        }

        $data = $request->validate([
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.quantity' => 'required|integer|min:1',
            'services.*.note' => 'nullable|string|max:1000',
        ], [
            'services.required' => 'Vui lòng thêm ít nhất một dịch vụ.',
            'services.*.service_id.required' => 'Vui lòng chọn dịch vụ.',
            'services.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'services.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
        ]);

        DB::beginTransaction();

        try {
            $totalAdded = 0;
            $logMessages = [];

            foreach ($data['services'] as $serviceRow) {
                $service = Service::where('id', $serviceRow['service_id'])
                    ->where('status', 'active')
                    ->where('price', '>', 0)
                    ->whereIn('type', ['service', 'minibar'])
                    ->first();

                if (!$service) {
                    throw new \Exception('Có dịch vụ không hợp lệ hoặc đã bị ẩn.');
                }

                $quantity = max(1, (int) $serviceRow['quantity']);
                $unitPrice = (float) $service->price;

                $billingStatus = 'confirmed';
                $usedQuantity = $quantity;
                $total = $unitPrice * $quantity;

                $existingItem = BookingServiceItem::where('booking_id', $booking->id)
                    ->where('service_id', $service->id)
                    ->whereIn('type', ['service', 'minibar'])
                    ->first();

                if ($existingItem) {
                    $existingItem->quantity += $quantity;
                    $existingItem->used_quantity += $quantity;
                    $existingItem->billing_status = 'confirmed';
                    $existingItem->total += $total;

                    if (!empty($serviceRow['note'])) {
                        $existingItem->note = trim(($existingItem->note ? $existingItem->note . "\n" : '') . $serviceRow['note']);
                    }

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
                        'confirmed_by' => Auth::id(),
                        'confirmed_at' => now(),
                        'confirm_note' => 'Dịch vụ/minibar khách gọi thêm, tính tiền ngay vào booking.',
                        'total' => $total,
                        'note' => $serviceRow['note'] ?? null,
                    ]);
                }

                $totalAdded += $total;

                $logMessages[] = $service->name
                    . ' x ' . $quantity
                    . ' = ' . number_format($total, 0, ',', '.') . 'đ';
            }

            if ($totalAdded > 0) {
                $oldSubtotal = (float) ($booking->subtotal_amount ?? 0);

                if ($oldSubtotal <= 0) {
                    $oldSubtotal = (float) $booking->estimated_total + (float) ($booking->discount_amount ?? 0);
                }

                $booking->subtotal_amount = $oldSubtotal + $totalAdded;
                $booking->estimated_total += $totalAdded;
                $booking->save();
            }

            $this->addBookingLog(
                $booking,
                'service_added',
                'Thêm dịch vụ/minibar vào booking: ' . implode('; ', $logMessages)
                . '. Tổng cộng thêm: ' . number_format($totalAdded, 0, ',', '.') . 'đ.'
            );

            DB::commit();

            return back()->with('success', 'Đã thêm dịch vụ/minibar vào booking và cộng tiền ngay vào đơn.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi thêm dịch vụ: ' . $e->getMessage());
        }
    }

    public function updateServiceItem(Request $request, Booking $booking, BookingServiceItem $bookingServiceItem)
    {
        $this->guardCanAccessBooking($booking);

        if ($bookingServiceItem->booking_id != $booking->id) {
            abort(404);
        }

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Booking đã kết thúc hoặc đã hủy nên không thể sửa dịch vụ.');
        }

        if (!in_array($bookingServiceItem->type, ['service', 'minibar'])) {
            return back()->with('error', 'Chỉ được sửa số lượng dịch vụ hoặc minibar.');
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $oldTotal = (float) $bookingServiceItem->total;
            $oldQuantity = (int) $bookingServiceItem->quantity;

            $newQuantity = (int) $data['quantity'];

            $usedQuantity = $newQuantity;
            $newTotal = (float) $bookingServiceItem->unit_price * $newQuantity;

            $difference = $newTotal - $oldTotal;

            $bookingServiceItem->update([
                'quantity' => $newQuantity,
                'used_quantity' => $usedQuantity,
                'billing_status' => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'confirm_note' => 'Dịch vụ/minibar khách gọi thêm, tính tiền ngay vào booking.',
                'total' => $newTotal,
            ]);

            if ($difference != 0) {
                $oldSubtotal = (float) ($booking->subtotal_amount ?? 0);

                if ($oldSubtotal <= 0) {
                    $oldSubtotal = (float) $booking->estimated_total + (float) ($booking->discount_amount ?? 0);
                }

                $booking->subtotal_amount = max(0, $oldSubtotal + $difference);
                $booking->estimated_total = max(0, (float) $booking->estimated_total + $difference);
                $booking->save();
            }

            $this->addBookingLog(
                $booking,
                'service_quantity_updated',
                'Cập nhật số lượng "' . $bookingServiceItem->name . '" từ ' . $oldQuantity . ' sang ' . $newQuantity . '. Chênh lệch: ' . number_format($difference, 0, ',', '.') . 'đ.'
            );

            DB::commit();

            return back()->with('success', 'Cập nhật số lượng dịch vụ thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi cập nhật số lượng dịch vụ: ' . $e->getMessage());
        }
    }

    public function destroyServiceItem(Booking $booking, BookingServiceItem $bookingServiceItem)
    {
        $this->guardCanAccessBooking($booking);

        if ($bookingServiceItem->booking_id != $booking->id) {
            abort(404);
        }

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Booking đã kết thúc hoặc đã hủy nên không thể xóa dịch vụ.');
        }

        DB::beginTransaction();

        try {
            $total = (float) $bookingServiceItem->total;
            $serviceName = $bookingServiceItem->name;

            $bookingServiceItem->delete();

            $oldSubtotal = (float) ($booking->subtotal_amount ?? 0);

            if ($oldSubtotal <= 0) {
                $oldSubtotal = (float) $booking->estimated_total + (float) ($booking->discount_amount ?? 0);
            }

            $booking->subtotal_amount = max(0, $oldSubtotal - $total);
            $booking->estimated_total = max(0, (float) $booking->estimated_total - $total);
            $booking->save();

            $this->addBookingLog(
                $booking,
                'service_removed',
                'Xóa dịch vụ "' . $serviceName . '". Trừ khỏi tổng tiền: ' . number_format($total, 0, ',', '.') . 'đ.'
            );

            DB::commit();

            return back()->with('success', 'Đã xóa dịch vụ khỏi booking.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi xóa dịch vụ: ' . $e->getMessage());
        }
    }

    public function updatePaymentStatus(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->payment_status === 'paid') {
            return back()->with('error', 'Booking đã thanh toán nên không thể đổi trạng thái thanh toán.');
        }

        $data = $request->validate([
            'payment_status' => 'required|in:unpaid,partial,paid',
        ]);

        $oldPaymentStatus = $booking->payment_status;

        if ($oldPaymentStatus == 'partial' && $data['payment_status'] == 'unpaid') {
            return back()->with('error', 'Booking đã có thanh toán một phần nên không thể chuyển về chưa thanh toán.');
        }

        $booking->update([
            'payment_status' => $data['payment_status'],
        ]);

        if ($oldPaymentStatus !== $booking->payment_status) {
            $this->addBookingLog(
                $booking,
                'payment_update',
                'Cập nhật thanh toán từ ' . $oldPaymentStatus . ' sang ' . $booking->payment_status . '.'
            );
        }

        return back()->with('success', 'Cập nhật trạng thái thanh toán thành công.');
    }

    public function recordPayment(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->payment_status === 'paid') {
            return back()->with('error', 'Booking đã thanh toán đủ nên không thể thu thêm.');
        }

        if (in_array($booking->status, ['canceled', 'cancelled', 'no_show'], true)) {
            return back()->with('error', 'Booking đã hủy/no-show nên không thể ghi nhận thanh toán.');
        }

        $data = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer',
            'payment_type' => 'required|in:deposit_30,full_100,custom',
            'amount' => 'nullable|numeric|min:1000',
            'payment_note' => 'nullable|string|max:1000',
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thu tiền.',
            'payment_method.in' => 'Phương thức thu tiền không hợp lệ.',
            'payment_type.required' => 'Vui lòng chọn kiểu thu tiền.',
            'payment_type.in' => 'Kiểu thu tiền không hợp lệ.',
            'amount.min' => 'Số tiền thu tối thiểu là 1.000đ.',
        ]);

        DB::beginTransaction();

        try {
            $booking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $payableTotal = $this->calculateAdminPayableTotal($booking);
            $currentPaid = (float) $booking->deposit_amount;
            $remaining = max(0, $payableTotal - $currentPaid);

            if ($remaining <= 0) {
                $booking->update([
                    'payment_status' => 'paid',
                ]);

                DB::commit();

                return back()->with('success', 'Booking đã đủ số tiền cần thu.');
            }

            $amount = $this->resolveAdminPaymentAmount(
                $data['payment_type'],
                (float) ($data['amount'] ?? 0),
                $payableTotal,
                $currentPaid
            );

            if ($amount <= 0) {
                throw new \Exception('Số tiền thu không hợp lệ.');
            }

            if ($amount > $remaining) {
                throw new \Exception('Số tiền thu không được lớn hơn số còn lại: ' . number_format($remaining, 0, ',', '.') . 'đ.');
            }

            $newPaidAmount = $currentPaid + $amount;
            $newPaymentStatus = $newPaidAmount >= $payableTotal ? 'paid' : 'partial';
            $storedPaymentType = $amount >= $remaining ? 'full_100' : 'deposit_30';

            $payment = BookingPayment::create([
                'booking_id' => $booking->id,
                'provider' => $data['payment_method'],
                'txn_ref' => $this->generateAdminPaymentTxnRef($booking, $data['payment_method']),
                'amount' => $amount,
                'status' => 'success',
                'payment_type' => $storedPaymentType,
                'paid_at' => now('Asia/Ho_Chi_Minh'),
                'raw_response' => [
                    'source' => 'admin',
                    'method' => $data['payment_method'],
                    'type' => $data['payment_type'],
                    'note' => $data['payment_note'] ?? null,
                    'staff_id' => Auth::id(),
                ],
            ]);

            $oldPaymentStatus = $booking->payment_status;

            $booking->update([
                'deposit_amount' => $newPaidAmount,
                'payment_status' => $newPaymentStatus,
            ]);

            $methodLabel = $data['payment_method'] === 'cash'
                ? 'tiền mặt tại quầy'
                : 'chuyển khoản tại quầy';

            $this->addBookingLog(
                $booking,
                'admin_payment_received',
                'Ghi nhận thanh toán ' . $methodLabel . ': '
                . number_format($amount, 0, ',', '.')
                . 'đ. Đã thu: '
                . number_format($newPaidAmount, 0, ',', '.')
                . 'đ / '
                . number_format($payableTotal, 0, ',', '.')
                . 'đ. Trạng thái thanh toán: '
                . $oldPaymentStatus
                . ' → '
                . $newPaymentStatus
                . '. Mã giao dịch: '
                . $payment->txn_ref
                . (!empty($data['payment_note']) ? '. Ghi chú: ' . $data['payment_note'] : '')
            );

            DB::commit();

            Realtime::booking($booking->id, 'payment_updated');

            return back()->with('success', 'Đã ghi nhận thanh toán ' . number_format($amount, 0, ',', '.') . 'đ.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể ghi nhận thanh toán: ' . $e->getMessage());
        }
    }

    private function calculateAdminPayableTotal(Booking $booking): float
    {
        $booking->loadMissing([
            'serviceItems',
            'roomInspections.items',
        ]);

        $estimatedTotal = (float) $booking->estimated_total;
        $subtotalTotal = max(
            0,
            (float) ($booking->subtotal_amount ?? 0) - (float) ($booking->discount_amount ?? 0)
        );

        $approvedInspectionTotal = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->sum('total');

        return max(0, max($estimatedTotal, $subtotalTotal) + (float) $approvedInspectionTotal);
    }

    private function resolveAdminPaymentAmount(
        string $paymentType,
        float $customAmount,
        float $payableTotal,
        float $currentPaid
    ): float {
        $remaining = max(0, $payableTotal - $currentPaid);

        if ($paymentType === 'deposit_30') {
            $depositTarget = round($payableTotal * 0.3, 0);

            return max(0, min($depositTarget - $currentPaid, $remaining));
        }

        if ($paymentType === 'full_100') {
            return $remaining;
        }

        return min($customAmount, $remaining);
    }

    private function generateAdminPaymentTxnRef(Booking $booking, string $method): string
    {
        $prefix = $method === 'cash' ? 'CASH' : 'BANK';

        do {
            $txnRef = $prefix
                . $booking->booking_code
                . now('Asia/Ho_Chi_Minh')->format('YmdHis')
                . strtoupper(Str::random(5));

            $txnRef = preg_replace('/[^A-Za-z0-9]/', '', $txnRef);
        } while (BookingPayment::where('txn_ref', $txnRef)->exists());

        return $txnRef;
    }

    public function updateNote(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $booking->update([
            'note' => $data['note'],
        ]);

        $this->addBookingLog(
            $booking,
            'note_update',
            'Cập nhật ghi chú nội bộ cho booking.'
        );

        return back()->with('success', 'Cập nhật ghi chú nội bộ thành công.');
    }

    private function currentUserIsReceptionist(): bool
    {
        return Auth::check() && Auth::user()->role === 'receptionist';
    }

    private function guardCanAccessBooking(Booking $booking): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'receptionist') {
            return;
        }

        $canAccess = (int) $booking->created_by === (int) $user->id
            || $booking->staffAssignments()
                ->where('staff_id', $user->id)
                ->where('status', 'active')
                ->exists();

        abort_unless($canAccess, 403, 'Bạn không được phân công xử lý booking này.');
    }

    private function addBookingLog(Booking $booking, string $action, string $description): void
    {
        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
        ]);
    }
}