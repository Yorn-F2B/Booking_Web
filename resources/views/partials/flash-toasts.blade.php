@php
    $flashMessages = [];

    foreach (['success', 'error', 'warning', 'info'] as $flashType) {
        $value = session($flashType);
        if (is_string($value) && trim($value) !== '') {
            $flashMessages[] = ['type' => $flashType, 'message' => trim($value)];
        }
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

    // Validation errors belong to the form that created them. Do not turn the
    // global Laravel error bag into a site-wide toast, otherwise an error from
    // one form can appear on an unrelated page/tab. Forms should render their
    // own validation messages next to the relevant fields or local error box.

    $seen = [];
    $flashMessages = array_values(array_filter($flashMessages, function ($item) use (&$seen) {
        $key = $item['type'] . '|' . preg_replace('/\s+/u', ' ', trim($item['message']));
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
            <span data-app-flash data-type="{{ $flash['type'] }}">{{ $flash['message'] }}</span>
        @endforeach
    </div>
@endif

<link rel="stylesheet" href="{{ asset('assets/css/flash-toast.css') }}?v={{ filemtime(public_path('assets/css/flash-toast.css')) }}">
<script src="{{ asset('assets/js/flash-toast.js') }}?v={{ filemtime(public_path('assets/js/flash-toast.js')) }}" defer></script>
