@extends('layouts.app')

@section('title', 'Login')

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, rgba(12, 35, 64, 0.78) 0%, rgba(12, 77, 161, 0.72) 100%),
                    url('{{ asset("images/mersifarma-facility.jpg") }}') center center / cover no-repeat fixed !important;
        min-height: 100vh;
        overflow-y: auto;
    }

    main {
        padding: 1rem !important;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 65px);
    }

    footer {
        background: rgba(255, 255, 255, 0.92) !important;
        backdrop-filter: blur(8px);
        padding: 0.65rem 1rem !important;
        font-size: 0.75rem !important;
    }

    .login-card {
        width: 100%;
        max-width: 400px;
        background: #ffffff;
        border-radius: 12px;
        padding: 1.75rem 1.75rem 1.5rem;
        box-shadow: 0 16px 36px rgba(12, 35, 64, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-top: 4px solid var(--primary);
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
<div class="login-card">
    <!-- Compact Mersifarma Branding Header -->
    <div style="text-align: center; margin-bottom: 1.25rem;">
        <div style="display: inline-flex; align-items: center; justify-content: center; gap: 0.55rem; margin-bottom: 0.25rem;">
            <img src="{{ asset('images/logo-mersifarma.png') }}" alt="Logo PT Mersifarma TM" style="width: 42px; height: 42px; object-fit: contain;">
            <span style="font-family: 'Nunito', sans-serif; font-size: 2.2rem; font-weight: 900; color: var(--primary); letter-spacing: -0.04em; line-height: 1; text-transform: lowercase;">
                mersi
            </span>
        </div>
        <h2 style="font-size: 0.82rem; font-weight: 800; color: var(--secondary); letter-spacing: 0.04em; text-transform: uppercase;">
            PT MERSIFARMA TIRMAKU MERCUSANA
        </h2>
        <p style="color: var(--primary); font-weight: 700; font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase; margin-top: 0.1rem;">
            Mobile Sales Force (MSF)
        </p>
        <p style="color: var(--text-muted); font-size: 0.7rem; font-style: italic; margin-top: 0.25rem;">
            "Good Quality Improves Life's Quality"
        </p>
    </div>

    <!-- Login Form -->
    <form method="POST" action="{{ route('login.post') }}" id="loginForm">
        @csrf
        <div style="margin-bottom: 0.9rem;">
            <label for="email" style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">Email Pengguna</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem;"
                   placeholder="nama@mersi.com">
        </div>

        <div style="margin-bottom: 1.15rem;">
            <label for="password" style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">Kata Sandi</label>
            <input id="password" type="password" name="password" required
                   style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem;"
                   placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; min-height: 40px; font-size: 0.9rem; font-weight: 700; border-radius: 6px;">
            Masuk ke Aplikasi
        </button>
    </form>
</div>

<!-- Floating Action Button Akun Demo di Pojok Kiri Bawah -->
<div style="position: fixed; bottom: 20px; left: 20px; z-index: 100;">
    <!-- Popover Menu Akun Demo -->
    <div id="demoModal" style="display: none; position: absolute; bottom: 50px; left: 0; width: 330px; background: white; border: 1px solid var(--border); border-radius: 10px; padding: 0.9rem; box-shadow: 0 12px 30px rgba(12, 35, 64, 0.25); animation: popIn 0.15s ease-out;">
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
            style="display: flex; align-items: center; gap: 0.45rem; background: #ffffff; color: var(--secondary); border: 1px solid var(--border); padding: 0.45rem 0.85rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; box-shadow: 0 4px 14px rgba(12, 35, 64, 0.2); cursor: pointer; transition: transform 0.15s ease;">
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
