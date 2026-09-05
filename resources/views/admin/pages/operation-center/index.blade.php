@extends('layouts.admin')
@section('title','Trung tâm công việc')
@section('content')
<div class="admin-wrapper"><div class="admin-content">
<div class="container-fluid py-3">
 <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div><h1 class="h3 fw-bold mb-1">Trung tâm công việc</h1><div class="text-muted">Các việc đang còn mở được hệ thống tổng hợp theo vai trò; không cần mở từng booking để dò.</div></div>
  <form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary btn-sm">Đánh dấu thông báo đã đọc</button></form>
 </div>
 <div class="row g-3">
  <div class="col-xl-7"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 fw-bold">Việc cần xử lý <span class="badge bg-danger-subtle text-danger">{{ $tasks->count() }}</span></h2>
   @forelse($tasks as $task)<a href="{{ $task['url'] }}" class="d-flex text-decoration-none text-dark border rounded-3 p-3 mt-2 gap-3 align-items-start">
    <span class="badge {{ $task['priority']==='high'?'bg-danger':($task['priority']==='medium'?'bg-warning text-dark':'bg-secondary') }}">{{ $task['priority']==='high'?'Gấp':($task['priority']==='medium'?'Cần làm':'Theo dõi') }}</span>
    <div class="flex-grow-1"><div class="fw-bold">{{ $task['title'] }}</div><div class="small text-muted">{{ $task['detail'] }}</div></div><i class="bx bx-chevron-right fs-4"></i>
   </a>@empty<div class="alert alert-success mb-0">Hiện không có công việc tồn cần xử lý.</div>@endforelse
  </div></div></div>
  <div class="col-xl-5"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 fw-bold">Thông báo</h2>
   @forelse($notifications as $n)<a href="{{ route('admin.notifications.open',$n) }}" class="d-block text-decoration-none text-dark border-bottom py-3 {{ $n->read_at?'opacity-75':'' }}"><div class="d-flex justify-content-between gap-2"><strong>{{ $n->title }}</strong>@if(!$n->read_at)<span class="badge bg-danger rounded-pill">Mới</span>@endif</div><div class="small text-muted mt-1">{{ $n->message }}</div><div class="small text-muted mt-1">{{ $n->created_at?->format('H:i d/m/Y') }}</div></a>@empty<div class="text-muted">Chưa có thông báo.</div>@endforelse
   <div class="mt-3">{{ $notifications->links() }}</div>
  </div></div></div>
 </div>
</div></div></div>
@endsection
