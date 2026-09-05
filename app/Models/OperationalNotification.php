<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OperationalNotification extends Model
{
    protected $fillable = ['user_id','role','title','message','type','target_url','booking_id','room_id','read_at','meta'];
    protected $casts = ['read_at'=>'datetime','meta'=>'array'];

    public function user(){ return $this->belongsTo(User::class); }
    public function booking(){ return $this->belongsTo(Booking::class); }
    public function room(){ return $this->belongsTo(Room::class); }

    /**
     * Thông báo mới được fan-out theo từng user. Chỉ các row legacy thật sự
     * không có user_id mới được xem như broadcast theo role. Điều này tránh
     * nhân viên cùng role đọc/mở nhầm notification riêng của nhau.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $visible) use ($user) {
            $visible->where('user_id', $user->id)
                ->orWhere(function (Builder $legacyRoleBroadcast) use ($user) {
                    $legacyRoleBroadcast->whereNull('user_id')
                        ->where('role', $user->role);
                });
        });
    }
}
