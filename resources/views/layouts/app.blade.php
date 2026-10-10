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
    <!-- Fonts: Nunito for Mersi wordmark + Plus Jakarta Sans for UI/data + Outfit for headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0c4da1;          /* PT Mersifarma TM Authentic Blue */
            --primary-hover: #083c80;    /* Deep Mersi Blue hover */
            --primary-light: #eff6fd;    /* Mersi Blue subtle background tint */
            --primary-border: #b8daf8;   /* Mersi Blue crisp border */
            --secondary: #0c2340;        /* Mersifarma Deep Corporate Navy */
            --secondary-hover: #08182d;
            --success: #059669;          /* Emerald */
            --danger: #dc2626;           /* Red */
            --warning: #d97706;          /* Amber */
            --surface: #ffffff;
            --background: #f8fafc;
            --border: #cbd5e1;           /* Crisp solid enterprise border */
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        h1, h2, h3, .brand-text {
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
            color: var(--secondary);
            font-weight: 700;
        }

        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }

        body {
            background-color: var(--background);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: var(--surface);
            border-top: 3px solid var(--primary);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05);
        }

        .nav-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: var(--secondary);
        }

        .brand-logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            display: block;
        }

        .brand-titles {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .brand-wordmark {
            font-family: 'Nunito', 'Outfit', sans-serif;
            font-size: 1.45rem;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: -0.035em;
            line-height: 0.95;
            text-transform: lowercase;
            display: inline-block;
        }

        .brand-sub {
            font-size: 0.62rem;
            font-weight: 700;
            color: var(--secondary);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .brand-badge {
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid var(--primary-border);
            font-size: 0.7rem;
            padding: 0.15rem 0.45rem;
            border-radius: 0.375rem;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.5rem 0.85rem;
            border-radius: 0.375rem;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }

        .nav-link:hover {
            color: var(--primary);
            background: var(--primary-light);
        }

        .nav-link.active {
            color: var(--primary);
            background: var(--primary-light);
            border: 1px solid var(--primary-border);
        }

        .badge-upcoming {
            font-size: 0.7rem;
            font-weight: 600;
            background: #e2e8f0;
            color: #475569;
            padding: 0.15rem 0.4rem;
            border-radius: 0.25rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .role-pill {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            text-transform: uppercase;
        }

        .role-mr { background: #eff6fc; color: #005a9c; border: 1px solid #b9ddf8; }
        .role-dm { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .role-rsm { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .role-mm { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary-hover);
        }
        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-outline {
            background: #ffffff;
            border-color: var(--border);
            color: var(--secondary);
        }
        .btn-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        input, select, textarea {
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            color: var(--text-main);
            background: #ffffff;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary) !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(0, 102, 178, 0.25) !important;
        }

        button:focus-visible, a:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #ffffff;
        }

        .enterprise-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            text-align: left;
        }

        .enterprise-table th {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            font-weight: 700;
            color: var(--secondary);
            border-bottom: 1px solid var(--border);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .enterprise-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-main);
            vertical-align: middle;
        }

        .enterprise-table tr:last-child td {
            border-bottom: none;
        }

        .enterprise-table tbody tr:hover {
            background: #f8fafc;
        }

        main {
            flex: 1;
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            padding: 1.5rem 1rem;
        }

        .alert {
            padding: 0.85rem 1.15rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        footer {
            margin-top: auto;
            border-top: 1px solid var(--border);
            background: white;
            padding: 1rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: center;
        }

        .mobile-bottom-nav {
            display: none;
        }

        @media (max-width: 640px) {
            .nav-container {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 0.6rem 0.75rem;
            }
            .nav-links {
                display: none;
            }
            .user-menu {
                gap: 0.5rem;
            }
            .mobile-bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 60px;
                background: #ffffff;
                border-top: 1px solid var(--border);
                box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
                z-index: 50;
                justify-content: space-around;
                align-items: center;
                padding-bottom: env(safe-area-inset-bottom, 0);
            }
            .mobile-nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                color: var(--text-muted);
                font-size: 0.7rem;
                font-weight: 600;
                gap: 0.2rem;
                min-width: 64px;
                min-height: 48px;
                transition: color 0.15s ease;
            }
            .mobile-nav-item.active {
                color: var(--primary);
            }
            .mobile-nav-item svg {
                width: 20px;
                height: 20px;
            }
            main {
                padding: 1rem 0.75rem 80px 0.75rem;
            }
            footer {
                margin-bottom: 60px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
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
                <a href="{{ route('call-lists.index') }}" class="nav-link {{ request()->routeIs('call-lists.*') ? 'active' : '' }}">
                    Call List
                </a>
                <a href="{{ route('call-plans.index') }}" class="nav-link {{ request()->routeIs('call-plans.*') ? 'active' : '' }}">
                    Call Plan
                </a>
                <a href="{{ route('call-actuals.index') }}" class="nav-link {{ request()->routeIs('call-actuals.*') ? 'active' : '' }}">
                    Call Actual
                </a>
            </nav>

            <div class="user-menu">
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <span class="role-pill role-{{ auth()->user()->role }}">{{ strtoupper(auth()->user()->role) }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="min-height: 44px; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <nav class="mobile-bottom-nav">
        <a href="{{ route('call-lists.index') }}" class="mobile-nav-item {{ request()->routeIs('call-lists.*') ? 'active' : '' }}">
            <i class="bi bi-clipboard-check" style="font-size: 1.25rem;"></i>
            <span>Call List</span>
        </a>
        <a href="{{ route('call-plans.index') }}" class="mobile-nav-item {{ request()->routeIs('call-plans.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-event" style="font-size: 1.25rem;"></i>
            <span>Call Plan</span>
        </a>
        <a href="{{ route('call-actuals.index') }}" class="mobile-nav-item {{ request()->routeIs('call-actuals.*') ? 'active' : '' }}">
            <i class="bi bi-check2-circle" style="font-size: 1.25rem;"></i>
            <span>Call Actual</span>
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
                <ul style="margin-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer>
        <div style="max-width: 1100px; margin: 0 auto; text-align: center;">
            Mobile Sales Force v1.0 - Ananta Raihan Fatih
        </div>
    </footer>

    <script>
        // Global CSRF helper for API Fetch requests
        window.apiFetch = async function(url, options = {}) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            options.headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers || {})
            };
            return fetch(url, options);
        };
    </script>
    @stack('scripts')
</body>
</html>
