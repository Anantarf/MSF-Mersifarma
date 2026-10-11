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
    <style>
        :root {
            --primary: #0c4da1;          /* Authentic Mersifarma Blue */
            --primary-hover: #083c80;    /* Deep Mersi Blue hover */
            --primary-subtle: #f0f6fc;   /* Mersi Blue subtle tint */
            --primary-border: #bfdbfe;   /* Crisp blue border */
            --secondary: #0c2340;        /* Mersifarma Deep Corporate Navy */
            --secondary-hover: #08182d;
            --surface: #ffffff;
            --background: #f8fafc;
            --border: #e2e8f0;           /* Clean enterprise border */
            --border-strong: #cbd5e1;
            --text-main: #0f172a;        /* Slate 900 */
            --text-muted: #64748b;       /* Slate 500 */
            --text-subtle: #94a3b8;      /* Slate 400 */
            --success: #059669;          /* Emerald */
            --danger: #dc2626;           /* Red */
            --warning: #d97706;          /* Amber */
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4, .brand-text {
            color: var(--secondary);
            font-weight: 700;
            letter-spacing: -0.02em;
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
            font-size: 0.875rem;
            line-height: 1.5;
        }

        header {
            background: var(--surface);
            border-top: 3px solid var(--primary);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.04);
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
            line-height: 1.15;
        }

        .brand-wordmark {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.035em;
            line-height: 1;
            text-transform: lowercase;
            display: inline-block;
        }

        .brand-sub {
            font-size: 0.62rem;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.5rem 0.85rem;
            border-radius: 5px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.15s ease, background-color 0.15s ease;
        }

        .nav-link:hover {
            color: var(--primary);
            background: var(--primary-subtle);
        }

        .nav-link.active {
            color: var(--primary);
            background: var(--primary-subtle);
            border-bottom: 2px solid var(--primary);
            font-weight: 700;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-profile-badge {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1.25;
            text-align: right;
        }

        .user-profile-top {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .user-profile-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--secondary);
        }

        .user-profile-subtitle {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .role-pill, .role-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            line-height: 1.2;
            vertical-align: middle;
        }

        .role-mr  { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .role-dm  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .role-rsm { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .role-mm  { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0.5rem 0.95rem;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            text-decoration: none;
            line-height: 1.2;
            white-space: nowrap;
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
            border-color: var(--border-strong);
            color: var(--text-main);
        }
        .btn-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .btn-sm {
            min-height: 32px;
            padding: 0.25rem 0.65rem;
            font-size: 0.8rem;
        }

        input, select, textarea {
            border: 1px solid var(--border-strong);
            border-radius: 5px;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            color: var(--text-main);
            background: #ffffff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary) !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(12, 77, 161, 0.18) !important;
        }

        button:focus-visible, a:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 4px;
            border: 1px solid transparent;
            line-height: 1.2;
            white-space: nowrap;
        }

        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .status-approved, .status-completed {
            background: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .status-approved::before, .status-completed::before {
            background: #10b981;
        }

        .status-pending {
            background: #fffbeb;
            color: #92400e;
            border-color: #fde68a;
        }
        .status-pending::before {
            background: #f59e0b;
        }

        .status-draft, .status-scheduled {
            background: #f1f5f9;
            color: #334155;
            border-color: #cbd5e1;
        }
        .status-draft::before, .status-scheduled::before {
            background: #64748b;
        }

        .status-rejected, .status-cancelled {
            background: #fff1f2;
            color: #9f1239;
            border-color: #fecdd3;
        }
        .status-rejected::before, .status-cancelled::before {
            background: #f43f5e;
        }

        /* Tabs */
        .tab-btn {
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.15s ease, border-color 0.15s ease;
            margin-bottom: -2px;
            min-height: 44px;
        }

        .tab-btn:hover {
            color: var(--text-main);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            font-weight: 700;
        }

        /* Enterprise Table */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03);
        }

        .enterprise-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
        }

        .enterprise-table th {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid var(--border);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        .enterprise-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-main);
            vertical-align: middle;
            line-height: 1.45;
        }

        .enterprise-table tr:last-child td {
            border-bottom: none;
        }

        .enterprise-table tbody tr:hover {
            background: #f8fafc;
        }

        /* Team Monitoring Roster List */
        .team-roster-list {
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: #ffffff;
            overflow: hidden;
        }

        .team-roster-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            gap: 0.85rem;
            transition: background-color 0.15s ease;
        }

        .team-roster-item:last-child {
            border-bottom: none;
        }

        .team-roster-item:hover {
            background-color: #f8fafc;
        }

        .team-roster-user {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0.15rem;
        }

        .team-roster-name-wrap {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .team-roster-name {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--secondary);
        }

        .team-roster-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .team-roster-meta {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-shrink: 0;
        }

        .team-roster-count {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .team-roster-num {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.2;
        }

        .team-roster-label {
            font-size: 0.6875rem;
            color: var(--text-muted);
        }

        .team-roster-status {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.8rem;
            font-weight: 600;
            min-width: 140px;
            justify-content: flex-start;
        }

        .team-roster-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .team-roster-status.status-approved { color: #047857; }
        .team-roster-status.status-approved .team-roster-dot { background: #10b981; }

        .team-roster-status.status-pending { color: #b45309; }
        .team-roster-status.status-pending .team-roster-dot { background: #f59e0b; }

        .team-roster-status.status-draft { color: #b91c1c; }
        .team-roster-status.status-draft .team-roster-dot { background: #ef4444; }

        .team-roster-status.status-none { color: #64748b; }
        .team-roster-status.status-none .team-roster-dot { background: #94a3b8; }

        @media (max-width: 640px) {
            .team-roster-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .team-roster-meta {
                width: 100%;
                justify-content: space-between;
                gap: 0.5rem;
                padding-top: 0.4rem;
                border-top: 1px dashed #f1f5f9;
            }
        }

        /* Enterprise Modals */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--text-muted);
            cursor: pointer;
            line-height: 1;
            padding: 0.25rem;
            border-radius: 4px;
            transition: color 0.15s ease;
        }

        .close-btn:hover {
            color: var(--text-main);
        }

        main {
            flex: 1;
            max-width: 1140px;
            width: 100%;
            margin: 0 auto;
            padding: 1.75rem 1rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 5px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            line-height: 1.45;
        }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger  { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

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
            .user-profile-subtitle {
                display: none;
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

        /* Global Floating Toast System */
        .toast-container {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            pointer-events: none;
            max-width: 380px;
            width: calc(100vw - 2rem);
        }

        .toast {
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid var(--border);
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
            font-size: 0.85rem;
            line-height: 1.4;
            color: var(--text-main);
            transform: translateY(-8px);
            opacity: 0;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .toast.toast-show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.toast-hide {
            transform: translateY(-12px);
            opacity: 0;
        }

        .toast-icon {
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .toast-success {
            border-left: 4px solid var(--success);
        }
        .toast-success .toast-icon {
            color: var(--success);
        }

        .toast-danger, .toast-error {
            border-left: 4px solid var(--danger);
        }
        .toast-danger .toast-icon, .toast-error .toast-icon {
            color: var(--danger);
        }

        .toast-warning {
            border-left: 4px solid #f59e0b;
        }
        .toast-warning .toast-icon {
            color: #f59e0b;
        }

        .toast-info {
            border-left: 4px solid var(--primary);
        }
        .toast-info .toast-icon {
            color: var(--primary);
        }

        .toast-message {
            flex: 1;
            font-weight: 500;
        }

        .toast-close {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.15rem;
            padding: 0.1rem;
            line-height: 1;
            transition: color 0.15s ease;
        }

        .toast-close:hover {
            color: var(--text-main);
        }

        @media (max-width: 640px) {
            .toast-container {
                top: auto;
                bottom: 4.5rem;
                right: 1rem;
                left: 1rem;
                width: auto;
            }
        }

        /* Skeleton Shimmer Loading */
        .skeleton-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1.25rem 1rem;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .skeleton-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .skeleton-box {
            height: 16px;
            border-radius: 4px;
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Modal Quick Search Filter */
        .modal-filter-wrap {
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 0.65rem;
        }

        .modal-filter-wrap i {
            position: absolute;
            left: 0.75rem;
            color: #94a3b8;
            font-size: 0.85rem;
            pointer-events: none;
        }

        .modal-filter-input {
            width: 100%;
            padding: 0.45rem 0.75rem 0.45rem 2.15rem;
            border: 1px solid var(--border-strong);
            border-radius: 5px;
            font-size: 0.825rem;
            transition: all 0.15s ease;
        }

        .modal-filter-input:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 2px rgba(12, 77, 161, 0.15) !important;
        }
    </style>
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
                <a href="{{ route('call-lists.index') }}" class="nav-link {{ request()->routeIs('call-lists.*') ? 'active' : '' }}" title="Call List Bulanan">
                    Target Dokter
                </a>
                <a href="{{ route('call-plans.index') }}" class="nav-link {{ request()->routeIs('call-plans.*') ? 'active' : '' }}" title="Call Plan">
                    Jadwal Kunjungan
                </a>
                <a href="{{ route('call-actuals.index') }}" class="nav-link {{ request()->routeIs('call-actuals.*') ? 'active' : '' }}" title="Call Actual">
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
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="min-height: 40px; padding: 0.35rem 0.75rem; font-size: 0.82rem;">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <nav class="mobile-bottom-nav">
        <a href="{{ route('call-lists.index') }}" class="mobile-nav-item {{ request()->routeIs('call-lists.*') ? 'active' : '' }}" title="Target Dokter (Call List Bulanan)">
            <i class="bi bi-clipboard-check" style="font-size: 1.25rem;"></i>
            <span>Target</span>
        </a>
        <a href="{{ route('call-plans.index') }}" class="mobile-nav-item {{ request()->routeIs('call-plans.*') ? 'active' : '' }}" title="Jadwal Kunjungan (Call Plan)">
            <i class="bi bi-calendar-event" style="font-size: 1.25rem;"></i>
            <span>Jadwal</span>
        </a>
        <a href="{{ route('call-actuals.index') }}" class="mobile-nav-item {{ request()->routeIs('call-actuals.*') ? 'active' : '' }}" title="Pelaksanaan Kunjungan (Call Actual)">
            <i class="bi bi-check2-circle" style="font-size: 1.25rem;"></i>
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

        // Global Toast Notification Helper
        window.showToast = function(message, type = 'success', duration = 3500) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            const safeType = ['success', 'danger', 'error', 'warning', 'info'].includes(type) ? type : 'info';
            toast.className = `toast toast-${safeType}`;

            let icon = 'bi-check-circle-fill';
            if (safeType === 'danger' || safeType === 'error') icon = 'bi-x-circle-fill';
            else if (safeType === 'warning') icon = 'bi-exclamation-triangle-fill';
            else if (safeType === 'info') icon = 'bi-info-circle-fill';

            toast.innerHTML = `
                <i class="bi ${icon} toast-icon"></i>
                <div class="toast-message">${escapeHtmlToast(message)}</div>
                <button type="button" class="toast-close" aria-label="Tutup">&times;</button>
            `;

            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.add('toast-show');
            });

            const closeBtn = toast.querySelector('.toast-close');
            let timeoutId;

            const removeToast = () => {
                clearTimeout(timeoutId);
                toast.classList.remove('toast-show');
                toast.classList.add('toast-hide');
                setTimeout(() => toast.remove(), 250);
            };

            closeBtn.addEventListener('click', removeToast);
            timeoutId = setTimeout(removeToast, duration);
        };

        function escapeHtmlToast(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Global Table Skeleton Generator Helper
        window.renderTableSkeleton = function(rows = 3) {
            let html = '<div class="skeleton-wrap">';
            for (let i = 0; i < rows; i++) {
                html += `
                    <div class="skeleton-row">
                        <div class="skeleton-box" style="width: 25%;"></div>
                        <div class="skeleton-box" style="width: 35%;"></div>
                        <div class="skeleton-box" style="width: 20%;"></div>
                        <div class="skeleton-box" style="width: 20%;"></div>
                    </div>
                `;
            }
            html += '</div>';
            return html;
        };

        // Native Network Status Listeners (Efficient & Friendly)
        window.addEventListener('online', () => {
            window.showToast('Koneksi internet terhubung kembali.', 'info', 3000);
        });
        window.addEventListener('offline', () => {
            window.showToast('Koneksi internet terputus. Mode offline aktif, draf tersimpan lokal.', 'warning', 4500);
        });
    </script>
    @stack('scripts')
</body>
</html>
