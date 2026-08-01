<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\RoomInspection;
use App\Services\RoomInspectionWorkflowService;
use App\Support\Realtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InspectionGuestConsultationController extends Controller
{
    public function store(
        Request $request,
        Booking $booking,
        RoomInspection $roomInspection,
        RoomInspectionWorkflowService $workflow
    ) {
        $this->guardCanAccessBooking($booking);

        if ((int) $roomInspection->booking_id !== (int) $booking->id) {
            abort(404);
        }

        $data = $request->validate([
            'item_responses' => 'required|array',
            'item_responses.*' => 'required|in:accepted,disputed',
            'item_notes' => 'nullable|array',
            'item_notes.*' => 'nullable|string|max:1000',
            'item_claimed_quantities' => 'nullable|array',
            'item_claimed_quantities.*' => 'nullable|integer|min:0|max:999',
            'guest_consultation_note' => 'nullable|string|max:1500',
        ]);

        DB::beginTransaction();

        try {
            $inspection = RoomInspection::whereKey($roomInspection->id)
                ->lockForUpdate()
                ->with(['items', 'room', 'booking'])
                ->firstOrFail();

            if ((int) $inspection->booking_id !== (int) $booking->id) {
                throw new \RuntimeException('Phiếu kiểm tra không thuộc booking này.');
            }

            if ($booking->status !== 'inspection_requested') {
                throw new \RuntimeException('Booking không còn ở bước kiểm tra phòng.');
            }

            if (
                $inspection->status !== 'reported'
                || $inspection->workflow_stage !== RoomInspection::STAGE_GUEST_CONSULTATION
            ) {
                throw new \RuntimeException('Phiếu này hiện không ở bước lễ tân trao đổi với khách.');
            }

            if ($inspection->items->isEmpty()) {
                throw new \RuntimeException('Phiếu không có hạng mục cần trao đổi với khách.');
            }

            $responses = $data['item_responses'] ?? [];
            $notes = $data['item_notes'] ?? [];
            $claimedQuantities = $data['item_claimed_quantities'] ?? [];
            $changes = [];
            $disputedNames = [];
            $acceptedNames = [];

            foreach ($inspection->items as $item) {
                $response = $responses[$item->id] ?? null;
                $note = trim((string) ($notes[$item->id] ?? ''));
                $claimedQuantity = array_key_exists($item->id, $claimedQuantities)
                    && $claimedQuantities[$item->id] !== null
                    && $claimedQuantities[$item->id] !== ''
                        ? max(0, (int) $claimedQuantities[$item->id])
                        : null;

                if (!in_array($response, ['accepted', 'disputed'], true)) {
                    throw ValidationException::withMessages([
                        "item_responses.{$item->id}" => 'Vui lòng ghi nhận ý kiến khách cho hạng mục ' . $item->name . '.',
                    ]);
                }

                $wasAlreadyAccepted = $item->guest_response === 'accepted';

                if ($wasAlreadyAccepted && $response !== 'accepted') {
                    throw ValidationException::withMessages([
                        "item_responses.{$item->id}" => 'Hạng mục ' . $item->name . ' đã khớp và được khách xác nhận trước đó, không thể gửi khiếu nại lại. Nếu buồng phòng phát hiện kết quả thực tế thay đổi, hãy cập nhật từ màn kiểm tra phòng.',
                    ]);
                }

                if ($response === 'disputed' && $claimedQuantity === null) {
                    throw ValidationException::withMessages([
                        "item_claimed_quantities.{$item->id}" => 'Vui lòng nhập số lượng khách xác nhận cho hạng mục ' . $item->name . '.',
                    ]);
                }

                if ($response === 'disputed' && $claimedQuantity === (int) $item->quantity) {
                    throw ValidationException::withMessages([
                        "item_claimed_quantities.{$item->id}" => 'Số lượng khách xác nhận đã trùng với kết quả hiện tại. Hạng mục này được xem là đã thống nhất và không cần kiểm tra lại.',
                    ]);
                }

                if ($response === 'disputed' && $note === '') {
                    throw ValidationException::withMessages([
                        "item_notes.{$item->id}" => 'Vui lòng ghi ngắn gọn vị trí hoặc nội dung cần buồng phòng kiểm tra lại.',
                    ]);
                }

                $before = $workflow->itemSnapshot($item);

                $itemUpdate = [
                    'guest_response' => $response,
                    'guest_response_note' => $note !== '' ? $note : null,
                    'guest_claimed_quantity' => $response === 'disputed'
                        ? $claimedQuantity
                        : (int) $item->quantity,
                    'guest_responded_by' => Auth::id(),
                    'guest_responded_at' => now('Asia/Ho_Chi_Minh'),
                ];

                if ($response === 'disputed') {
                    // Khách vẫn chưa đồng ý: mở một vòng kiểm tra lại mới.
                    $itemUpdate += [
                        'recheck_decision' => 'pending',
                        'recheck_note' => null,
                        'rechecked_by' => null,
                        'rechecked_at' => null,
                    ];
                } elseif (!in_array($item->recheck_decision, ['keep_charge', 'remove_charge'], true)) {
                    // Hạng mục chưa từng phải kiểm tra lại.
                    $itemUpdate['recheck_decision'] = 'not_required';
                }
                // Nếu buồng phòng vừa kiểm tra lại và khách đồng ý, giữ nguyên kết quả/ghi chú
                // để admin nhìn thấy căn cứ cuối cùng, không xóa mất dấu.

                $item->update($itemUpdate);

                $item->refresh();
                $after = $workflow->itemSnapshot($item);

                $changes[] = [
                    'item_id' => $item->id,
                    'event_type' => 'guest_consultation',
                    'summary' => $response === 'accepted'
                        ? 'Khách đồng ý hạng mục “' . $item->name . '” với số lượng hiện tại là ' . (int) $item->quantity . ' ' . ($item->unit ?: 'đơn vị') . '.'
                        : 'Khách xác nhận hạng mục “' . $item->name . '” chỉ có ' . $claimedQuantity . ' ' . ($item->unit ?: 'đơn vị')
                            . ($note !== '' ? '. Ghi chú: ' . $note : '.') ,
                    'before' => $before,
                    'after' => $after,
                ];

                if ($response === 'disputed') {
                    $disputedNames[] = $item->name;
                } else {
                    $acceptedNames[] = $item->name;
                }
            }

            $nextStage = empty($disputedNames)
                ? RoomInspection::STAGE_ADMIN_APPROVAL
                : RoomInspection::STAGE_HOUSEKEEPING_RECHECK;

            $summary = empty($disputedNames)
                ? 'Lễ tân đã trao đổi lại với khách: khách đồng ý toàn bộ ' . count($acceptedNames) . ' hạng mục hiện tại. Chờ admin xác nhận.'
                : 'Lễ tân đã trao đổi với khách: ' . count($disputedNames) . ' hạng mục cần buồng phòng kiểm tra lại (' . implode(', ', $disputedNames) . ').';

            $inspection->update([
                'workflow_stage' => $nextStage,
                'guest_consulted_by' => Auth::id(),
                'guest_consulted_at' => now('Asia/Ho_Chi_Minh'),
                'guest_consultation_note' => trim((string) ($data['guest_consultation_note'] ?? '')) ?: null,
            ]);

            $workflow->advanceVersion(
                $inspection,
                'guest_consultation',
                $summary,
                $changes,
                Auth::id()
            );

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => empty($disputedNames) ? 'inspection_guest_accepted' : 'inspection_guest_disputed',
                'description' => 'Trao đổi kết quả kiểm tra phòng '
                    . ($inspection->room->room_number ?? '---')
                    . ' với khách. ' . $summary,
            ]);

            DB::commit();
            Realtime::inspection($inspection->id, empty($disputedNames) ? 'guest_accepted' : 'recheck_requested');

            return back()->with(
                'success',
                empty($disputedNames)
                    ? 'Đã ghi nhận khách đồng ý toàn bộ kết quả hiện tại. Phiếu đã chuyển sang admin xác nhận cuối.'
                    : 'Đã gửi các hạng mục khách phản hồi sang buồng phòng kiểm tra lại.'
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Không thể ghi nhận trao đổi với khách: ' . $e->getMessage());
        }
    }

    private function guardCanAccessBooking(Booking $booking): void
    {
        $user = Auth::user();

        abort_unless($user && in_array($user->role, [
            'super_admin',
            'manager',
            'receptionist_lead',
            'receptionist',
        ], true), 403, 'Bạn không có quyền trao đổi phiếu kiểm tra với khách.');
    }
}
