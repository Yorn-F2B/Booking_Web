<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerRequestAttachment extends Model
{
    protected $fillable=['customer_request_id','file_path','original_name','mime_type','file_size'];
    public function customerRequest(){ return $this->belongsTo(CustomerRequest::class); }
}
