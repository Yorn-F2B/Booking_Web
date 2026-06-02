<header class="site-header">
    <nav class="navbar navbar-expand-lg py-2">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand" href="{{ url('/') }}">
                <span class="logo-mark">MC</span>
                <span>
                    MCuong Hotel
                    <span class="brand-sub">Luxury &amp; Comfort</span>
                </span>
            </a>

            <!-- Toggler -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Nav links -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">Trang chủ</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('rooms') ? 'active' : '' }}" href="{{ url('rooms') }}">Hạng phòng</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('about') ? 'active' : '' }}" href="{{ url('about') }}">Giới thiệu</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('contact') ? 'active' : '' }}" href="{{ url('contact') }}">Liên hệ</a>
                    </li>
                </ul>

                <!-- Right group -->
                <ul class="navbar-nav mb-2 mb-lg-0 align-items-lg-center gap-lg-2">

                    @guest

                        {{-- LOGIN --}}
                        <li class="nav-item">

                            <a class="nav-link {{ request()->is('login') ? 'active' : '' }}" href="{{ route('login') }}">

                                <i class="bx bx-log-in me-1"></i>
                                Đăng nhập

                            </a>

                        </li>

                        {{-- REGISTER --}}
                        <li class="nav-item">

                            <a class="nav-link {{ request()->is('register') ? 'active' : '' }}"
                                href="{{ route('register') }}">

                                Đăng ký

                            </a>

                        </li>

                    @endguest


                    @auth

                        {{-- USER --}}
                        <li class="nav-item">

                            <a class="nav-link {{ request()->is('user-settings') ? 'active' : '' }}" href="{{ url('user-settings') }}"
                                title="Tài khoản">

                                <i class="bx bx-user-circle" style="font-size:1.25rem;vertical-align:middle"></i>

                                {{ Auth::user()->name }}

                            </a>

                        </li>

                        {{-- ADMIN --}}
                        <li class="nav-item">

                            <a class="nav-link" href="{{ route('staffs.index') }}">

                                <i class="bx bx-shield-quarter me-1"></i>
                                Admin

                            </a>

                        </li>

                        {{-- LOGOUT --}}
                        <li class="nav-item">

                            <form method="POST" action="{{ route('logout') }}">

                                @csrf

                                <button type="submit" class="btn nav-link border-0 bg-transparent">

                                    <i class="bx bx-log-out me-1"></i>
                                    Đăng xuất

                                </button>

                            </form>

                        </li>

                    @endauth

                    {{-- DIVIDER --}}
                    <li class="d-none d-lg-block nav-divider"></li>

                    {{-- BOOKING HISTORY --}}
                    <li class="nav-item">

                        <a class="btn-nav-cta" href="{{ url('booking-history') }}">

                            <i class="bx bx-calendar-check"></i>
                            Lịch sử đặt phòng

                        </a>

                    </li>

                </ul>

            </div>
        </div>
    </nav>
</header>