@extends('layouts.admin')
@section('title','Trung tâm công việc')
@section('content')
<style>
 .work-group{border:1px solid #e3e8ef;border-radius:14px;margin-top:10px;background:#fff;overflow:hidden}.work-group summary{display:flex;align-items:center;gap:10px;padding:14px;cursor:pointer;list-style:none}.work-group summary::-webkit-details-marker{display:none}.work-group[open] summary{background:#f7f9fc;border-bottom:1px solid #e3e8ef}.work-group-items{padding:8px 12px 12px}.work-item{display:flex;text-decoration:none;color:#172033;border-bottom:1px solid #edf0f4;padding:11px 4px;gap:12px;align-items:start}.work-item:last-child{border-bottom:0}.work-item:hover{color:#0d6efd}.work-count{min-width:32px;text-align:center}
</style>
<div class="admin-wrapper"><div class="admin-content">
<div class="container-fluid py-3">
 <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div><h1 class="h3 fw-bold mb-1">Trung tâm công việc</h1><div class="text-muted">Các việc đang còn mở được hệ thống tổng hợp theo vai trò; không cần mở từng booking để dò.</div></div>
  <form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary btn-sm">Đánh dấu thông báo đã đọc</button></form>
 </div>
 <div class="row g-3">
  <div class="col-xl-7"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 fw-bold">Việc cần xử lý <span class="badge bg-danger-subtle text-danger">{{ $tasks->count() }}</span></h2>
   <div class="small text-muted">Mỗi nhóm chỉ hiện một dòng. Mở nhóm để xem các booking/phòng cụ thể.</div>
   @forelse($taskGroups as $group)
    <details class="work-group">
     <summary>
      <span class="badge {{ $group['priority']==='high'?'bg-danger':($group['priority']==='medium'?'bg-warning text-dark':'bg-secondary') }}">{{ $group['priority']==='high'?'Gấp':($group['priority']==='medium'?'Cần làm':'Theo dõi') }}</span>
      <strong class="flex-grow-1">{{ $group['title'] }}</strong><span class="badge bg-primary work-count">{{ $group['count'] }}</span><i class="bx bx-chevron-down fs-5"></i>
     </summary>
     <div class="work-group-items">
      @foreach($group['items'] as $task)<a href="{{ $task['url'] }}" class="work-item"><div class="flex-grow-1"><div class="fw-semibold">{{ $task['detail'] }}</div><div class="small text-muted">{{ $task['time'] ? \Carbon\Carbon::parse($task['time'])->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') : '' }}</div></div><span class="small fw-semibold">Mở <i class="bx bx-chevron-right"></i></span></a>@endforeach
     </div>
    </details>
   @empty<div class="alert alert-success mt-3 mb-0">Hiện không có công việc tồn cần xử lý.</div>@endforelse
  </div></div></div>
  <div class="col-xl-5"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 fw-bold">Thông báo</h2>
   @forelse($notificationGroups as $group)
    <details class="work-group">
     <summary><strong class="flex-grow-1">{{ $group['title'] }}</strong><span class="badge {{ $group['unread_count'] ? 'bg-danger' : 'bg-secondary' }} work-count">{{ $group['count'] }}</span><i class="bx bx-chevron-down fs-5"></i></summary>
     <div class="work-group-items">@foreach($group['items'] as $n)<a href="{{ route('admin.notifications.open',$n) }}" class="work-item {{ $n->read_at?'opacity-75':'' }}"><div class="flex-grow-1"><div>{{ $n->message }}</div><div class="small text-muted mt-1">{{ $n->created_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</div></div>@if(!$n->read_at)<span class="badge bg-danger rounded-pill">Mới</span>@else<i class="bx bx-chevron-right"></i>@endif</a>@endforeach</div>
    </details>
   @empty<div class="text-muted mt-3">Chưa có thông báo.</div>@endforelse
   <div class="mt-3">{{ $notifications->links() }}</div>
  </div></div></div>
 </div>
</div></div></div>
@endsection
