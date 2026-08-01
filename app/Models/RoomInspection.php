<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInspection extends Model
{
    public const STAGE_HOUSEKEEPING_REPORT = 'housekeeping_report';
    public const STAGE_GUEST_CONSULTATION = 'guest_consultation';
    public const STAGE_HOUSEKEEPING_RECHECK = 'housekeeping_recheck';
    public const STAGE_ADMIN_APPROVAL = 'admin_approval';
    public const STAGE_COMPLETED = 'completed';

    protected $fillable = [
        'booking_id',
        'room_id',
        'inspected_by',
        'confirmed_by',
        'status',
        'workflow_stage',
        'version',
        'admin_acknowledged_version',
        'admin_acknowledged_by',
        'admin_acknowledged_at',
        'guest_consulted_by',
        'guest_consulted_at',
        'guest_consultation_note',
        'last_update_summary',
        'last_revision_at',
        'has_damage',
        'damage_items',
        'minibar_total',
        'damage_total',
        'approved_total',
        'inspection_note',
        'admin_note',
        'inspected_at',
        'confirmed_at',
    ];

    protected $casts = [
        'has_damage' => 'boolean',
        'damage_items' => 'array',
        'damage_total' => 'decimal:2',
        'minibar_total' => 'decimal:2',
        'approved_total' => 'decimal:2',
        'version' => 'integer',
        'admin_acknowledged_version' => 'integer',
        'inspected_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'admin_acknowledged_at' => 'datetime',
        'guest_consulted_at' => 'datetime',
        'last_revision_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function guestConsultant()
    {
        return $this->belongsTo(User::class, 'guest_consulted_by');
    }

    public function adminAcknowledger()
    {
        return $this->belongsTo(User::class, 'admin_acknowledged_by');
    }

    public function items()
    {
        return $this->hasMany(RoomInspectionItem::class);
    }

    public function revisions()
    {
        return $this->hasMany(RoomInspectionRevision::class)->orderByDesc('version')->orderByDesc('id');
    }

    public function hasUnseenAdminUpdate(): bool
    {
        return (int) $this->admin_acknowledged_version < (int) $this->version;
    }

    public function canAdminApprove(): bool
    {
        return $this->status === 'reported'
            && $this->workflow_stage === self::STAGE_ADMIN_APPROVAL
            && !$this->hasUnseenAdminUpdate();
    }
}
