<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BookingLog;
use Illuminate\Support\Facades\Auth;
use App\Models\Service;
use App\Models\BookingServiceItem;
use Carbon\Carbon;

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

        if ($request->filled('payment_status') && in_array($request->payment_status, ['unpaid', 'partial', 'paid'], true)) {
            $bookings->where('payment_status', $request->payment_status);
        }

        if ($request->filled('filter_date')) {
            $filterDate = $request->filter_date;

            $timeFrom = $request->filter_time_from ?: '00:00';
            $timeTo = $request->filter_time_to ?: '23:59';

            $filterStart = Carbon::parse($filterDate . ' ' . $timeFrom . ':00', 'Asia/Ho_Chi_Minh');
            $filterEnd = Carbon::parse($filterDate . ' ' . $timeTo . ':59', 'Asia/Ho_Chi_Minh');

            if ($filterEnd->lessThanOrEqualTo($filterStart)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'filter_time_to' => 'Giờ kết thúc lọc phải sau giờ bắt đầu.',
                    ]);
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
        $booking->load([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'roomInspections.items',
            'serviceItems.service',
            'logs.user',
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
            ->orderBy('type')
            ->orderBy('name')
            ->get();

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
        ));
    }

    public function edit(Booking $booking)
    {
        return view('admin.pages.bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
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
        return back()->with(
            'error',
            'Admin không được hủy booking thường. Chỉ được xử lý hủy no-show từ trang chi tiết khi khách quá giờ check-in theo chính sách.'
        );
    }

    public function storeServiceItem(Request $request, Booking $booking)
    {
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

    public function updateNote(Request $request, Booking $booking)
    {
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