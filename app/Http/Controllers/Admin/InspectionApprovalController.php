<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomInspection;
use App\Models\RoomInspectionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InspectionApprovalController extends Controller
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
            ->whereIn('status', [
                'reported',
                'confirmed',
                'rejected',
            ])
            ->latest()
            ->paginate(10);

        return view('admin.pages.inspection-approvals.index', compact('inspections'));
    }

    public function show(RoomInspection $roomInspection)
    {
        $roomInspection->load([
            'booking.customer',
            'booking.roomCategory',
            'room',
            'inspector',
            'confirmer',
            'items',
        ]);

        return view('admin.pages.inspection-approvals.show', compact('roomInspection'));
    }

    public function approve(Request $request, RoomInspection $roomInspection)
    {
        $data = $request->validate([
            'approved_item_ids' => 'nullable|array',
            'approved_item_ids.*' => 'exists:room_inspection_items,id',
            'rejection_notes' => 'nullable|array',
            'rejection_notes.*' => 'nullable|string|max:1000',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        if ($roomInspection->status !== 'reported') {
            return back()->with('error', 'Chỉ có thể duyệt phiếu đang chờ admin duyệt.');
        }

        $roomInspection->load(['booking', 'room', 'items']);

        if ($roomInspection->has_damage && $roomInspection->items->count() == 0) {
            return back()->with('error', 'Phiếu báo có hư hại nhưng chưa có hạng mục nào.');
        }

        $approvedItemIds = $data['approved_item_ids'] ?? [];
        $approvedItemIds = array_map('intval', $approvedItemIds);

        DB::beginTransaction();

        try {
            $approvedTotal = 0;

            foreach ($roomInspection->items as $item) {
                if (in_array($item->id, $approvedItemIds)) {
                    $item->update([
                        'status' => 'approved',
                        'admin_note' => null,
                    ]);

                    $approvedTotal += $item->total;
                } else {
                    $note = $data['rejection_notes'][$item->id] ?? null;

                    if ($roomInspection->has_damage && !$note) {
                        DB::rollBack();

                        return back()
                            ->withInput()
                            ->with('error', 'Vui lòng nhập lý do cho các hạng mục không duyệt.');
                    }

                    $item->update([
                        'status' => 'rejected',
                        'admin_note' => $note,
                    ]);
                }
            }

            $roomInspection->update([
                'status' => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'admin_note' => $data['admin_note'] ?? null,
                'damage_total' => $approvedTotal,
            ]);

            $booking = $roomInspection->booking;
            $oldNote = $booking->note ? $booking->note . "\n" : '';

            if ($approvedTotal > 0) {
                $newEstimatedTotal = (float) $booking->estimated_total + (float) $approvedTotal;

                $booking->update([
                    'estimated_total' => $newEstimatedTotal,
                    'payment_status' => $booking->deposit_amount > 0 ? 'partial' : 'unpaid',
                    'note' => $oldNote . now()->format('d/m/Y H:i') . ' - Admin duyệt phí hư hại phòng ' . $roomInspection->room->room_number . ': +' . number_format((float) $approvedTotal, 0, ',', '.') . 'đ.',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.inspection-approvals.index')
                ->with('success', 'Đã duyệt kết quả kiểm tra phòng.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi duyệt kiểm tra: ' . $e->getMessage());
        }
    }
}