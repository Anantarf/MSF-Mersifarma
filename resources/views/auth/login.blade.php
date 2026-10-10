@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div style="max-width: 460px; margin: 2rem auto; background: white; border: 1px solid var(--border); border-top: 4px solid var(--primary); border-radius: 0.875rem; padding: 2.25rem 2rem; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);">
    <div style="text-align: center; margin-bottom: 1.75rem;">
        <div style="display: inline-flex; width: 48px; height: 48px; background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); border-radius: 0.75rem; align-items: center; justify-content: center; color: white; margin-bottom: 0.75rem; box-shadow: 0 4px 8px rgba(2, 132, 199, 0.3);">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/>
                <path d="m8.5 8.5 7 7"/>
            </svg>
        </div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--secondary); letter-spacing: -0.01em;">PT MERSIFARMA TM</h2>
        <p style="color: var(--primary); font-weight: 700; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; margin-top: 0.15rem;">Mobile Sales Force System</p>
        <p style="color: var(--text-muted); font-size: 0.75rem; font-style: italic; margin-top: 0.35rem;">"Good Quality Improves Life's Quality"</p>
    </div>

    <form method="POST" action="{{ route('login.post') }}" id="loginForm">
        @csrf
        <div style="margin-bottom: 1.15rem;">
            <label for="email" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--secondary);">Email Pengguna</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 0.5rem; font-size: 0.95rem; transition: border-color 0.15s ease, box-shadow 0.15s ease;"
                   placeholder="nama@mersi.com">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="password" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--secondary);">Kata Sandi</label>
            <input id="password" type="password" name="password" required
                   style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 0.5rem; font-size: 0.95rem; transition: border-color 0.15s ease, box-shadow 0.15s ease;"
                   placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; min-height: 46px; font-size: 1rem; font-weight: 700; border-radius: 0.5rem;">
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

<script>
    function fillAccount(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endsection
