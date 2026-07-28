<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingLog;
use App\Models\RoomInspection;
use App\Services\BookingFinancialService;
use App\Services\RoomInspectionWorkflowService;
use App\Support\Realtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            ->whereIn('status', ['reported', 'confirmed', 'rejected'])
            ->orderByRaw("CASE WHEN status = 'reported' AND workflow_stage = 'admin_approval' THEN 0 WHEN status = 'reported' THEN 1 ELSE 2 END")
            ->latest('updated_at')
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
            'guestConsultant',
            'adminAcknowledger',
            'items.guestResponder',
            'items.rechecker',
            'revisions.changer',
            'revisions.item',
        ]);

        return view('admin.pages.inspection-approvals.show', compact('roomInspection'));
    }

    public function acknowledge(Request $request, RoomInspection $roomInspection)
    {
        $data = $request->validate([
            'version' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $inspection = RoomInspection::whereKey($roomInspection->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $data['version'] !== (int) $inspection->version) {
                throw new \RuntimeException('Kết quả vừa được cập nhật thêm. Hãy tải lại trang và xem nội dung mới nhất.');
            }

            $inspection->update([
                'admin_acknowledged_version' => (int) $inspection->version,
                'admin_acknowledged_by' => Auth::id(),
                'admin_acknowledged_at' => now('Asia/Ho_Chi_Minh'),
            ]);

            DB::commit();

            return back()->with('success', 'Đã ghi nhận admin đã xem toàn bộ cập nhật mới. Có thể kiểm tra và xác nhận cuối.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể xác nhận đã xem cập nhật: ' . $e->getMessage());
        }
    }

    public function updates(Request $request, RoomInspection $roomInspection)
    {
        $sinceVersion = max(0, (int) $request->integer('since_version', 0));

        $inspection = RoomInspection::with(['revisions' => function ($query) use ($sinceVersion) {
            $query->where('version', '>', $sinceVersion)
                ->with(['changer', 'item'])
                ->orderBy('version')
                ->orderBy('id');
        }])->findOrFail($roomInspection->id);

        return response()->json([
            'current_version' => (int) $inspection->version,
            'acknowledged_version' => (int) $inspection->admin_acknowledged_version,
            'workflow_stage' => $inspection->workflow_stage,
            'status' => $inspection->status,
            'has_updates' => (int) $inspection->version > $sinceVersion,
            'last_update_summary' => $inspection->last_update_summary,
            'last_revision_at' => optional($inspection->last_revision_at)->format('d/m/Y H:i:s'),
            'updates' => $inspection->revisions->map(function ($revision) {
                return [
                    'version' => (int) $revision->version,
                    'event_type' => $revision->event_type,
                    'summary' => $revision->summary,
                    'item_name' => $revision->item->name ?? null,
                    'changed_by' => $revision->changer->name ?? null,
                    'created_at' => optional($revision->created_at)->format('d/m/Y H:i:s'),
                ];
            })->values(),
        ]);
    }

    public function approve(
        Request $request,
        RoomInspection $roomInspection,
        RoomInspectionWorkflowService $workflow
    ) {
        $data = $request->validate([
            'viewed_version' => 'required|integer|min:0',
            'approved_item_ids' => 'nullable|array',
            'approved_item_ids.*' => 'integer|exists:room_inspection_items,id',
            'rejection_notes' => 'nullable|array',
            'rejection_notes.*' => 'nullable|string|max:1000',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $inspection = RoomInspection::whereKey($roomInspection->id)
                ->lockForUpdate()
                ->with(['booking', 'room', 'items'])
                ->firstOrFail();

            if ($inspection->status !== 'reported') {
                throw new \RuntimeException('Chỉ có thể duyệt phiếu đang chờ xác nhận.');
            }

            if ($inspection->workflow_stage !== RoomInspection::STAGE_ADMIN_APPROVAL) {
                throw new \RuntimeException('Phiếu chưa hoàn tất bước trao đổi với khách hoặc kiểm tra lại.');
            }

            if ((int) $data['viewed_version'] !== (int) $inspection->version) {
                throw new \RuntimeException(
                    'Kết quả kiểm tra vừa được lễ tân hoặc buồng phòng cập nhật sau khi bạn mở trang. Hãy tải lại và xem nội dung mới trước khi xác nhận.'
                );
            }

            if ((int) $inspection->admin_acknowledged_version !== (int) $inspection->version) {
                throw new \RuntimeException('Admin chưa bấm “Tôi đã xem các cập nhật mới”. Không thể xác nhận khi chưa đọc kết quả mới nhất.');
            }

            $notAcceptedItems = $inspection->items
                ->filter(fn ($item) => $item->guest_response !== 'accepted')
                ->pluck('name')
                ->values();

            $mismatchedItems = $inspection->items
                ->filter(function ($item) {
                    if ($item->guest_claimed_quantity === null) {
                        return true;
                    }

                    return (int) $item->guest_claimed_quantity !== (int) $item->quantity;
                })
                ->pluck('name')
                ->values();

            if ($mismatchedItems->isNotEmpty()) {
                throw new \RuntimeException(
                    'Chưa thể xác nhận vì số lượng khách đối chiếu và buồng phòng kiểm tra chưa khớp ở: '
                    . $mismatchedItems->implode(', ')
                    . '. Hãy chuyển lại bước đối chiếu trước khi admin xác nhận.'
                );
            }

            if ($notAcceptedItems->isNotEmpty()) {
                throw new \RuntimeException(
                    'Khách chưa đồng ý kết quả hiện tại của: ' . $notAcceptedItems->implode(', ')
                    . '. Phiếu phải quay lại lễ tân trao đổi trước khi admin xác nhận.'
                );
            }

            $approvedItemIds = array_map('intval', $data['approved_item_ids'] ?? []);
            $approvedMinibarTotal = 0;
            $approvedDamageTotal = 0;
            $changes = [];

            foreach ($inspection->items as $item) {
                $before = $workflow->itemSnapshot($item);

                if (in_array((int) $item->id, $approvedItemIds, true)) {
                    $item->update([
                        'status' => 'approved',
                        'admin_note' => null,
                    ]);

                    if ($item->type === 'minibar') {
                        $approvedMinibarTotal += (float) $item->total;
                    } else {
                        $approvedDamageTotal += (float) $item->total;
                    }
                } else {
                    $note = trim((string) ($data['rejection_notes'][$item->id] ?? ''));

                    if ($note === '') {
                        throw ValidationException::withMessages([
                            "rejection_notes.{$item->id}" => 'Vui lòng nhập lý do không duyệt hạng mục ' . $item->name . '.',
                        ]);
                    }

                    $item->update([
                        'status' => 'rejected',
                        'admin_note' => $note,
                    ]);
                }

                $item->refresh();
                $changes[] = [
                    'item_id' => $item->id,
                    'event_type' => 'admin_approval',
                    'summary' => $item->status === 'approved'
                        ? 'Admin duyệt “' . $item->name . '” với số tiền ' . number_format((float) $item->total, 0, ',', '.') . 'đ.'
                        : 'Admin không duyệt “' . $item->name . '”: ' . $item->admin_note,
                    'before' => $before,
                    'after' => $workflow->itemSnapshot($item),
                ];
            }

            $approvedTotal = $approvedMinibarTotal + $approvedDamageTotal;
            $adminNote = trim((string) ($data['admin_note'] ?? ''));

            $inspection->update([
                'status' => 'confirmed',
                'workflow_stage' => RoomInspection::STAGE_COMPLETED,
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now('Asia/Ho_Chi_Minh'),
                'admin_note' => $adminNote !== '' ? $adminNote : null,
                'minibar_total' => $approvedMinibarTotal,
                'damage_total' => $approvedDamageTotal,
                'approved_total' => $approvedTotal,
            ]);

            $booking = $inspection->booking;

            if ($approvedTotal > 0) {
                $oldNote = $booking->note ? $booking->note . "\n" : '';
                $noteParts = [];

                if ($approvedMinibarTotal > 0) {
                    $noteParts[] = 'minibar/đồ dùng +' . number_format($approvedMinibarTotal, 0, ',', '.') . 'đ';
                }
                if ($approvedDamageTotal > 0) {
                    $noteParts[] = 'hư hại/mất đồ +' . number_format($approvedDamageTotal, 0, ',', '.') . 'đ';
                }

                $booking->update([
                    'note' => $oldNote
                        . now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                        . ' - Admin xác nhận kiểm tra phòng '
                        . ($inspection->room->room_number ?? '')
                        . ': ' . implode(', ', $noteParts)
                        . '. Tổng cộng +' . number_format($approvedTotal, 0, ',', '.') . 'đ.',
                ]);
            }

            app(BookingFinancialService::class)->refreshPaymentStatus($booking->fresh());

            $summary = 'Admin xác nhận cuối kết quả kiểm tra phòng '
                . ($inspection->room->room_number ?? '---')
                . '. Minibar/đồ dùng được duyệt: '
                . number_format($approvedMinibarTotal, 0, ',', '.')
                . 'đ; hư hại/mất đồ được duyệt: '
                . number_format($approvedDamageTotal, 0, ',', '.')
                . 'đ; tổng cộng: '
                . number_format($approvedTotal, 0, ',', '.')
                . 'đ.';

            $workflow->advanceVersion($inspection, 'admin_approval', $summary, $changes, Auth::id());

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'inspection_approved',
                'description' => $summary,
            ]);

            DB::commit();
            Realtime::inspection($inspection->id, 'inspection_approved');

            return redirect()
                ->route('admin.inspection-approvals.index')
                ->with('success', 'Đã xác nhận cuối kết quả kiểm tra phòng. Lễ tân có thể tiếp tục thu tiền và check-out.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Có lỗi khi duyệt kiểm tra: ' . $e->getMessage());
        }
    }
}
