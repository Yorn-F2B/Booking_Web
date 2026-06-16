<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @include('user.partials.head')

</head>

<body>
    {{-- Header --}}
    @include('user.partials.header')

    {{-- Nội dung --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('user.partials.footer')
    @include('user.partials.scripts')

</body>

</html>