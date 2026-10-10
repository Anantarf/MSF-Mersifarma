@extends('layouts.app')

@section('title', 'Login')

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, rgba(12, 35, 64, 0.75) 0%, rgba(12, 77, 161, 0.70) 100%),
                    url('{{ asset("images/mersifarma-facility.jpg") }}') center center / cover no-repeat fixed !important;
        min-height: 100vh;
        overflow-y: auto;
    }

    main {
        padding: 1rem !important;
        display: flex;
        flex-direction: column !important;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 55px);
    }

    /* Sembunyikan alert global di luar card agar form tetap rapi di tengah */
    main > .alert {
        display: none !important;
    }

    /* Footer transparan terpusat di tengah layar */
    footer {
        background: transparent !important;
        border-top: none !important;
        padding: 0.65rem 1rem 0.85rem !important;
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.85) !important;
        text-shadow: 0 1px 4px rgba(12, 35, 64, 0.85);
        text-align: center !important;
    }
    footer div {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    /* Hilangkan background kebiruan akibat browser autofill */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0px 1000px #ffffff inset !important;
        -webkit-text-fill-color: var(--text-main) !important;
    }

    .login-card {
        width: 100%;
        max-width: 400px;
        background: #ffffff;
        border-radius: 12px;
        padding: 1.75rem 1.75rem 1.5rem;
        box-shadow: 0 20px 45px rgba(12, 35, 64, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-top: 4px solid var(--primary);
    }

    /* Mencegah kursor caret/text-box berkedip pada teks statis */
    .login-card * {
        caret-color: transparent;
    }
    .login-card h1,
    .login-card h2,
    .login-card p,
    .login-card label,
    .login-card span,
    .login-card img {
        user-select: none;
        -webkit-user-select: none;
        cursor: default;
    }
    .login-card input {
        caret-color: var(--primary) !important;
        cursor: text !important;
        user-select: text !important;
        -webkit-user-select: text !important;
    }

    .input-control {
        width: 100%;
        padding: 0.55rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.875rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .input-control.is-invalid {
        border-color: var(--danger) !important;
        background-color: #fffaf0;
    }

    .demo-btn {
        justify-content: space-between;
        font-size: 0.8rem;
        padding: 0.45rem 0.65rem;
        min-height: 38px;
        border-radius: 0.375rem;
        width: 100%;
        text-align: left;
    }
    .demo-btn:hover {
        background: #f1f5f9;
        border-color: var(--primary);
    }

    #demoAccountsTrigger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(12, 35, 64, 0.3) !important;
    }

    @keyframes popIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<div class="login-card">
    <!-- Login Card Branding Header (Refined Visual Hierarchy) -->
    <div style="text-align: center; margin-bottom: 1.35rem;">
        <!-- Logo Emblem Proporsional (Center) -->
        <div style="margin-bottom: 0.65rem;">
            <img src="{{ asset('images/logo-mersifarma.png') }}" alt="Logo PT Mersifarma TM" style="width: 72px; height: 72px; object-fit: contain; display: inline-block;">
        </div>
        <!-- Mobile Sales Force -->
        <h1 style="font-size: 1.35rem; font-weight: 800; color: var(--secondary); letter-spacing: -0.02em; line-height: 1.2; margin-bottom: 0.3rem;">
            Mobile Sales Force
        </h1>
        <!-- PT Mersifarma Tirmaku Mercusana -->
        <h2 style="font-size: 0.85rem; font-weight: 700; color: #1e293b; letter-spacing: -0.01em; margin-bottom: 0.25rem;">
            PT Mersifarma Tirmaku Mercusana
        </h2>
        <!-- Slogan -->
        <p style="color: var(--primary); font-size: 0.75rem; font-weight: 500; font-style: italic;">
            "Good Quality Improves Life's Quality"
        </p>
    </div>

    <!-- Error Alert Box di dalam Card -->
    @if ($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid var(--danger); border-radius: 6px; padding: 0.6rem 0.75rem; margin-bottom: 1rem; font-size: 0.8rem; color: #991b1b; text-align: left;">
            <div style="display: flex; align-items: center; gap: 0.35rem; font-weight: 700; margin-bottom: 0.2rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span>Gagal Masuk</span>
            </div>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    @if (session('success'))
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid var(--success); border-radius: 6px; padding: 0.6rem 0.75rem; margin-bottom: 1rem; font-size: 0.8rem; color: #166534; text-align: left;">
            {{ session('success') }}
        </div>
    @endif

    <!-- Login Form -->
    <form method="POST" action="{{ route('login.post') }}" id="loginForm" onsubmit="handleLoginSubmit()">
        @csrf
        <div style="margin-bottom: 0.9rem;">
            <label for="email" style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">Email Pengguna</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="input-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                   placeholder="nama@mersi.com">
        </div>

        <div style="margin-bottom: 0.85rem;">
            <label for="password" style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">Kata Sandi</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="input-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                   placeholder="••••••••">
        </div>

        <!-- Opsi Ingat Saya (Remember Me) -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.15rem; font-size: 0.8rem;">
            <label style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--text-muted); cursor: pointer; user-select: none;">
                <input type="checkbox" name="remember" id="remember" style="accent-color: var(--primary); cursor: pointer; width: 15px; height: 15px;">
                <span>Ingat saya di perangkat ini</span>
            </label>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-primary" style="width: 100%; min-height: 40px; font-size: 0.9rem; font-weight: 700; border-radius: 6px; letter-spacing: 0.02em;">
            <span id="btnText">Masuk ke Aplikasi</span>
        </button>
    </form>
</div>

<!-- Floating Action Button Akun Demo di Pojok Kiri Bawah -->
<div style="position: fixed; bottom: 20px; left: 20px; z-index: 100;">
    <!-- Popover Menu Akun Demo -->
    <div id="demoModal" style="display: none; position: absolute; bottom: 50px; left: 0; width: 330px; max-width: calc(100vw - 40px); background: white; border: 1px solid var(--border); border-radius: 10px; padding: 0.9rem; box-shadow: 0 16px 36px rgba(12, 35, 64, 0.3); animation: popIn 0.15s ease-out;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem; padding-bottom: 0.45rem; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 0.45rem;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <strong style="font-size: 0.8rem; color: var(--secondary);">Akun Demo Pengujian</strong>
            </div>
            <button type="button" onclick="toggleDemoModal()" aria-label="Tutup" style="border: none; background: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer; line-height: 1; padding: 0 0.2rem;">&times;</button>
        </div>

        <p style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 0.6rem;">Pilih akun untuk mengisi otomatis:</p>

        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
            <button type="button" class="btn btn-outline demo-btn" onclick="fillAccount('mr@mersi.com', 'password')">
                <span><strong>Rudi Hartono</strong> <span style="color: var(--text-muted); font-size: 0.75rem;">(MR Area 1)</span></span>
                <span class="role-pill role-mr">MR</span>
            </button>
            <button type="button" class="btn btn-outline demo-btn" onclick="fillAccount('dm@mersi.com', 'password')">
                <span><strong>Joko Widodo</strong> <span style="color: var(--text-muted); font-size: 0.75rem;">(DM Atasan)</span></span>
                <span class="role-pill role-dm">DM</span>
            </button>
            <button type="button" class="btn btn-outline demo-btn" onclick="fillAccount('rsm@mersi.com', 'password')">
                <span><strong>Siti Rahma</strong> <span style="color: var(--text-muted); font-size: 0.75rem;">(Atasan DM)</span></span>
                <span class="role-pill role-rsm">RSM</span>
            </button>
            <button type="button" class="btn btn-outline demo-btn" onclick="fillAccount('mm@mersi.com', 'password')">
                <span><strong>Budi Santoso</strong> <span style="color: var(--text-muted); font-size: 0.75rem;">(National MM)</span></span>
                <span class="role-pill role-mm">MM</span>
            </button>
            <button type="button" class="btn btn-outline demo-btn" onclick="fillAccount('dm2@mersi.com', 'password')">
                <span><strong>Hendra Gunawan</strong> <span style="color: var(--text-muted); font-size: 0.75rem;">(DM Non-Atasan)</span></span>
                <span class="role-pill role-dm">DM</span>
            </button>
        </div>
    </div>

    <!-- Tombol Trigger Icon Profile -->
    <button type="button" id="demoAccountsTrigger" onclick="toggleDemoModal()"
            style="display: flex; align-items: center; gap: 0.45rem; background: #ffffff; color: var(--secondary); border: 1px solid var(--border); padding: 0.45rem 0.85rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; box-shadow: 0 4px 14px rgba(12, 35, 64, 0.25); cursor: pointer; transition: all 0.15s ease;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        <span>Akun Demo</span>
    </button>
</div>

<script>
    function toggleDemoModal() {
        const modal = document.getElementById('demoModal');
        modal.style.display = (modal.style.display === 'none' || modal.style.display === '') ? 'block' : 'none';
    }

    function fillAccount(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
        document.getElementById('demoModal').style.display = 'none';
    }

    function handleLoginSubmit() {
        const btn = document.getElementById('submitBtn');
        const text = document.getElementById('btnText');
        if (btn && text) {
            btn.disabled = true;
            btn.style.opacity = '0.8';
            btn.style.cursor = 'wait';
            text.textContent = 'Memverifikasi...';
        }
    }

    // Close popover if clicked outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('demoModal');
        const trigger = document.getElementById('demoAccountsTrigger');
        if (modal && trigger && !modal.contains(event.target) && !trigger.contains(event.target)) {
            modal.style.display = 'none';
        }
    });
</script>
@endsection
