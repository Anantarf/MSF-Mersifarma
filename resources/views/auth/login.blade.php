@extends('layouts.app')

@section('title', 'Login')

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, rgba(12, 35, 64, 0.78) 0%, rgba(12, 77, 161, 0.72) 100%),
                    url('{{ asset("images/mersifarma-facility.jpg") }}') center center / cover no-repeat fixed !important;
        min-height: 100vh;
    }
    footer {
        background: rgba(255, 255, 255, 0.92) !important;
        backdrop-filter: blur(8px);
    }
</style>
@endpush

@section('content')
<div style="max-width: 440px; margin: 1.5rem auto 2.5rem; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 45px rgba(12, 35, 64, 0.28);">
    <!-- Background foto gedung fasilitas Mersifarma di belakang logo -->
    <div style="position: relative; height: 130px; background: url('{{ asset('images/mersifarma-facility.jpg') }}') center 32% / cover no-repeat;">
        <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(12, 35, 64, 0.2), rgba(12, 35, 64, 0.65));"></div>
    </div>

    <div style="padding: 0 2rem 2.25rem;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <!-- Badge logo Mersifarma mengapung di depan foto gedung -->
            <div style="margin-top: -38px; position: relative; z-index: 2; margin-bottom: 0.65rem;">
                <div style="display: inline-flex; width: 76px; height: 76px; background: white; border-radius: 16px; align-items: center; justify-content: center; box-shadow: 0 6px 18px rgba(12, 35, 64, 0.2); border: 3px solid white;">
                    <img src="{{ asset('images/logo-mersifarma.png') }}" alt="Logo PT Mersifarma TM" style="width: 52px; height: 52px; object-fit: contain;">
                </div>
            </div>
            <div style="font-family: 'Nunito', sans-serif; font-size: 2.35rem; font-weight: 900; color: var(--primary); letter-spacing: -0.04em; line-height: 1; text-transform: lowercase; margin-bottom: 0.25rem;">
                mersi
            </div>
            <h2 style="font-size: 0.95rem; font-weight: 800; color: var(--secondary); letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 0.15rem;">
                PT MERSIFARMA TIRMAKU MERCUSANA
            </h2>
            <p style="color: var(--primary); font-weight: 700; font-size: 0.78rem; letter-spacing: 0.06em; text-transform: uppercase;">
                Mobile Sales Force (MSF)
            </p>
            <p style="color: var(--text-muted); font-size: 0.75rem; font-style: italic; margin-top: 0.35rem;">
                "Good Quality Improves Life's Quality"
            </p>
        </div>

    <form method="POST" action="{{ route('login.post') }}" id="loginForm">
        @csrf
        <div style="margin-bottom: 1.15rem;">
            <label for="email" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--secondary);">Email Pengguna</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem;"
                   placeholder="nama@mersi.com">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="password" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--secondary);">Kata Sandi</label>
            <input id="password" type="password" name="password" required
                   style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem;"
                   placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; min-height: 42px; font-size: 0.95rem; font-weight: 700; border-radius: 6px;">
            Masuk ke Aplikasi
        </button>
    </form>

    <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px dashed var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
            <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.04em;">
                Akun Demo Pengujian:
            </span>
            <span style="font-size: 0.7rem; color: var(--primary); font-weight: 600;">1-Klik Langsung Isi</span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.45rem;">
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px; border-radius: 0.45rem;"
                    onclick="fillAccount('mr@mersi.com', 'password')">
                <span><strong>Rudi Hartono</strong> <span style="color: var(--text-muted); font-size: 0.8rem;">(MR Area 1)</span></span>
                <span class="role-pill role-mr">MR</span>
            </button>
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px; border-radius: 0.45rem;"
                    onclick="fillAccount('dm@mersi.com', 'password')">
                <span><strong>Joko Widodo</strong> <span style="color: var(--text-muted); font-size: 0.8rem;">(Atasan Langsung MR)</span></span>
                <span class="role-pill role-dm">DM</span>
            </button>
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px; border-radius: 0.45rem;"
                    onclick="fillAccount('rsm@mersi.com', 'password')">
                <span><strong>Siti Rahma</strong> <span style="color: var(--text-muted); font-size: 0.8rem;">(Atasan DM)</span></span>
                <span class="role-pill role-rsm">RSM</span>
            </button>
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px; border-radius: 0.45rem;"
                    onclick="fillAccount('mm@mersi.com', 'password')">
                <span><strong>Budi Santoso</strong> <span style="color: var(--text-muted); font-size: 0.8rem;">(National MM)</span></span>
                <span class="role-pill role-mm">MM</span>
            </button>
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px; border-radius: 0.45rem;"
                    onclick="fillAccount('dm2@mersi.com', 'password')">
                <span><strong>Hendra Gunawan</strong> <span style="color: var(--text-muted); font-size: 0.8rem;">(DM Non-Atasan / PDF-05)</span></span>
                <span class="role-pill role-dm">DM</span>
            </button>
        </div>
    </div>
    </div>
</div>

<script>
    function fillAccount(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endsection
