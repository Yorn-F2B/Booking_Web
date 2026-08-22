<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="auth-user-id" content="{{ auth()->id() }}">
        @if(auth()->user()->customer)
            <meta name="customer-id" content="{{ auth()->user()->customer->id }}">
        @endif
    @endauth

    @include('user.partials.head')

</head>

<body>
    @include('partials.flash-toasts')
    {{-- Header --}}
    @include('user.partials.header')

    {{-- Nội dung --}}
    <main>
        @yield('content')
    </main>
    
    {{-- Footer --}}
    @include('user.partials.footer')

    {{-- JS giao diện chung --}}
    @include('user.partials.scripts')

    {{-- Giao diện chat --}}
    @include('user.partials.chat-box')

    {{-- Camera thật dùng getUserMedia cho mọi nút "Chụp ảnh" --}}
    @include('partials.camera-capture')

    {{-- Axios, Echo, Reverb và logic chat --}}
    @vite('resources/js/app.js')

</body>

</html>
