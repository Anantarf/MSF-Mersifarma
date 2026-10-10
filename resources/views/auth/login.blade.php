@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div style="max-width: 440px; margin: 2rem auto; background: white; border: 1px solid var(--border); border-radius: 0.75rem; padding: 2rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">Masuk ke MSF</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.25rem;">Mobile Sales Force : PT Mersifarma</p>
    </div>

    <form method="POST" action="{{ route('login.post') }}" id="loginForm">
        @csrf
        <div style="margin-bottom: 1rem;">
            <label for="email" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.35rem;">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.95rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label for="password" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.35rem;">Kata Sandi</label>
            <input id="password" type="password" name="password" required
                   style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.95rem;">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; min-height: 44px; font-size: 1rem;">
            Masuk
        </button>
    </form>

    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px dashed var(--border);">
        <p style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.75rem;">
            Akun Demo Pengujian (1-Klik Isi):
        </p>
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px;"
                    onclick="fillAccount('mr@mersi.com', 'password')">
                <span><strong>Rudi Hartono</strong> (MR)</span>
                <span class="role-pill role-mr">MR</span>
            </button>
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px;"
                    onclick="fillAccount('dm@mersi.com', 'password')">
                <span><strong>Joko Widodo</strong> (DM)</span>
                <span class="role-pill role-dm">DM</span>
            </button>
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px;"
                    onclick="fillAccount('rsm@mersi.com', 'password')">
                <span><strong>Siti Rahma</strong> (RSM)</span>
                <span class="role-pill role-rsm">RSM</span>
            </button>
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px;"
                    onclick="fillAccount('mm@mersi.com', 'password')">
                <span><strong>Budi Santoso</strong> (MM)</span>
                <span class="role-pill role-mm">MM</span>
            </button>
            <button type="button" class="btn btn-outline" style="justify-content: space-between; font-size: 0.85rem; padding: 0.5rem 0.75rem; min-height: 44px;"
                    onclick="fillAccount('dm2@mersi.com', 'password')">
                <span><strong>Hendra Gunawan</strong> (DM 2 Lain)</span>
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
