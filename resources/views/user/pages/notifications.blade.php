@extends('layouts.app')
@section('title', 'Thông báo')
@section('content')
<div class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h3 mb-1">Thông báo</h1><p class="text-muted mb-0">Các thay đổi quan trọng liên quan đến booking và dịch vụ của bạn.</p></div>
        @if($notifications->whereNull('read_at')->count())
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-outline-secondary btn-sm">Đánh dấu đã đọc</button></form>
        @endif
    </div>
    <div class="list-group shadow-sm">
        @forelse($notifications as $notification)
            <a href="{{ route('notifications.open', $notification) }}" class="list-group-item list-group-item-action py-3 {{ $notification->read_at ? '' : 'bg-light' }}">
                <div class="d-flex justify-content-between gap-3">
                    <div><div class="fw-semibold">{{ $notification->title }}</div><div class="text-muted small mt-1">{{ $notification->message }}</div></div>
                    <div class="text-nowrap small text-muted">{{ optional($notification->created_at)->format('d/m H:i') }}</div>
                </div>
            </a>
        @empty
            <div class="list-group-item py-5 text-center text-muted">Chưa có thông báo.</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
</div>
@endsection
