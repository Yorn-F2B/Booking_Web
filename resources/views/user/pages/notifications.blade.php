@extends('layouts.user')
@section('title', 'Thông báo')

@section('content')
<style>
    .customer-notification-page {
        max-width: 1120px;
    }

    .customer-notification-header {
        padding-bottom: 18px;
        border-bottom: 1px solid #e5e7eb;
    }

    .customer-notification-list {
        display: grid;
        gap: 12px;
    }

    .customer-notification-item {
        position: relative;
        display: block;
        padding: 18px 20px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        color: inherit;
        text-decoration: none;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    .customer-notification-item:hover {
        color: inherit;
        border-color: #cbd5e1;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .07);
        transform: translateY(-1px);
    }

    .customer-notification-item.is-unread {
        border-left: 4px solid #d4a72c;
        background: #fffdf7;
    }

    .customer-notification-title {
        font-size: 16px;
        font-weight: 750;
        line-height: 1.4;
        color: #111827;
    }

    .customer-notification-message {
        margin-top: 7px;
        font-size: 14px;
        line-height: 1.65;
        color: #5b6472;
    }

    .customer-notification-time {
        flex: 0 0 auto;
        font-size: 12px;
        color: #7b8492;
        white-space: nowrap;
    }

    .customer-notification-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        margin-right: 7px;
        border-radius: 50%;
        background: #dc3545;
        vertical-align: 1px;
    }

    .customer-notification-state {
        margin-top: 10px;
        font-size: 12px;
        font-weight: 700;
        color: #9a7411;
    }

    @media (max-width: 767.98px) {
        .customer-notification-item {
            padding: 16px;
        }

        .customer-notification-row {
            flex-direction: column;
            gap: 6px !important;
        }
    }
</style>

<div class="container customer-notification-page py-4 py-lg-5">
    <div class="customer-notification-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Thông báo</h1>
            <p class="text-muted mb-0">Thông tin cụ thể về đặt phòng, thanh toán, phòng ở và các yêu cầu dịch vụ của bạn.</p>
        </div>

        @if($notifications->whereNull('read_at')->count())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-outline-secondary btn-sm px-3" type="submit">Đánh dấu tất cả đã đọc</button>
            </form>
        @endif
    </div>

    <div class="customer-notification-list">
        @forelse($notifications as $notification)
            <a href="{{ route('notifications.open', $notification) }}"
               class="customer-notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
                <div class="customer-notification-row d-flex justify-content-between align-items-start gap-4">
                    <div class="flex-grow-1">
                        <div class="customer-notification-title">
                            @if(!$notification->read_at)<span class="customer-notification-dot"></span>@endif
                            {{ $notification->title }}
                        </div>
                        <div class="customer-notification-message">{{ $notification->message }}</div>
                        @if(!$notification->read_at)
                            <div class="customer-notification-state">Thông báo mới · Nhấn để xem chi tiết</div>
                        @endif
                    </div>
                    <time class="customer-notification-time" datetime="{{ optional($notification->created_at)->toIso8601String() }}">
                        {{ optional($notification->created_at)?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                    </time>
                </div>
            </a>
        @empty
            <div class="border rounded-4 bg-white py-5 px-3 text-center text-muted shadow-sm">
                Chưa có thông báo nào.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
</div>
@endsection
