@php
    $flashMessages = [];

    foreach (['success', 'error', 'warning', 'info'] as $flashType) {
        $value = session($flashType);
        if (is_string($value) && trim($value) !== '') {
            $flashMessages[] = ['type' => $flashType, 'message' => trim($value)];
        }
    }

    // Kết quả gửi thông báo cho khách được xếp hàng riêng để một thao tác có
    // nhiều sự kiện không ghi đè nhau. Vẫn đọc key cũ để tương thích patch trước.
    $deliveries = session('customer_notification_deliveries', []);
    if (!is_array($deliveries)) {
        $deliveries = [];
    }

    $legacyDelivery = session('customer_notification_delivery');
    if (is_array($legacyDelivery) && !empty($legacyDelivery['message'])) {
        $deliveries[] = $legacyDelivery;
    }

    foreach ($deliveries as $delivery) {
        if (!is_array($delivery) || empty($delivery['message'])) {
            continue;
        }

        $deliveryType = $delivery['type'] ?? 'success';
        if (!in_array($deliveryType, ['success', 'error', 'warning', 'info'], true)) {
            $deliveryType = 'success';
        }

        $flashMessages[] = [
            'type' => $deliveryType,
            'title' => trim((string) ($delivery['title'] ?? 'Đã xử lý thông báo cho khách')),
            'message' => trim((string) $delivery['message']),
            'duration' => max(1800, (int) ($delivery['duration'] ?? 7000)),
            'dedupe_key' => trim((string) ($delivery['dedupe_key'] ?? '')),
        ];
    }

    $status = session('status');
    $statusMessages = [
        'profile-updated' => 'Cập nhật thông tin cá nhân thành công.',
        'password-updated' => 'Đổi mật khẩu thành công.',
        'verification-link-sent' => 'Đã gửi lại liên kết xác minh email.',
    ];
    if (is_string($status) && trim($status) !== '') {
        $flashMessages[] = [
            'type' => 'info',
            'message' => $statusMessages[$status] ?? $status,
        ];
    }

    // Validation errors thuộc về chính form phát sinh lỗi, không đưa vào toast toàn cục.
    $seen = [];
    $flashMessages = array_values(array_filter($flashMessages, function ($item) use (&$seen) {
        $key = ($item['dedupe_key'] ?? '') !== ''
            ? 'dedupe|' . $item['dedupe_key']
            : ($item['type'] . '|' . preg_replace('/\s+/u', ' ', trim($item['message'])));

        if (isset($seen[$key])) {
            return false;
        }
        $seen[$key] = true;
        return true;
    }));
@endphp

@if(count($flashMessages))
    <div data-app-flash-source hidden aria-hidden="true">
        @foreach($flashMessages as $flash)
            <span
                data-app-flash
                data-type="{{ $flash['type'] }}"
                @if(!empty($flash['title'])) data-title="{{ $flash['title'] }}" @endif
                @if(!empty($flash['duration'])) data-duration="{{ (int) $flash['duration'] }}" @endif
                @if(!empty($flash['dedupe_key'])) data-dedupe-key="{{ $flash['dedupe_key'] }}" @endif
            >{{ $flash['message'] }}</span>
        @endforeach
    </div>
@endif

<link rel="stylesheet" href="{{ asset('assets/css/flash-toast.css') }}?v={{ filemtime(public_path('assets/css/flash-toast.css')) }}">
<script src="{{ asset('assets/js/flash-toast.js') }}?v={{ filemtime(public_path('assets/js/flash-toast.js')) }}" defer></script>
