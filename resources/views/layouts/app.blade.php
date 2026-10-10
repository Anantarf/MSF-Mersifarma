<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MSF') : Mobile Sales Force PT Mersifarma</title>
    <!-- Fonts: Plus Jakarta Sans for UI/data + Outfit for brand/headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0284c7;          /* Mersifarma Medical Sky-Blue */
            --primary-hover: #0369a1;    /* Deep sky hover */
            --primary-light: #f0f9ff;    /* Sky tint */
            --primary-border: #bae6fd;   /* Sky light border */
            --mersi-cyan: #0ea5e9;       /* Vibrant cyan accent */
            --mersi-teal: #0f766e;       /* Pharma clinic teal */
            --secondary: #0f172a;        /* Deep slate navy */
            --secondary-hover: #1e293b;
            --success: #059669;          /* Emerald for approved/completed */
            --danger: #dc2626;           /* Crimson alert */
            --warning: #d97706;          /* Amber pending */
            --surface: #ffffff;
            --background: #f8fafc;
            --border: #e2e8f0;
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
            letter-spacing: -0.015em;
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
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.04);
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
            gap: 0.6rem;
            text-decoration: none;
            color: var(--secondary);
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 2px 4px rgba(2, 132, 199, 0.25);
        }

        .brand-titles {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
        }

        .brand-name {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--secondary);
            letter-spacing: 0.02em;
        }

        .brand-sub {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.06em;
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

        .role-mr { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .role-dm { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .role-rsm { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .role-mm { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 1px 2px rgba(2, 132, 199, 0.2);
        }
        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-outline {
            background: transparent;
            border-color: var(--border);
            color: var(--text-main);
        }
        .btn-outline:hover {
            background: var(--border);
        }

        button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
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
                background: white;
                border-top: 1px solid var(--border);
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
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
                <div class="brand-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/>
                        <path d="m8.5 8.5 7 7"/>
                    </svg>
                </div>
                <div class="brand-titles">
                    <span class="brand-name brand-text">MERSIFARMA</span>
                    <span class="brand-sub">Mobile Sales Force</span>
                </div>
                <span class="brand-badge">v1.5</span>
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                <path d="M9 12h6"/>
                <path d="M9 16h6"/>
            </svg>
            <span>Call List</span>
        </a>
        <a href="{{ route('call-plans.index') }}" class="mobile-nav-item {{ request()->routeIs('call-plans.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span>Call Plan</span>
        </a>
        <a href="{{ route('call-actuals.index') }}" class="mobile-nav-item {{ request()->routeIs('call-actuals.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
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
        <div style="max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <strong style="color: var(--secondary);">PT Mersifarma TM</strong>: <em style="color: var(--primary);">"Good Quality Improves Life's Quality"</em>
            </div>
            <div style="color: var(--text-muted);">
                MSF Baseline v1.5 &bull; Mobile Sales Force System
            </div>
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
