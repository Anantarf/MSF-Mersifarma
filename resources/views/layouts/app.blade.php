<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MSF') : Mobile Sales Force PT Mersifarma</title>
    <!-- Favicon: Logo Resmi PT Mersifarma TM -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo-mersifarma.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-mersifarma.png') }}">
    <!-- Bootstrap Icons (BI) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Fonts: Single cohesive enterprise typeface (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/layout.css')
    @stack('styles')
</head>
<body>
    <div id="toastContainer" class="toast-container" aria-live="polite"></div>
    @auth
    <header>
        <div class="nav-container">
            <a href="{{ route('call-lists.index') }}" class="brand">
                <img src="{{ asset('images/logo-mersifarma.png') }}" alt="Logo PT Mersifarma TM" class="brand-logo-img">
                <div class="brand-titles">
                    <span class="brand-wordmark">mersi</span>
                    <span class="brand-sub">Mobile Sales Force</span>
                </div>
            </a>

            <nav class="nav-links">
                <a href="{{ route('call-lists.index') }}" class="nav-link {{ request()->routeIs('call-lists.*') ? 'active' : '' }}" title="Target Dokter">
                    Target Dokter
                </a>
                <a href="{{ route('call-plans.index') }}" class="nav-link {{ request()->routeIs('call-plans.*') ? 'active' : '' }}" title="Jadwal Kunjungan">
                    Jadwal Kunjungan
                </a>
                <a href="{{ route('call-actuals.index') }}" class="nav-link {{ request()->routeIs('call-actuals.*') ? 'active' : '' }}" title="Pelaksanaan Kunjungan">
                    Pelaksanaan Kunjungan
                </a>
            </nav>

            <div class="user-menu">

                <div class="user-profile-badge">
                    <div class="user-profile-top">
                        <strong class="user-profile-name">{{ auth()->user()->name }}</strong>
                        <span class="role-pill role-{{ auth()->user()->role }}">{{ strtoupper(auth()->user()->role) }}</span>
                    </div>
                    <span class="user-profile-subtitle">{{ auth()->user()->role_subtitle }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="logout-form">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-logout">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <nav class="mobile-bottom-nav">
        <a href="{{ route('call-lists.index') }}" class="mobile-nav-item {{ request()->routeIs('call-lists.*') ? 'active' : '' }}" title="Target Dokter">
            <i class="bi bi-clipboard-check mobile-nav-icon"></i>
            <span>Target</span>
        </a>
        <a href="{{ route('call-plans.index') }}" class="mobile-nav-item {{ request()->routeIs('call-plans.*') ? 'active' : '' }}" title="Jadwal Kunjungan">
            <i class="bi bi-calendar-event mobile-nav-icon"></i>
            <span>Jadwal</span>
        </a>
        <a href="{{ route('call-actuals.index') }}" class="mobile-nav-item {{ request()->routeIs('call-actuals.*') ? 'active' : '' }}" title="Pelaksanaan Kunjungan">
            <i class="bi bi-check2-circle mobile-nav-icon"></i>
            <span>Kunjungan</span>
        </a>
    </nav>
    @endauth

    <main>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="alert-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer>
        <div class="footer-inner">
            Mobile Sales Force v1.0 - Ananta Raihan Fatih
        </div>
    </footer>

    @vite('resources/js/layout.js')
    @stack('scripts')
</body>
</html>
