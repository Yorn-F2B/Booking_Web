<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomInspection;
use App\Models\RoomInspectionItem;
use App\Models\Service;
use App\Models\BookingServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\BookingLog;

class FloorInspectionController extends Controller
{
    public function index()
    {
        $inspections = RoomInspection::with([
            'booking.customer',
            'booking.roomCategory',
            'room',
            'inspector',
            'items',
        ])
            ->latest()
            ->paginate(10);

        return view('admin.pages.floor-inspections.index', compact('inspections'));
    }

    public function show(RoomInspection $roomInspection)
    {
        $roomInspection->load([
            'booking.customer',
            'booking.roomCategory',
            'room',
            'inspector',
            'items',
        ]);

        $damageServices = Service::where('type', 'damage_fee')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $minibarServices = Service::where('type', 'minibar')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $registeredMinibarItems = $roomInspection->booking
            ? $roomInspection->booking->serviceItems()
                ->where('type', 'minibar')
                ->get()
            : collect();

        return view(
            'admin.pages.floor-inspections.show',
            compact('roomInspection', 'damageServices', 'minibarServices', 'registeredMinibarItems')
        );
    }

    public function report(Request $request, RoomInspection $roomInspection)
    {
        $data = $request->validate([
            'has_damage' => 'required|in:0,1',
            'damage_service_ids' => 'nullable|array',
            'damage_service_ids.*' => 'exists:services,id',
            'damage_quantities' => 'nullable|array',
            'damage_quantities.*' => 'nullable|integer|min:1',
            'inspection_note' => 'nullable|string|max:1000',
            'registered_minibar_used_quantities' => 'nullable|array',
            'registered_minibar_used_quantities.*' => 'nullable|integer|min:0',

            'room_minibar_service_ids' => 'nullable|array',
            'room_minibar_service_ids.*' => 'exists:services,id',
            'room_minibar_quantities' => 'nullable|array',
            'room_minibar_quantities.*' => 'nullable|integer|min:1',
        ]);

        if (!in_array($roomInspection->status, ['pending', 'reported', 'rejected'])) {
            return back()->with('error', 'Phiếu kiểm tra này đã được admin duyệt nên không thể sửa.');
        }

        $hasDamage = (bool) $data['has_damage'];

        DB::beginTransaction();

        try {
            RoomInspectionItem::where('room_inspection_id', $roomInspection->id)->delete();

            $damageTotal = 0;
            $minibarTotal = 0;

            $roomInspection->load(['booking.serviceItems']);

            if ($hasDamage) {
                $damageServiceIds = $data['damage_service_ids'] ?? [];
                $damageQuantities = $data['damage_quantities'] ?? [];

                if (count($damageServiceIds) == 0) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', 'Vui lòng chọn ít nhất một hạng mục hư hại.');
                }

                $damageServices = Service::whereIn('id', $damageServiceIds)
                    ->where('type', 'damage_fee')
                    ->where('status', 'active')
                    ->get()
                    ->keyBy('id');

                foreach ($damageServiceIds as $serviceId) {
                    if (!isset($damageServices[$serviceId])) {
                        continue;
                    }

                    $service = $damageServices[$serviceId];
                    $quantity = max(1, (int) ($damageQuantities[$serviceId] ?? 1));
                    $lineTotal = $service->price * $quantity;

                    RoomInspectionItem::create([
                        'room_inspection_id' => $roomInspection->id,
                        'service_id' => $service->id,
                        'name' => $service->name,
                        'unit' => $service->unit,
                        'price' => $service->price,
                        'quantity' => $quantity,
                        'total' => $lineTotal,
                        'status' => 'pending',
                        'admin_note' => null,
                    ]);

                    $damageTotal += $lineTotal;
                }
            }

            $registeredUsedQuantities = $data['registered_minibar_used_quantities'] ?? [];

            foreach ($registeredUsedQuantities as $itemId => $usedQuantity) {
                $item = BookingServiceItem::where('booking_id', $roomInspection->booking_id)
                    ->where('id', $itemId)
                    ->where('type', 'minibar')
                    ->first();

                if (!$item) {
                    continue;
                }

                $usedQuantity = max(0, (int) $usedQuantity);
                $usedQuantity = min($usedQuantity, (int) $item->quantity);

                $itemTotal = (float) $item->unit_price * $usedQuantity;

                $item->update([
                    'used_quantity' => $usedQuantity,
                    'billing_status' => $usedQuantity > 0 ? 'confirmed' : 'unused',
                    'total' => $itemTotal,
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                    'confirm_note' => $usedQuantity > 0
                        ? 'Buồng phòng xác nhận khách sử dụng ' . $usedQuantity . '/' . $item->quantity . ' ' . $item->name . '.'
                        : 'Buồng phòng xác nhận khách không sử dụng ' . $item->name . '.',
                ]);
            }

            $roomMinibarServiceIds = $data['room_minibar_service_ids'] ?? [];
            $roomMinibarQuantities = $data['room_minibar_quantities'] ?? [];

            if (count($roomMinibarServiceIds) > 0) {
                $minibarServices = Service::whereIn('id', $roomMinibarServiceIds)
                    ->where('type', 'minibar')
                    ->where('status', 'active')
                    ->get()
                    ->keyBy('id');

                foreach ($roomMinibarServiceIds as $serviceId) {
                    if (!isset($minibarServices[$serviceId])) {
                        continue;
                    }

                    $service = $minibarServices[$serviceId];
                    $quantity = max(1, (int) ($roomMinibarQuantities[$serviceId] ?? 1));
                    $lineTotal = (float) $service->price * $quantity;

                    RoomInspectionItem::create([
                        'room_inspection_id' => $roomInspection->id,
                        'service_id' => $service->id,
                        'type' => 'damage_fee',
                        'name' => $service->name,
                        'unit' => $service->unit,
                        'price' => $service->price,
                        'quantity' => $quantity,
                        'total' => $lineTotal,
                        'status' => 'pending',
                        'admin_note' => null,
                    ]);

                    $minibarTotal += $lineTotal;
                }
            }

            $roomInspection->update([
                'inspected_by' => Auth::id(),
                'status' => 'reported',
                'has_damage' => $hasDamage,
                'damage_items' => null,
                'damage_total' => $damageTotal + $minibarTotal,
                'inspection_note' => $data['inspection_note'] ?? null,
                'inspected_at' => now(),
                'confirmed_by' => null,
                'confirmed_at' => null,
                'admin_note' => null,
            ]);

            $roomInspection->load(['booking', 'room', 'items']);

            if ($hasDamage) {
                $this->addBookingLog(
                    $roomInspection->booking,
                    'inspection_reported',
                    'Buồng phòng báo cáo kiểm tra phòng '
                    . ($roomInspection->room->room_number ?? '')
                    . ': có hư hại, tổng tạm tính '
                    . number_format($damageTotal, 0, ',', '.')
                    . 'đ. Chờ admin duyệt.'
                );
            } else {
                $this->addBookingLog(
                    $roomInspection->booking,
                    'inspection_reported',
                    'Buồng phòng báo cáo kiểm tra phòng '
                    . ($roomInspection->room->room_number ?? '')
                    . ': không có hư hại. Chờ admin duyệt.'
                );
            }

            DB::commit();

            return redirect()
                ->route('admin.floor-inspections.index')
                ->with('success', 'Đã cập nhật kết quả kiểm tra. Chờ admin duyệt.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi gửi kết quả kiểm tra: ' . $e->getMessage());
        }
    }

    private function addBookingLog($booking, string $action, string $description): void
    {
        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
        ]);
    }
}