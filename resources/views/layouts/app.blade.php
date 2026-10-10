<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MSF') : Mobile Sales Force PT Mersifarma</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {            --primary: #0f766e;
            --primary-hover: #115e59;
            --primary-light: #f0fdfa;
            --secondary: #0f172a;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #d97706;
            --surface: #ffffff;
            --background: #f8fafc;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
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
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 40;
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
            gap: 0.5rem;
            text-decoration: none;
            color: var(--secondary);
            font-weight: 700;
            font-size: 1.15rem;
        }

        .brand-badge {
            background: var(--primary);
            color: white;
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary);
            background: var(--primary-light);
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

        .role-mr { background: #dbeafe; color: #1e40af; }
        .role-dm { background: #fef3c7; color: #92400e; }
        .role-rsm { background: #dcfce7; color: #166534; }
        .role-mm { background: #f3e8ff; color: #6b21a8; }        .btn {
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
        }        button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
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

        @media (max-width: 640px) {
            .nav-container {
                flex-direction: column;
                gap: 0.75rem;
                align-items: stretch;
            }
            .nav-links {
                justify-content: space-around;
            }
            .user-menu {
                justify-content: space-between;
                border-top: 1px solid var(--border);
                padding-top: 0.5rem;
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
                <span>MSF Mersifarma</span>
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
                    Call Actual                </a>
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
