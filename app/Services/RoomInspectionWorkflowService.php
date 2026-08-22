<?php

namespace App\Services;

use App\Models\RoomInspection;
use App\Models\RoomInspectionItem;
use App\Models\RoomInspectionRevision;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoomInspectionWorkflowService
{
    /**
     * Tăng phiên bản phiếu đúng một lần và ghi một hoặc nhiều thay đổi trong cùng phiên bản.
     * Hàm này phải được gọi bên trong transaction sau khi phiếu đã lockForUpdate().
     */
    public function advanceVersion(
        RoomInspection $inspection,
        string $eventType,
        string $summary,
        array $changes = [],
        ?int $changedBy = null
    ): int {
        $nextVersion = max(0, (int) $inspection->version) + 1;
        $changedBy ??= Auth::id();

        $inspection->forceFill([
            'version' => $nextVersion,
            'last_update_summary' => $summary,
            'last_revision_at' => now('Asia/Ho_Chi_Minh'),
        ])->save();

        if (empty($changes)) {
            $changes[] = [
                'item_id' => null,
                'before' => null,
                'after' => null,
                'summary' => $summary,
            ];
        }

        foreach ($changes as $change) {
            RoomInspectionRevision::create([
                'room_inspection_id' => $inspection->id,
                'room_inspection_item_id' => $change['item_id'] ?? null,
                'version' => $nextVersion,
                'event_type' => $change['event_type'] ?? $eventType,
                'summary' => Str::limit((string) ($change['summary'] ?? $summary), 1000, ''),
                'before_data' => $change['before'] ?? null,
                'after_data' => $change['after'] ?? null,
                'changed_by' => $changedBy,
                'created_at' => now('Asia/Ho_Chi_Minh'),
            ]);
        }

        return $nextVersion;
    }

    public function itemSnapshot(RoomInspectionItem $item): array
    {
        return [
            'id' => (int) $item->id,
            'name' => (string) $item->name,
            'type' => (string) $item->type,
            'unit' => $item->unit,
            'price' => (float) $item->price,
            'quantity' => (int) $item->quantity,
            'total' => (float) $item->total,
            'original_total' => (float) ($item->original_total ?? $item->total),
            'status' => (string) $item->status,
            'guest_response' => (string) ($item->guest_response ?? 'pending'),
            'guest_response_note' => $item->guest_response_note,
            'guest_claimed_quantity' => $item->guest_claimed_quantity !== null ? (int) $item->guest_claimed_quantity : null,
            'recheck_decision' => (string) ($item->recheck_decision ?? 'not_required'),
            'recheck_note' => $item->recheck_note,
            'admin_note' => $item->admin_note,
            'detection_source' => (string) ($item->detection_source ?? 'initial'),
            'detected_by' => $item->detected_by !== null ? (int) $item->detected_by : null,
            'detected_at' => $item->detected_at?->toIso8601String(),
            'detection_version' => $item->detection_version !== null ? (int) $item->detection_version : null,
        ];
    }

    public function inspectionSnapshot(RoomInspection $inspection): array
    {
        return [
            'id' => (int) $inspection->id,
            'status' => (string) $inspection->status,
            'workflow_stage' => (string) ($inspection->workflow_stage ?? RoomInspection::STAGE_HOUSEKEEPING_REPORT),
            'version' => (int) $inspection->version,
            'has_damage' => (bool) $inspection->has_damage,
            'minibar_total' => (float) ($inspection->minibar_total ?? 0),
            'damage_total' => (float) ($inspection->damage_total ?? 0),
            'approved_total' => (float) ($inspection->approved_total ?? 0),
            'inspection_note' => $inspection->inspection_note,
            'guest_consultation_note' => $inspection->guest_consultation_note,
            'admin_note' => $inspection->admin_note,
        ];
    }
}
