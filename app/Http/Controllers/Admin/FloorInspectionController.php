<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomInspection;
use App\Models\RoomInspectionItem;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        return view(
            'admin.pages.floor-inspections.show',
            compact('roomInspection', 'damageServices')
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
        ]);

        if (!in_array($roomInspection->status, ['pending', 'reported', 'rejected'])) {
            return back()->with('error', 'Phiếu kiểm tra này đã được admin duyệt nên không thể sửa.');
        }

        $hasDamage = (bool) $data['has_damage'];

        DB::beginTransaction();

        try {
            RoomInspectionItem::where('room_inspection_id', $roomInspection->id)->delete();

            $damageTotal = 0;

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

            $roomInspection->update([
                'inspected_by' => Auth::id(),
                'status' => 'reported',
                'has_damage' => $hasDamage,
                'damage_items' => null,
                'damage_total' => $damageTotal,
                'inspection_note' => $data['inspection_note'] ?? null,
                'inspected_at' => now(),
                'confirmed_by' => null,
                'confirmed_at' => null,
                'admin_note' => null,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.floor-inspections.index')
                ->with('success', 'Đã cập nhật kết quả kiểm tra. Chờ admin duyệt.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi gửi kết quả kiểm tra: ' . $e->getMessage());
        }
    }
}