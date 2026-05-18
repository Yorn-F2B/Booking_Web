<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin Panel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-white flex-shrink-0">
            <div class="h-16 flex items-center px-6 font-bold text-xl tracking-wider border-b border-slate-800">
                HOTEL ADMIN
            </div>
            <nav class="p-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="block px-4 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors duration-200">
                    Dashboard
                </a>
                <a href="{{ route('admin.room-categories.index') }}" class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.room-categories.*') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} transition-colors duration-200">
                    Quản lý Loại Phòng
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 z-10">
                <h1 class="text-xl font-semibold text-gray-800">
                    @yield('header', 'Admin Panel')
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">{{ Auth::user()->name ?? 'Admin' }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors">Đăng xuất</button>
                    </form>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-8">
                @if (session('success'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-md shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    {{ session('success') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
