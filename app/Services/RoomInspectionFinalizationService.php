<?php

namespace App\Services;

use App\Models\BookingLog;
use App\Models\RoomInspection;
use Illuminate\Support\Facades\Auth;

class RoomInspectionFinalizationService
{
    public function finalize(
        RoomInspection $inspection,
        RoomInspectionWorkflowService $workflow,
        ?int $actorId = null,
        ?string $reason = null
    ): array {
        $actorId ??= Auth::id();

        // Luôn nạp lại các hạng mục vì cùng instance có thể đã cache quan hệ items
        // trước khi buồng phòng/khách vừa cập nhật. loadMissing() ở đây có thể dùng
        // collection cũ và chốt nhầm một tranh chấp chưa xử lý.
        $inspection->load(['items', 'booking', 'room']);

        $unresolvedItems = $inspection->items->filter(function ($item) {
            if ($item->guest_response !== 'accepted') {
                return true;
            }

            if ($item->guest_claimed_quantity === null) {
                return false;
            }

            return (int) $item->guest_claimed_quantity !== (int) $item->quantity;
        });

        if ($inspection->items->isNotEmpty() && $unresolvedItems->isNotEmpty()) {
            throw new \RuntimeException(
                'Chưa thể hoàn tất kiểm tra vì còn hạng mục chưa thống nhất với khách: '
                . $unresolvedItems->pluck('name')->implode(', ')
                . '.'
            );
        }

        $changes = [];
        $approvedMinibarTotal = 0.0;
        $approvedDamageTotal = 0.0;

        foreach ($inspection->items as $item) {
            $before = $workflow->itemSnapshot($item);

            $item->update([
                'status' => 'approved',
                'admin_note' => null,
            ]);

            if ($item->type === 'minibar') {
                $approvedMinibarTotal += (float) $item->total;
            } else {
                $approvedDamageTotal += (float) $item->total;
            }

            $item->refresh();
            $changes[] = [
                'item_id' => $item->id,
                'event_type' => 'inspection_completed',
                'summary' => 'Chốt hạng mục “' . $item->name . '” sau khi kết quả buồng phòng và ý kiến khách đã thống nhất: '
                    . number_format((float) $item->total, 0, ',', '.') . 'đ.',
                'before' => $before,
                'after' => $workflow->itemSnapshot($item),
            ];
        }

        $approvedTotal = $approvedMinibarTotal + $approvedDamageTotal;
        $completedAt = now('Asia/Ho_Chi_Minh');

        $inspection->update([
            'status' => 'confirmed',
            'workflow_stage' => RoomInspection::STAGE_COMPLETED,
            'confirmed_by' => $actorId,
            'confirmed_at' => $completedAt,
            'admin_note' => null,
            'minibar_total' => $approvedMinibarTotal,
            'damage_total' => $approvedDamageTotal,
            'approved_total' => $approvedTotal,
        ]);

        $summary = trim((string) $reason);
        if ($summary === '') {
            $summary = 'Hoàn tất kiểm tra phòng ' . ($inspection->room->room_number ?? '---')
                . ' vì kết quả buồng phòng và ý kiến khách đã thống nhất.';
        }

        $summary .= ' Phí minibar/đồ dùng: '
            . number_format($approvedMinibarTotal, 0, ',', '.')
            . 'đ; phí hư hại/mất đồ: '
            . number_format($approvedDamageTotal, 0, ',', '.')
            . 'đ; tổng cộng: '
            . number_format($approvedTotal, 0, ',', '.')
            . 'đ.';

        $workflow->advanceVersion(
            $inspection,
            'inspection_completed',
            $summary,
            $changes,
            $actorId
        );

        if ($inspection->booking) {
            BookingLog::create([
                'booking_id' => $inspection->booking->id,
                'user_id' => $actorId,
                'action' => 'inspection_completed',
                'description' => $summary,
            ]);

            app(BookingFinancialService::class)->refreshPaymentStatus($inspection->booking->fresh());
        }

        return [
            'approved_total' => $approvedTotal,
            'minibar_total' => $approvedMinibarTotal,
            'damage_total' => $approvedDamageTotal,
            'summary' => $summary,
        ];
    }
}
