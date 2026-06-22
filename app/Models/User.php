<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
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

    public function isHousekeeping()
    {
        return $this->role === 'housekeeping';
    }

    public function customerChatConversations()
    {
        return $this->hasMany(ChatConversation::class, 'customer_id');
    }

    public function assignedChatConversations()
    {
        return $this->hasMany(ChatConversation::class, 'assigned_staff_id');
    }

}