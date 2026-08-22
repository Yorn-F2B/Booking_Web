<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'status',
        'google_id',
        'booking_locked_until',
        'booking_lock_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'booking_locked_until' => 'datetime',
        ];
    }


    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if (!in_array($user->role, ['super_admin', 'manager'], true)) {
                return;
            }

            $duplicate = static::query()
                ->where('role', $user->role)
                ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->getKey()))
                ->exists();

            if ($duplicate) {
                $label = $user->role === 'super_admin' ? 'Super Admin' : 'Quản lý';
                throw new RuntimeException("Khách sạn chỉ được có 1 {$label}.");
            }
        });
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isReceptionist()
    {
        return $this->role === 'receptionist';
    }

    public function isReceptionistLead()
    {
        return $this->role === 'receptionist_lead';
    }

    public function isHousekeeping()
    {
        return $this->role === 'housekeeping';
    }

    public function isHousekeepingSupervisor()
    {
        return $this->role === 'housekeeping_supervisor';
    }


    public function createdBookings()
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    public function bookingAssignments()
    {
        return $this->hasMany(BookingStaffAssignment::class, 'staff_id');
    }

    public function assignedBookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_staff_assignments', 'staff_id', 'booking_id')
            ->withPivot(['role_in_booking', 'assigned_by', 'status', 'note'])
            ->withTimestamps();
    }

    public function floorAssignments()
    {
        return $this->hasMany(StaffFloorAssignment::class, 'staff_id');
    }

    public function roomAssignments()
    {
        return $this->hasMany(StaffRoomAssignment::class, 'staff_id');
    }

    public function canManageAssignments()
    {
        return in_array($this->role, ['super_admin', 'manager', 'receptionist_lead', 'housekeeping_supervisor'], true);
    }

    public function canManageReceptionistAssignments()
    {
        return in_array($this->role, ['super_admin', 'manager', 'receptionist_lead'], true);
    }

    public function canManageHousekeepingAssignments()
    {
        return in_array($this->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true);
    }

    public function hotelReviews()
    {
        return $this->hasMany(HotelReview::class);
    }

    public function approvedHotelReviews()
    {
        return $this->hasMany(HotelReview::class, 'approved_by');
    }

    public function repliedHotelReviews()
    {
        return $this->hasMany(HotelReview::class, 'replied_by');
    }

    public function customerChatConversations()
    {
        return $this->hasMany(ChatConversation::class, 'customer_id');
    }

    public function assignedChatConversations()
    {
        return $this->hasMany(ChatConversation::class, 'assigned_staff_id');
    }

    public function chatPresence()
    {
        return $this->hasOne(ChatStaffPresence::class, 'user_id');
    }

}
