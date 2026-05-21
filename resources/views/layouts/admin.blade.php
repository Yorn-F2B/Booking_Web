<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @include('admin.partials.head')

</head>

<body>
    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    {{-- Header --}}
    @include('admin.partials.header')

    {{-- Nội dung --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('admin.partials.scripts')

</body>

</html>