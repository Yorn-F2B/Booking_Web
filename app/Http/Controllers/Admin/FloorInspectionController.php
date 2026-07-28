<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingLog;
use App\Models\RoomInspection;
use App\Models\RoomInspectionItem;
use App\Models\Service;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use App\Services\RoomInspectionWorkflowService;
use App\Support\Realtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        ]);

        $this->applyHousekeepingInspectionScope($inspections);

        $inspections = $inspections
            ->orderByRaw("CASE workflow_stage WHEN 'housekeeping_recheck' THEN 0 WHEN 'housekeeping_report' THEN 1 ELSE 2 END")
            ->latest()
            ->paginate(10);

        return view('admin.pages.floor-inspections.index', compact('inspections'));
    }

    public function show(RoomInspection $roomInspection)
    {
        $this->guardCanHandleInspection($roomInspection);

        $roomInspection->load([
            'booking.customer',
            'booking.roomCategory',
            'room',
            'inspector',
            'items.guestResponder',
            'items.rechecker',
            'revisions.changer',
        ]);

        $damageServices = Service::where('type', 'damage_fee')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $minibarServices = Service::where('type', 'minibar')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'admin.pages.floor-inspections.show',
            compact('roomInspection', 'damageServices', 'minibarServices')
        );
    }

    public function report(
        Request $request,
        RoomInspection $roomInspection,
        RoomInspectionWorkflowService $workflow
    ) {
        $this->guardCanHandleInspection($roomInspection);

        $data = $request->validate([
            'has_damage' => 'required|in:0,1',
            'damage_service_ids' => 'nullable|array',
            'damage_service_ids.*' => 'exists:services,id',
            'damage_quantities' => 'nullable|array',
            'damage_quantities.*' => 'nullable|integer|min:1',
            'inspection_note' => 'nullable|string|max:1000',
            'room_minibar_service_ids' => 'nullable|array',
            'room_minibar_service_ids.*' => 'exists:services,id',
            'room_minibar_quantities' => 'nullable|array',
            'room_minibar_quantities.*' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $inspection = RoomInspection::whereKey($roomInspection->id)
                ->lockForUpdate()
                ->with(['items', 'booking', 'room'])
                ->firstOrFail();

            if (
                !in_array($inspection->status, ['pending', 'rejected'], true)
                || $inspection->workflow_stage !== RoomInspection::STAGE_HOUSEKEEPING_REPORT
            ) {
                throw new \RuntimeException('Phiếu đã chuyển sang bước trao đổi với khách nên không thể nhập lại báo cáo ban đầu.');
            }

            $hasDamage = (bool) $data['has_damage'];
            $oldItems = $inspection->items->map(fn ($item) => $workflow->itemSnapshot($item))->values()->all();

            RoomInspectionItem::where('room_inspection_id', $inspection->id)->delete();

            $damageTotal = 0;
            $minibarTotal = 0;
            $damageMessages = [];
            $minibarMessages = [];
            $createdItems = collect();

            if ($hasDamage) {
                $damageServiceIds = array_values(array_unique(array_map('intval', $data['damage_service_ids'] ?? [])));
                $damageQuantities = $data['damage_quantities'] ?? [];

                if (count($damageServiceIds) === 0) {
                    throw ValidationException::withMessages([
                        'damage_service_ids' => 'Vui lòng chọn ít nhất một hạng mục hư hại.',
                    ]);
                }

                $damageServices = Service::whereIn('id', $damageServiceIds)
                    ->where('type', 'damage_fee')
                    ->where('status', 'active')
                    ->get()
                    ->keyBy('id');

                foreach ($damageServiceIds as $serviceId) {
                    $service = $damageServices->get($serviceId);
                    if (!$service) {
                        continue;
                    }

                    $quantity = max(1, (int) ($damageQuantities[$serviceId] ?? 1));
                    $lineTotal = (float) $service->price * $quantity;

                    $item = RoomInspectionItem::create([
                        'room_inspection_id' => $inspection->id,
                        'service_id' => $service->id,
                        'type' => 'damage_fee',
                        'name' => $service->name,
                        'unit' => $service->unit,
                        'price' => $service->price,
                        'quantity' => $quantity,
                        'total' => $lineTotal,
                        'original_total' => $lineTotal,
                        'status' => 'pending',
                        'admin_note' => null,
                        'guest_response' => 'pending',
                        'recheck_decision' => 'not_required',
                    ]);

                    $createdItems->push($item);
                    $damageTotal += $lineTotal;
                    $damageMessages[] = $service->name . ' x' . $quantity . ' = ' . number_format($lineTotal, 0, ',', '.') . 'đ';
                }
            }

            $roomMinibarServiceIds = array_values(array_unique(array_map('intval', $data['room_minibar_service_ids'] ?? [])));
            $roomMinibarQuantities = $data['room_minibar_quantities'] ?? [];

            if (count($roomMinibarServiceIds) > 0) {
                $minibarServices = Service::whereIn('id', $roomMinibarServiceIds)
                    ->where('type', 'minibar')
                    ->where('status', 'active')
                    ->get()
                    ->keyBy('id');

                foreach ($roomMinibarServiceIds as $serviceId) {
                    $service = $minibarServices->get($serviceId);
                    if (!$service) {
                        continue;
                    }

                    $quantity = max(1, (int) ($roomMinibarQuantities[$serviceId] ?? 1));
                    $lineTotal = (float) $service->price * $quantity;

                    $item = RoomInspectionItem::create([
                        'room_inspection_id' => $inspection->id,
                        'service_id' => $service->id,
                        'type' => 'minibar',
                        'name' => $service->name,
                        'unit' => $service->unit,
                        'price' => $service->price,
                        'quantity' => $quantity,
                        'total' => $lineTotal,
                        'original_total' => $lineTotal,
                        'status' => 'pending',
                        'admin_note' => null,
                        'guest_response' => 'pending',
                        'recheck_decision' => 'not_required',
                    ]);

                    $createdItems->push($item);
                    $minibarTotal += $lineTotal;
                    $minibarMessages[] = $service->name . ' x' . $quantity . ' = ' . number_format($lineTotal, 0, ',', '.') . 'đ';
                }
            }

            $hasChargeItems = $createdItems->isNotEmpty();
            $nextStage = $hasChargeItems
                ? RoomInspection::STAGE_GUEST_CONSULTATION
                : RoomInspection::STAGE_ADMIN_APPROVAL;

            $inspection->update([
                'inspected_by' => Auth::id(),
                'status' => 'reported',
                'workflow_stage' => $nextStage,
                'has_damage' => $hasDamage,
                'damage_items' => null,
                'damage_total' => $damageTotal,
                'minibar_total' => $minibarTotal,
                'approved_total' => 0,
                'inspection_note' => $data['inspection_note'] ?? null,
                'inspected_at' => now('Asia/Ho_Chi_Minh'),
                'confirmed_by' => null,
                'confirmed_at' => null,
                'admin_note' => null,
                'guest_consulted_by' => null,
                'guest_consulted_at' => null,
                'guest_consultation_note' => null,
            ]);

            $parts = [];
            if ($minibarTotal > 0) {
                $parts[] = 'minibar/đồ dùng: ' . implode('; ', $minibarMessages) . ' — ' . number_format($minibarTotal, 0, ',', '.') . 'đ';
            }
            if ($damageTotal > 0) {
                $parts[] = 'hư hại/mất đồ: ' . implode('; ', $damageMessages) . ' — ' . number_format($damageTotal, 0, ',', '.') . 'đ';
            }
            if (empty($parts)) {
                $parts[] = 'không phát sinh minibar, mất đồ hoặc hư hại';
            }

            $summary = 'Buồng phòng gửi kết quả kiểm tra phòng '
                . ($inspection->room->room_number ?? '---')
                . ': ' . implode('. ', $parts)
                . ($hasChargeItems ? '. Chờ lễ tân trao đổi với khách.' : '. Không có khoản phí; chờ admin xác nhận.');

            $changes = [[
                'item_id' => null,
                'event_type' => 'inspection_reported',
                'summary' => $summary,
                'before' => ['items' => $oldItems],
                'after' => [
                    'items' => $createdItems->map(fn ($item) => $workflow->itemSnapshot($item))->values()->all(),
                    'workflow_stage' => $nextStage,
                ],
            ]];

            $workflow->advanceVersion($inspection, 'inspection_reported', $summary, $changes, Auth::id());

            $this->addBookingLog($inspection->booking, 'inspection_reported', $summary);

            DB::commit();
            Realtime::inspection($inspection->id, 'inspection_reported');

            return redirect()
                ->route('admin.floor-inspections.index')
                ->with('success', $hasChargeItems
                    ? 'Đã gửi kết quả. Lễ tân cần trao đổi từng khoản với khách trước khi admin duyệt.'
                    : 'Đã gửi kết quả không phát sinh phí. Phiếu chuyển thẳng sang admin xác nhận.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Có lỗi khi gửi kết quả kiểm tra: ' . $e->getMessage());
        }
    }

    public function recheck(
        Request $request,
        RoomInspection $roomInspection,
        RoomInspectionWorkflowService $workflow
    ) {
        $this->guardCanHandleInspection($roomInspection);

        $data = $request->validate([
            'recheck_notes' => 'nullable|array',
            'recheck_notes.*' => 'nullable|string|max:1000',
            'recheck_quantities' => 'required|array',
            'recheck_quantities.*' => 'required|integer|min:0|max:999',
        ]);

        DB::beginTransaction();

        try {
            $inspection = RoomInspection::whereKey($roomInspection->id)
                ->lockForUpdate()
                ->with(['items', 'booking', 'room'])
                ->firstOrFail();

            if (
                $inspection->status !== 'reported'
                || $inspection->workflow_stage !== RoomInspection::STAGE_HOUSEKEEPING_RECHECK
            ) {
                throw new \RuntimeException('Phiếu này hiện không có hạng mục cần buồng phòng kiểm tra lại.');
            }

            if ($inspection->items->where('guest_response', 'disputed')->isEmpty()) {
                throw new \RuntimeException('Không tìm thấy hạng mục khách đang phản hồi.');
            }

            $notes = $data['recheck_notes'] ?? [];
            $quantities = $data['recheck_quantities'] ?? [];
            $changes = [];
            $comparisonSummaries = [];
            $needsGuestReview = [];
            $autoResolved = [];

            // Hiển thị và cho phép cập nhật toàn bộ hạng mục trong lần kiểm tra lại.
            // Nhờ vậy nếu buồng phòng phát hiện một khoản đã xác nhận trước đó thực tế thay đổi,
            // họ vẫn sửa được thay vì khoản đó biến mất khỏi màn hình.
            foreach ($inspection->items as $item) {
                if (!array_key_exists($item->id, $quantities)) {
                    continue;
                }

                $note = trim((string) ($notes[$item->id] ?? ''));
                $before = $workflow->itemSnapshot($item);
                $oldQuantity = max(0, (int) $item->quantity);
                $guestClaimedQuantity = $item->guest_claimed_quantity !== null
                    ? max(0, (int) $item->guest_claimed_quantity)
                    : $oldQuantity;
                $quantity = max(0, (int) $quantities[$item->id]);
                $newTotal = (float) $item->price * $quantity;
                $matchesGuest = $quantity === $guestClaimedQuantity;
                $quantityChanged = $quantity !== $oldQuantity;

                // Nếu kết quả đã khớp đúng số lượng khách xác nhận thì không bắt nhập lý do.
                // Chỉ khi vẫn khác ý kiến khách hoặc buồng phòng tự sửa một hạng mục khác
                // thì mới cần ghi rõ căn cứ để lễ tân còn trao đổi tiếp.
                if ((!$matchesGuest || ($quantityChanged && $item->guest_response !== 'disputed')) && $note === '') {
                    throw ValidationException::withMessages([
                        "recheck_notes.{$item->id}" => 'Kết quả vẫn khác ý kiến khách hoặc vừa được thay đổi; vui lòng ghi rõ căn cứ kiểm tra.',
                    ]);
                }

                if (!$quantityChanged && $item->guest_response !== 'disputed') {
                    // Hạng mục khách đã đồng ý và buồng phòng không thay đổi: giữ nguyên, không tạo vòng tranh luận mới.
                    continue;
                }

                $decision = $quantity > 0 ? 'keep_charge' : 'remove_charge';
                $nextGuestResponse = $matchesGuest ? 'accepted' : 'pending';
                $storedNote = $note !== ''
                    ? $note
                    : ($matchesGuest ? 'Kết quả xác minh khớp với số lượng khách đã xác nhận.' : null);

                $item->update([
                    'quantity' => $quantity,
                    'total' => $newTotal,
                    'guest_response' => $nextGuestResponse,
                    'recheck_decision' => $decision,
                    'recheck_note' => $storedNote,
                    'rechecked_by' => Auth::id(),
                    'rechecked_at' => now('Asia/Ho_Chi_Minh'),
                ]);

                $item->refresh();
                $after = $workflow->itemSnapshot($item);

                if ($matchesGuest) {
                    $comparisonText = 'khớp đúng số lượng khách xác nhận';
                    $autoResolved[] = $item->name;
                } elseif ($quantity > $guestClaimedQuantity) {
                    $comparisonText = 'cao hơn ý kiến khách ' . ($quantity - $guestClaimedQuantity) . ' ' . ($item->unit ?: 'đơn vị');
                    $needsGuestReview[] = $item->name;
                } else {
                    $comparisonText = 'thấp hơn ý kiến khách ' . ($guestClaimedQuantity - $quantity) . ' ' . ($item->unit ?: 'đơn vị');
                    $needsGuestReview[] = $item->name;
                }

                $itemSummary = 'Buồng phòng kiểm tra lại “' . $item->name . '”: khách xác nhận '
                    . $guestClaimedQuantity . ' ' . ($item->unit ?: 'đơn vị')
                    . ', xác minh thực tế ' . $quantity . ' ' . ($item->unit ?: 'đơn vị')
                    . ' (' . $comparisonText . '), thành tiền ' . number_format($newTotal, 0, ',', '.') . 'đ'
                    . ($note !== '' ? '. ' . $note : '.');

                $changes[] = [
                    'item_id' => $item->id,
                    'event_type' => 'housekeeping_recheck',
                    'summary' => $itemSummary,
                    'before' => $before,
                    'after' => $after,
                ];

                $comparisonSummaries[] = $item->name . ': khách ' . $guestClaimedQuantity
                    . ', xác minh ' . $quantity . ' ' . ($item->unit ?: 'đơn vị');
            }

            if (empty($changes)) {
                throw ValidationException::withMessages([
                    'recheck_quantities' => 'Không có hạng mục nào được cập nhật.',
                ]);
            }

            $inspection->refresh();
            $inspection->load('items');
            $hasPendingGuestReview = $inspection->items->contains(
                fn ($item) => $item->guest_response === 'pending'
            );

            $inspection->update([
                'workflow_stage' => $hasPendingGuestReview
                    ? RoomInspection::STAGE_GUEST_CONSULTATION
                    : RoomInspection::STAGE_ADMIN_APPROVAL,
                'minibar_total' => (float) $inspection->items->where('type', 'minibar')->sum('total'),
                'damage_total' => (float) $inspection->items->where('type', 'damage_fee')->sum('total'),
            ]);

            $summary = 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng '
                . ($inspection->room->room_number ?? '---') . ': '
                . implode('; ', $comparisonSummaries) . '.';

            if ($hasPendingGuestReview) {
                $summary .= ' Các khoản còn khác ý kiến khách cần lễ tân trao đổi lại: '
                    . implode(', ', $needsGuestReview) . '.';
            } else {
                $summary .= ' Tất cả kết quả đã khớp với ý kiến khách; chuyển admin xác nhận cuối.';
            }

            $workflow->advanceVersion($inspection, 'housekeeping_recheck', $summary, $changes, Auth::id());
            $this->addBookingLog($inspection->booking, 'inspection_rechecked', $summary);

            DB::commit();
            Realtime::inspection($inspection->id, 'inspection_rechecked');

            return redirect()
                ->route('admin.floor-inspections.index')
                ->with('success', $hasPendingGuestReview
                    ? 'Đã cập nhật. Chỉ các khoản còn lệch với ý kiến khách được chuyển lại lễ tân.'
                    : 'Đã cập nhật. Kết quả đã khớp với khách và được chuyển sang admin xác nhận cuối.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Không thể gửi kết quả kiểm tra lại: ' . $e->getMessage());
        }
    }

    private function applyHousekeepingInspectionScope($query): void
    {
        $user = Auth::user();

        if (!$user || in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true)) {
            return;
        }

        if ($user->role !== 'housekeeping') {
            abort(403, 'Bạn không có quyền xem phiếu kiểm tra phòng.');
        }

        $today = now('Asia/Ho_Chi_Minh')->toDateString();

        $assignedFloorNumbers = StaffFloorAssignment::where('staff_id', $user->id)
            ->whereDate('work_date', $today)
            ->where('status', 'active')
            ->pluck('floor_number')
            ->toArray();

        $assignedRoomIds = StaffRoomAssignment::where('staff_id', $user->id)
            ->whereDate('work_date', $today)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->pluck('room_id')
            ->toArray();

        $query->whereHas('room', function ($roomQuery) use ($assignedFloorNumbers, $assignedRoomIds) {
            $roomQuery->whereIn('id', $assignedRoomIds);

            if (!empty($assignedFloorNumbers)) {
                $roomQuery->orWhereIn('floor_number', $assignedFloorNumbers);
            }
        });
    }

    private function guardCanHandleInspection(RoomInspection $roomInspection): void
    {
        $user = Auth::user();

        if (!$user || in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true)) {
            return;
        }

        if ($user->role !== 'housekeeping') {
            abort(403, 'Bạn không có quyền xử lý phiếu kiểm tra này.');
        }

        $roomInspection->loadMissing('room');
        $today = now('Asia/Ho_Chi_Minh')->toDateString();
        $room = $roomInspection->room;

        $assignedByRoom = StaffRoomAssignment::where('staff_id', $user->id)
            ->where('room_id', $room?->id)
            ->whereDate('work_date', $today)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->exists();

        $assignedByFloor = StaffFloorAssignment::where('staff_id', $user->id)
            ->where('floor_number', $room?->floor_number)
            ->whereDate('work_date', $today)
            ->where('status', 'active')
            ->exists();

        abort_unless($assignedByRoom || $assignedByFloor, 403, 'Bạn không được phân công kiểm tra phòng này.');
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
