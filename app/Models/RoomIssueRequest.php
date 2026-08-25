<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomIssueRequest extends Model
{
    protected $fillable = [
        'booking_id','customer_id','current_room_id','current_room_category_id',
        'approved_room_id','approved_room_category_id','reviewed_by','issue_description',
        'status','resolution_type','price_difference_per_night','promotion_codes','admin_note',
        'repair_status','repair_completed_by','repair_completed_at','repair_note','reviewed_at',
        'group_uuid','workflow_status','proposed_resolution_type','proposed_room_id','proposed_room_category_id',
        'proposal_note','proposal_created_by','proposal_created_at','guest_response','guest_selected_resolution_type','guest_response_note',
        'guest_responded_by','guest_responded_at','proposal_expires_at',
        'housekeeping_verdict','housekeeping_can_repair_in_room','housekeeping_note',
        'housekeeping_verified_by','housekeeping_verified_at',
    ];

    protected $casts = [
        'promotion_codes' => 'array',
        'reviewed_at' => 'datetime',
        'repair_completed_at' => 'datetime',
        'proposal_created_at' => 'datetime',
        'guest_responded_at' => 'datetime',
        'proposal_expires_at' => 'datetime',
        'housekeeping_can_repair_in_room' => 'boolean',
        'housekeeping_verified_at' => 'datetime',
    ];

    public function scopeNeedsManagerAction($query)
    {
        return $query
            ->where('status', 'pending')
            ->whereIn('workflow_status', [
                'housekeeping_verified',
                'housekeeping_not_found',
                'proposal_ready',
                'guest_accepted',
                'guest_requested_change',
            ])
            // Quản lý chỉ xử lý cả nhóm sau khi buồng phòng đã kiểm tra hết
            // các phòng được khách báo trong cùng một lần.
            ->whereNotExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('room_issue_requests as pending_housekeeping')
                    ->whereColumn('pending_housekeeping.group_uuid', 'room_issue_requests.group_uuid')
                    ->where('pending_housekeeping.status', 'pending')
                    ->where('pending_housekeeping.workflow_status', 'awaiting_housekeeping');
            });
    }

    public function scopeWaitingGuestConfirmation($query)
    {
        return $query
            ->where('status', 'pending')
            ->where('workflow_status', 'waiting_guest_confirmation');
    }

    public function booking(){ return $this->belongsTo(Booking::class); }
    public function customer(){ return $this->belongsTo(Customer::class); }
    public function currentRoom(){ return $this->belongsTo(Room::class, 'current_room_id'); }
    public function currentCategory(){ return $this->belongsTo(RoomCategory::class, 'current_room_category_id'); }
    public function proposedRoom(){ return $this->belongsTo(Room::class, 'proposed_room_id'); }
    public function proposalCreator(){ return $this->belongsTo(User::class, 'proposal_created_by'); }
    public function guestResponder(){ return $this->belongsTo(User::class, 'guest_responded_by'); }
    public function approvedRoom(){ return $this->belongsTo(Room::class, 'approved_room_id'); }
    public function approvedCategory(){ return $this->belongsTo(RoomCategory::class, 'approved_room_category_id'); }
    public function reviewer(){ return $this->belongsTo(User::class, 'reviewed_by'); }
    public function housekeepingVerifier(){ return $this->belongsTo(User::class, 'housekeeping_verified_by'); }
    public function repairCompleter(){ return $this->belongsTo(User::class, 'repair_completed_by'); }
    public function roomChanges(){ return $this->hasMany(BookingRoomChange::class, 'room_issue_request_id'); }
    public function attachments(){ return $this->hasMany(RoomIssueAttachment::class); }
}
