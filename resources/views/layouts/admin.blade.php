<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('admin.layouts.partials.head')

</head>

<body class="admin-page"
    @auth
        data-auth-user-id="{{ auth()->id() }}"
        data-auth-user-role="{{ auth()->user()->role }}"
    @endauth>
    @include('partials.flash-toasts')
    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    {{-- Header --}}
    @include('admin.layouts.partials.header')

    {{-- Nội dung --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('admin.layouts.partials.scripts')

    {{-- Camera thật dùng getUserMedia cho mọi nút "Chụp ảnh" --}}
    @include('partials.camera-capture')

    {{-- Axios, Echo, Reverb và realtime chat --}}
    @vite('resources/js/app.js')

    @auth
        @if(in_array(auth()->user()->role, ['receptionist', 'receptionist_lead'], true))
            <script>
                (function () {
                    const url = @json(route('admin.chats.presence.heartbeat'));
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                    const heartbeat = async function () {
                        try {
                            await fetch(url, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrf,
                                },
                            });
                        } catch (error) {
                            console.debug('Chat heartbeat unavailable.', error);
                        }
                    };

                    heartbeat();
                    window.setInterval(heartbeat, 45000);
                })();
            </script>
        @endif
    @endauth

</body>

</html>
