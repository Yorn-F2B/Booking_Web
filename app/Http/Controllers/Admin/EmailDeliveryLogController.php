<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\EmailDeliveryLog;
use Illuminate\Http\Request;
class EmailDeliveryLogController extends Controller
{
 public function index(Request $request){
  $q=EmailDeliveryLog::query()->with('booking')->latest();
  if($request->filled('status')) $q->where('status',$request->status);
  if($request->filled('recipient')) $q->where('recipient','like','%'.$request->recipient.'%');
  $logs=$q->paginate(50)->withQueryString(); return view('admin.pages.email-logs.index',compact('logs'));
 }
}
