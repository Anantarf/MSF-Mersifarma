@extends('layouts.app')

@section('title', 'Login')

@push('styles')
<style>
    /* ==========================================================================
       Page Container & Background
       ========================================================================== */
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

    /* Suppress global static alert outside card */
    main > .alert {
        display: none !important;
    }

    /* Transparent centered footer */
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

    /* Prevent browser autofill background discoloration */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0px 1000px #ffffff inset !important;
        -webkit-text-fill-color: var(--text-main) !important;
    }

    /* Suppress caret cursor on non-editable elements */
    *:not(input):not(textarea) {
        caret-color: transparent !important;
        -webkit-user-select: none;
        user-select: none;
    }

    input, textarea {
        caret-color: var(--primary) !important;
        cursor: text !important;
        user-select: auto !important;
        -webkit-user-select: auto !important;
    }

    /* ==========================================================================
       Top Floating Toast Notification
       ========================================================================== */
    .top-toast-banner {
        position: fixed;
        top: 24px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 99999;
        max-width: 440px;
        width: calc(100% - 32px);
        padding: 0.85rem 1.15rem;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 16px 36px rgba(12, 35, 64, 0.35);
        animation: slideDown 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        font-size: 0.85rem;
        font-weight: 600;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .top-toast-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-left: 5px solid #16a34a;
        color: #15803d;
    }

    .top-toast-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-left: 5px solid #dc2626;
        color: #b91c1c;
    }

    .top-toast-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .top-toast-text {
        flex: 1;
        line-height: 1.4;
    }

    .top-toast-close {
        margin-left: auto;
        border: none;
        background: none;
        font-size: 1.35rem;
        line-height: 1;
        cursor: pointer;
        color: inherit;
        opacity: 0.7;
        padding: 0 0.25rem;
        transition: opacity 0.15s ease;
    }

    .top-toast-close:hover {
        opacity: 1;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translate(-50%, -20px); }
        to { opacity: 1; transform: translate(-50%, 0); }
    }

    /* ==========================================================================
       Login Card & Form Elements
       ========================================================================== */
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

    .brand-header {
        text-align: center;
        margin-bottom: 1.35rem;
    }

    .brand-emblem-wrap {
        margin-bottom: 0.65rem;
    }

    .brand-emblem-img {
        width: 72px;
        height: 72px;
        object-fit: contain;
        display: inline-block;
    }

    .brand-app-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--secondary);
        letter-spacing: -0.02em;
        line-height: 1.2;
        margin-bottom: 0.3rem;
    }

    .brand-company-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.01em;
        margin-bottom: 0.25rem;
    }

    .brand-tagline {
        color: var(--primary);
        font-size: 0.75rem;
        font-weight: 500;
        font-style: italic;
    }

    /* Form Fields */
    .form-group {
        margin-bottom: 0.85rem;
    }

    .form-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
        color: var(--secondary);
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

    .password-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .password-input-wrap .input-control {
        padding-right: 2.5rem;
    }

    .password-toggle-btn {
        position: absolute;
        right: 0.65rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        padding: 0.25rem;
        cursor: pointer;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: color 0.15s ease;
    }

    .password-toggle-btn:hover {
        color: var(--primary);
    }

    .remember-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.15rem;
        font-size: 0.8rem;
    }

    .remember-label {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--text-muted);
        cursor: pointer;
        user-select: none;
    }

    .remember-checkbox {
        accent-color: var(--primary);
        cursor: pointer;
        width: 15px;
        height: 15px;
    }

    .btn-submit {
        width: 100%;
        min-height: 40px;
        font-size: 0.9rem;
        font-weight: 700;
        border-radius: 6px;
        letter-spacing: 0.02em;
    }

    /* ==========================================================================
       Floating Demo Account Launcher
       ========================================================================== */
    .floating-demo-wrap {
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 100;
    }

    .floating-demo-trigger {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        background: #ffffff;
        color: var(--secondary);
        border: 1px solid var(--border-strong);
        padding: 0.45rem 0.85rem;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 700;
        box-shadow: 0 4px 14px rgba(12, 35, 64, 0.25);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .floating-demo-trigger:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(12, 35, 64, 0.3) !important;
    }

    .demo-popover {
        display: none;
        position: absolute;
        bottom: 50px;
        left: 0;
        width: 330px;
        max-width: calc(100vw - 40px);
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 0.9rem;
        box-shadow: 0 16px 36px rgba(12, 35, 64, 0.3);
        animation: popIn 0.15s ease-out;
    }

    .demo-popover-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.65rem;
        padding-bottom: 0.45rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .demo-close-btn {
        border: none;
        background: none;
        font-size: 1.25rem;
        color: var(--text-muted);
        cursor: pointer;
        line-height: 1;
        padding: 0 0.2rem;
    }

    .demo-btn-list {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
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

    @keyframes popIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<!-- Top Floating Notification Toast -->
@php
    $initialMessage = '';
    $toastType = 'info';

    if ($errors->any()) {
        $initialMessage = $errors->first();
        $toastType = 'error';
    } elseif (session('error')) {
        $initialMessage = session('error');
        $toastType = 'error';
    } elseif (session('success')) {
        $initialMessage = session('success');
        $toastType = 'success';
    }

    $shouldShow = !empty(trim((string)$initialMessage));
    $toastClass = $toastType === 'error' ? 'top-toast-error' : ($toastType === 'success' ? 'top-toast-success' : '');
@endphp

<div id="topNotificationBanner" class="top-toast-banner {{ $toastClass }}" style="display: {{ $shouldShow ? 'flex' : 'none' }};" role="alert">
    <div id="topNotificationIcon" class="top-toast-icon">
        <i class="bi {{ $toastType === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}" style="font-size: 1.15rem;"></i>
    </div>
    <div id="topNotificationText" class="top-toast-text">{{ $initialMessage }}</div>
    <button type="button" onclick="closeTopNotification()" class="top-toast-close" aria-label="Tutup">&times;</button>
</div>

<div class="login-card">
    <!-- Brand Header -->
    <div class="brand-header">
        <div class="brand-emblem-wrap">
            <img src="{{ asset('images/logo-mersifarma.png') }}" alt="Logo PT Mersifarma TM" class="brand-emblem-img">
        </div>
        <h1 class="brand-app-title">
            Mobile Sales Force
        </h1>
        <h2 class="brand-company-title">
            PT Mersifarma Tirmaku Mercusana
        </h2>
        <p class="brand-tagline">
            "Good Quality Improves Life's Quality"
        </p>
    </div>

    <!-- Authentication Form -->
    <form method="POST" action="{{ route('login.post') }}" id="loginForm" onsubmit="handleLoginSubmit(event)">
        @csrf
        <div class="form-group">
            <label for="username" class="form-label">Username atau Email</label>
            <input id="username" type="text" name="username" value="{{ old('username', old('email')) }}" required autofocus autocomplete="username"
                   class="input-control {{ $errors->has('email') || $errors->has('login') || $errors->has('username') ? 'is-invalid' : '' }}"
                   placeholder="Contoh: mr atau mr@mersi.com">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Kata Sandi</label>
            <div class="password-input-wrap">
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="input-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                       placeholder="••••••••">
                <button type="button" id="togglePasswordBtn" class="password-toggle-btn" onclick="togglePasswordVisibility()" aria-label="Tampilkan atau sembunyikan kata sandi" title="Tampilkan kata sandi">
                    <i id="passwordEyeIcon" class="bi bi-eye" style="font-size: 1.1rem;"></i>
                </button>
            </div>
        </div>

        <div class="remember-row">
            <label class="remember-label">
                <input type="checkbox" name="remember" id="remember" class="remember-checkbox">
                <span>Ingat saya di perangkat ini</span>
            </label>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-primary btn-submit">
            <span id="btnText">Masuk ke Aplikasi</span>
        </button>
    </form>
</div>

<!-- Floating Action Button Akun Demo -->
<div class="floating-demo-wrap">
    <div id="demoModal" class="demo-popover" role="dialog" aria-label="Pilihan Akun Demo">
        <div class="demo-popover-header">
            <div style="display: flex; align-items: center; gap: 0.45rem;">
                <i class="bi bi-people-fill" style="font-size: 1.05rem; color: var(--primary);"></i>
                <strong style="font-size: 0.8rem; color: var(--secondary);">Akun Demo Pengujian</strong>
            </div>
            <button type="button" onclick="toggleDemoModal()" aria-label="Tutup" class="demo-close-btn">&times;</button>
        </div>

        <p style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 0.6rem;">Pilih akun untuk mengisi otomatis:</p>

        <div class="demo-btn-list">
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

    <button type="button" id="demoAccountsTrigger" onclick="toggleDemoModal()" class="floating-demo-trigger" aria-haspopup="dialog">
        <i class="bi bi-people-fill" style="font-size: 1.05rem; color: var(--primary);"></i>
        <span>Akun Demo</span>
    </button>
</div>

<script>
    function toggleDemoModal() {
        const modal = document.getElementById('demoModal');
        modal.style.display = (modal.style.display === 'none' || modal.style.display === '') ? 'block' : 'none';
    }

    function fillAccount(account, password) {
        const usernameInput = document.getElementById('username') || document.getElementById('email');
        if (usernameInput) {
            usernameInput.value = account;
            usernameInput.classList.remove('is-invalid');
        }
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.value = password;
            passwordInput.type = 'password';
            passwordInput.classList.remove('is-invalid');
            const eyeIcon = document.getElementById('passwordEyeIcon');
            if (eyeIcon) eyeIcon.className = 'bi bi-eye';
            const btn = document.getElementById('togglePasswordBtn');
            if (btn) {
                btn.setAttribute('title', 'Tampilkan kata sandi');
                btn.setAttribute('aria-label', 'Tampilkan kata sandi');
            }
        }
        document.getElementById('demoModal').style.display = 'none';
    }

    function togglePasswordVisibility() {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('passwordEyeIcon');
        const btn = document.getElementById('togglePasswordBtn');
        if (!pwd || !icon) return;

        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.className = 'bi bi-eye-slash';
            if (btn) {
                btn.setAttribute('title', 'Sembunyikan kata sandi');
                btn.setAttribute('aria-label', 'Sembunyikan kata sandi');
            }
        } else {
            pwd.type = 'password';
            icon.className = 'bi bi-eye';
            if (btn) {
                btn.setAttribute('title', 'Tampilkan kata sandi');
                btn.setAttribute('aria-label', 'Tampilkan kata sandi');
            }
        }
    }

    async function handleLoginSubmit(event) {
        event.preventDefault();
        const btn = document.getElementById('submitBtn');
        const text = document.getElementById('btnText');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const rememberInput = document.getElementById('remember');

        usernameInput.classList.remove('is-invalid');
        passwordInput.classList.remove('is-invalid');

        if (btn && text) {
            btn.disabled = true;
            btn.style.opacity = '0.85';
            btn.style.cursor = 'wait';
            text.textContent = 'Memverifikasi...';
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch("{{ route('login.post') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    username: usernameInput.value,
                    password: passwordInput.value,
                    remember: rememberInput.checked
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showTopNotification('success', 'Login berhasil! Mengalihkan ke sistem...');
                if (btn && text) {
                    text.textContent = 'Mengalihkan...';
                }
                const redirectTarget = (data && data.redirect_url) ? data.redirect_url : "{{ route('call-lists.index') }}";
                setTimeout(() => {
                    window.location.href = redirectTarget;
                }, 700);
            } else {
                let errorMsg = 'Username atau kata sandi yang Anda masukkan salah.';
                if (data && data.message) {
                    errorMsg = data.message;
                } else if (data && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                        errorMsg = data.errors[firstKey][0];
                    }
                }
                showTopNotification('error', errorMsg);
                usernameInput.classList.add('is-invalid');
                passwordInput.classList.add('is-invalid');
                if (btn && text) {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    text.textContent = 'Masuk ke Aplikasi';
                }
            }
        } catch (error) {
            document.getElementById('loginForm').submit();
        }
    }

    let topToastTimer = null;

    function showTopNotification(type, message) {
        if (!message || typeof message !== 'string' || message.trim() === '') return;
        const banner = document.getElementById('topNotificationBanner');
        const text = document.getElementById('topNotificationText');
        const icon = document.getElementById('topNotificationIcon');
        if (!banner || !text) return;

        if (topToastTimer) {
            clearTimeout(topToastTimer);
            topToastTimer = null;
        }

        banner.className = 'top-toast-banner ' + (type === 'success' ? 'top-toast-success' : 'top-toast-error');
        text.textContent = message;
        banner.style.opacity = '1';
        banner.style.transform = 'translate(-50%, 0)';
        
        if (type === 'success') {
            icon.innerHTML = '<i class="bi bi-check-circle-fill" style="font-size: 1.15rem;"></i>';
            topToastTimer = setTimeout(() => {
                closeTopNotification();
            }, 4500);
        } else {
            icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="font-size: 1.15rem;"></i>';
        }

        banner.style.display = 'flex';
    }

    function closeTopNotification() {
        const banner = document.getElementById('topNotificationBanner');
        if (banner) {
            banner.style.opacity = '0';
            banner.style.transform = 'translate(-50%, -15px)';
            setTimeout(() => {
                banner.style.display = 'none';
            }, 200);
        }
    }

    // Safety guard & Auto dismiss on page load
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('topNotificationBanner');
        const text = document.getElementById('topNotificationText');
        if (banner) {
            const hasText = text && text.textContent.trim().length > 0;
            if (!hasText) {
                banner.style.display = 'none';
                return;
            }
            if (banner.classList.contains('top-toast-success')) {
                topToastTimer = setTimeout(() => {
                    closeTopNotification();
                }, 4500);
            }
        }
    });

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
