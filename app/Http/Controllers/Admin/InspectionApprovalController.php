<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\BookingLog;
use App\Support\Realtime;

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

        $approvedItemIds = $data['approved_item_ids'] ?? [];
        $approvedItemIds = array_map('intval', $approvedItemIds);

        DB::beginTransaction();

        try {
            $approvedMinibarTotal = 0;
            $approvedDamageTotal = 0;

            foreach ($roomInspection->items as $item) {
                if (in_array((int) $item->id, $approvedItemIds)) {
                    $item->update([
                        'status' => 'approved',
                        'admin_note' => null,
                    ]);

                    if ($item->type == 'minibar') {
                        $approvedMinibarTotal += (float) $item->total;
                    } else {
                        $approvedDamageTotal += (float) $item->total;
                    }

                    continue;
                }

                $note = $data['rejection_notes'][$item->id] ?? null;

                if (!$note) {
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

            $approvedTotal = $approvedMinibarTotal + $approvedDamageTotal;

            $roomInspection->update([
                'status' => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'admin_note' => $data['admin_note'] ?? null,
                'damage_total' => $approvedTotal,
            ]);

            $booking = $roomInspection->booking;

            if ($approvedTotal > 0) {
                $oldNote = $booking->note ? $booking->note . "\n" : '';

                $noteParts = [];

                if ($approvedMinibarTotal > 0) {
                    $noteParts[] = 'dịch vụ tại phòng +' . number_format($approvedMinibarTotal, 0, ',', '.') . 'đ';
                }

                if ($approvedDamageTotal > 0) {
                    $noteParts[] = 'hư hại +' . number_format($approvedDamageTotal, 0, ',', '.') . 'đ';
                }

                $booking->estimated_total = (float) $booking->estimated_total + (float) $approvedTotal;
                $booking->payment_status = $booking->calculatePaymentStatus();
                $booking->note = $oldNote
                    . now()->format('d/m/Y H:i')
                    . ' - Admin duyệt kiểm tra phòng '
                    . ($roomInspection->room->room_number ?? '')
                    . ': ' . implode(', ', $noteParts)
                    . '. Tổng cộng +' . number_format($approvedTotal, 0, ',', '.') . 'đ.';
                $booking->save();
            }

            $approvedItems = $roomInspection->items
                ->where('status', 'approved')
                ->map(function ($item) {
                    $typeLabel = $item->type == 'minibar' ? 'dịch vụ tại phòng' : 'hư hại';

                    return $typeLabel . ' - ' . $item->name . ' x' . $item->quantity . ' = ' . number_format((float) $item->total, 0, ',', '.') . 'đ';
                })
                ->implode('; ');

            $rejectedItems = $roomInspection->items
                ->where('status', 'rejected')
                ->map(function ($item) {
                    $typeLabel = $item->type == 'minibar' ? 'dịch vụ tại phòng' : 'hư hại';

                    return $typeLabel . ' - ' . $item->name . ' - không duyệt' . ($item->admin_note ? ' vì: ' . $item->admin_note : '');
                })
                ->implode('; ');

            $description = 'Admin duyệt kiểm tra phòng '
                . ($roomInspection->room->room_number ?? '')
                . '. Dịch vụ tại phòng được duyệt: '
                . number_format($approvedMinibarTotal, 0, ',', '.')
                . 'đ. Hư hại được duyệt: '
                . number_format($approvedDamageTotal, 0, ',', '.')
                . 'đ. Tổng cộng: '
                . number_format($approvedTotal, 0, ',', '.')
                . 'đ.';

            if ($approvedItems) {
                $description .= ' Mục duyệt: ' . $approvedItems . '.';
            }

            if ($rejectedItems) {
                $description .= ' Mục không duyệt: ' . $rejectedItems . '.';
            }

            $this->addBookingLog(
                $booking,
                'inspection_approved',
                $description
            );

            DB::commit();

            return redirect()
                ->route('admin.inspection-approvals.index')
                ->with('success', 'Đã duyệt kết quả kiểm tra phòng. Khoản được duyệt đã được cộng vào đơn.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi duyệt kiểm tra: ' . $e->getMessage());
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
