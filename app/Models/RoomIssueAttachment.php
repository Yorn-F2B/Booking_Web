<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomIssueAttachment extends Model
{
    protected $fillable = ['room_issue_request_id','path','original_name','mime_type','size_bytes'];
    public function request(){ return $this->belongsTo(RoomIssueRequest::class, 'room_issue_request_id'); }
}
