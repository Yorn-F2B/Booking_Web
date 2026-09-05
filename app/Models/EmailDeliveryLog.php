<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class EmailDeliveryLog extends Model
{
    protected $fillable = ['booking_id','recipient','mail_type','subject','status','attempts','sent_at','failed_at','error_message','meta'];
    protected $casts = ['sent_at'=>'datetime','failed_at'=>'datetime','meta'=>'array'];

    /**
     * Failure còn cần xử lý: chưa có lần gửi thành công mới hơn cho cùng
     * booking + người nhận + loại email. Nhờ vậy Operation Center không treo
     * cảnh báo vĩnh viễn sau khi nhân viên đã sửa địa chỉ/gửi lại thành công.
     */
    public function scopeUnresolvedFailures(Builder $query): Builder
    {
        return $query
            ->where('email_delivery_logs.status', 'failed')
            ->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('email_delivery_logs as later_email_logs')
                    ->whereColumn('later_email_logs.id', '>', 'email_delivery_logs.id')
                    ->where('later_email_logs.status', 'sent')
                    ->whereColumn('later_email_logs.recipient', 'email_delivery_logs.recipient')
                    ->whereColumn('later_email_logs.mail_type', 'email_delivery_logs.mail_type')
                    ->where(function ($sameBooking) {
                        $sameBooking->whereColumn('later_email_logs.booking_id', 'email_delivery_logs.booking_id')
                            ->orWhere(function ($bothNull) {
                                $bothNull->whereNull('later_email_logs.booking_id')
                                    ->whereNull('email_delivery_logs.booking_id');
                            });
                    });
            });
    }
    public function booking(){ return $this->belongsTo(Booking::class); }
}
