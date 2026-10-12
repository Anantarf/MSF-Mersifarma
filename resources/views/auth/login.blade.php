@extends('layouts.app')

@section('title', 'Login')

@push('styles')
@vite('resources/css/pages/login.css')
@endpush

@section('content')
<!-- Top Floating Notification Toast -->
@php
    $initialMessage = '';
    $toastType = 'info';

    if ($errors->any()) {
        $initialMessage = $errors->first();
        $toastType = 'error';
    } elseif (request('expired') === 'session') {
        $initialMessage = 'Sesi sudah kedaluwarsa. Silakan masuk kembali.';
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

<div id="topNotificationBanner" class="top-toast-banner {{ $toastClass }} {{ $shouldShow ? 'is-visible' : '' }}" role="alert">
    <div id="topNotificationIcon" class="top-toast-icon">
        <i class="bi {{ $toastType === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"></i>
    </div>
    <div id="topNotificationText" class="top-toast-text">{{ $initialMessage }}</div>
    <button type="button" data-action="close-toast" class="top-toast-close" aria-label="Tutup">&times;</button>
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
    <form method="POST" action="{{ route('login.post') }}" id="loginForm" data-redirect-url="{{ route('call-lists.index') }}">
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
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                <button type="button" id="togglePasswordBtn" class="password-toggle-btn" data-action="toggle-password" aria-label="Tampilkan atau sembunyikan kata sandi" title="Tampilkan kata sandi">
                    <i id="passwordEyeIcon" class="bi bi-eye password-eye-icon"></i>
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
            <div class="demo-header-title">
                <i class="bi bi-people-fill demo-header-icon"></i>
                <strong class="demo-title-text">Akun Demo Pengujian</strong>
            </div>
            <button type="button" data-action="toggle-demo" aria-label="Tutup" class="demo-close-btn">&times;</button>
        </div>

        <p class="demo-help">Pilih akun untuk mengisi otomatis:</p>

        <div class="demo-btn-list">
            <button type="button" class="btn btn-outline demo-btn" data-account="mm@mersi.com" data-password="password">
                <div class="demo-btn-info">
                    <strong class="demo-btn-name">Budi Santoso</strong>
                    <span class="demo-btn-subtitle">Marketing Manager Nasional</span>
                </div>
                <span class="role-pill role-mm">MM</span>
            </button>
            <button type="button" class="btn btn-outline demo-btn" data-account="rsm@mersi.com" data-password="password">
                <div class="demo-btn-info">
                    <strong class="demo-btn-name">Siti Rahma</strong>
                    <span class="demo-btn-subtitle">Regional Sales Manager DKI Jakarta</span>
                </div>
                <span class="role-pill role-rsm">RSM</span>
            </button>
            <button type="button" class="btn btn-outline demo-btn" data-account="dm@mersi.com" data-password="password">
                <div class="demo-btn-info">
                    <strong class="demo-btn-name">Joko Widodo</strong>
                    <span class="demo-btn-subtitle">District Manager Jakarta Selatan</span>
                </div>
                <span class="role-pill role-dm">DM</span>
            </button>
            <button type="button" class="btn btn-outline demo-btn" data-account="dm2@mersi.com" data-password="password">
                <div class="demo-btn-info">
                    <strong class="demo-btn-name">Hendra Gunawan</strong>
                    <span class="demo-btn-subtitle">District Manager Jakarta Timur</span>
                </div>
                <span class="role-pill role-dm">DM</span>
            </button>
            <button type="button" class="btn btn-outline demo-btn" data-account="mr@mersi.com" data-password="password">
                <div class="demo-btn-info">
                    <strong class="demo-btn-name">Rudi Hartono</strong>
                    <span class="demo-btn-subtitle">MR Jakarta Selatan</span>
                </div>
                <span class="role-pill role-mr">MR</span>
            </button>
            <button type="button" class="btn btn-outline demo-btn" data-account="mr3@mersi.com" data-password="password">
                <div class="demo-btn-info">
                    <strong class="demo-btn-name">Nadia Putri</strong>
                    <span class="demo-btn-subtitle">MR Jakarta Timur</span>
                </div>
                <span class="role-pill role-mr">MR</span>
            </button>
        </div>
    </div>

    <button type="button" id="demoAccountsTrigger" data-action="toggle-demo" class="floating-demo-trigger" aria-haspopup="dialog">
        <i class="bi bi-people-fill demo-header-icon"></i>
        <span>Akun Demo</span>
    </button>
</div>

@push('scripts')
@vite('resources/js/pages/login.js')
@endpush
@endsection
