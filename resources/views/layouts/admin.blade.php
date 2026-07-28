<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('admin.layouts.partials.head')

</head>

<body class="admin-page">
    <div class="d-none" aria-hidden="true">
        @if(session('success'))
            <span data-admin-flash data-type="success">{{ session('success') }}</span>
        @endif
        @if(session('error'))
            <span data-admin-flash data-type="error">{{ session('error') }}</span>
        @endif
        @if(session('warning'))
            <span data-admin-flash data-type="warning">{{ session('warning') }}</span>
        @endif
        @if(session('info'))
            <span data-admin-flash data-type="info">{{ session('info') }}</span>
        @endif
        @if($errors->any())
            @foreach($errors->all() as $error)
                <span data-admin-flash data-type="error">{{ $error }}</span>
            @endforeach
        @endif
    </div>

    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    {{-- Header --}}
    @include('admin.layouts.partials.header')

    {{-- Nội dung --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('admin.layouts.partials.scripts')

    {{-- Axios, Echo, Reverb và realtime chat --}}
    @vite('resources/js/app.js')

</body>

</html>
