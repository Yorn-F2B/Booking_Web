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
            'serviceItems.service',
            'logs.user',
        ]);

        $assignedRoomIds = $booking->bookingRooms
            ->pluck('room_id')
            ->toArray();

        $availableRooms = Room::where('room_category_id', $booking->room_category_id)
            ->where(function ($query) use ($assignedRoomIds) {
                $query->whereNotIn('status', ['maintenance', 'cleaning']);

                if (!empty($assignedRoomIds)) {
                    $query->orWhereIn('id', $assignedRoomIds);
                }
            })
            ->where(function ($query) use ($booking, $assignedRoomIds) {
                if (!empty($assignedRoomIds)) {
                    $query->whereIn('id', $assignedRoomIds);
                }

                $query->orWhereDoesntHave('bookingRooms.booking', function ($bookingQuery) use ($booking) {
                    $bookingQuery->where('id', '!=', $booking->id)
                        ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                        ->where('check_in_at', '<', $booking->check_out_at)
                        ->where('check_out_at', '>', $booking->check_in_at);
                });
            })
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
            'status' => 'required|in:pending,confirmed,checked_in,checked_out,cancelled',
            'payment_status' => 'required|in:unpaid,partial,paid,refunded',
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

            if ($booking->status == 'cancelled') {
                $bookingRoom->room->update([
                    'status' => 'available',
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

    public function storeServiceItem(Request $request, Booking $booking)
    {
        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Booking đã kết thúc hoặc đã hủy nên không thể thêm dịch vụ.');
        }

        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:1000',
        ], [
            'service_id.required' => 'Vui lòng chọn dịch vụ.',
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.min' => 'Số lượng phải lớn hơn 0.',
        ]);

        $service = Service::where('id', $data['service_id'])
            ->where('status', 'active')
            ->whereIn('type', ['service', 'minibar'])
            ->first();

        if (!$service) {
            return back()->with('error', 'Dịch vụ không hợp lệ hoặc đã bị ẩn.');
        }

        $quantity = (int) $data['quantity'];
        $unitPrice = (float) $service->price;

        $billingStatus = $service->type == 'minibar' ? 'pending' : 'confirmed';
        $usedQuantity = $service->type == 'minibar' ? 0 : $quantity;
        $total = $unitPrice * $usedQuantity;

        DB::beginTransaction();

        try {
            $existingItem = BookingServiceItem::where('booking_id', $booking->id)
                ->where('service_id', $service->id)
                ->whereIn('type', ['service', 'minibar'])
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $quantity;

                if ($existingItem->type == 'minibar') {
                    $existingItem->billing_status = 'pending';
                    $existingItem->used_quantity = 0;
                    $existingItem->total = 0;
                } else {
                    $existingItem->used_quantity += $quantity;
                    $existingItem->billing_status = 'confirmed';
                    $existingItem->total += $total;
                }

                if (!empty($data['note'])) {
                    $existingItem->note = trim(($existingItem->note ? $existingItem->note . "\n" : '') . $data['note']);
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
                    'total' => $total,
                    'note' => $data['note'] ?? null,
                ]);
            }

            if ($total > 0) {
                $booking->estimated_total += $total;
                $booking->save();
            }

            $this->addBookingLog(
                $booking,
                'service_added',
                'Thêm dịch vụ "' . $service->name . '" x ' . $quantity . '. Thành tiền: ' . number_format($total, 0, ',', '.') . 'đ.'
            );

            DB::commit();

            return back()->with('success', $existingItem ? 'Dịch vụ đã có trong booking, hệ thống đã cộng thêm số lượng.' : 'Đã thêm dịch vụ vào booking.');
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

            if ($bookingServiceItem->type == 'minibar') {
                $usedQuantity = min((int) $bookingServiceItem->used_quantity, $newQuantity);
                $newTotal = (float) $bookingServiceItem->unit_price * $usedQuantity;
            } else {
                $usedQuantity = $newQuantity;
                $newTotal = (float) $bookingServiceItem->unit_price * $newQuantity;
            }

            $difference = $newTotal - $oldTotal;

            $bookingServiceItem->update([
                'quantity' => $newQuantity,
                'used_quantity' => $usedQuantity,
                'billing_status' => $bookingServiceItem->type == 'minibar'
                    ? ($usedQuantity > 0 ? 'confirmed' : 'pending')
                    : 'confirmed',
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
        if (in_array($booking->payment_status, ['paid', 'refunded'])) {
            return back()->with('error', 'Booking đã thanh toán hoặc đã hoàn tiền nên không thể đổi trạng thái thanh toán.');
        }

        $data = $request->validate([
            'payment_status' => 'required|in:unpaid,partial,paid,refunded',
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