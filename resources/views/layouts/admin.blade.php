<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('admin.layouts.partials.head')

</head>

<body>
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