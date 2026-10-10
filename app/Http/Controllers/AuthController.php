<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('call-lists.index');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $loginInput = $request->input('username') ?? $request->input('email') ?? $request->input('login');

        $request->merge([
            'login' => $loginInput,
        ]);

        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Username atau email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $resolvedEmail = $loginInput;
        if (!str_contains($loginInput, '@')) {
            $user = User::where('username', $loginInput)
                ->orWhere('email', $loginInput.'@mersi.com')
                ->orWhere('name', 'LIKE', $loginInput.'%')
                ->first();

            if ($user) {
                $resolvedEmail = $user->email;
            } else {
                $resolvedEmail = $loginInput.'@mersi.com';
            }
        }

        $credentials = [
            'email' => $resolvedEmail,
            'password' => $request->input('password'),
        ];

        $throttleKey = Str::transliterate(Str::lower($loginInput).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $message = "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.";

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => 'TOO_MANY_ATTEMPTS',
                    'errors' => [
                        'email' => [$message],
                    ],
                ], 429);
            }

            return back()->withErrors([
                'email' => $message,
            ])->onlyInput('username', 'email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil',
                    'redirect_url' => redirect()->intended(route('call-lists.index'))->getTargetUrl(),
                    'data' => [
                        'user' => [
                            'id' => Auth::id(),
                            'name' => Auth::user()->name,
                            'email' => Auth::user()->email,
                            'role' => Auth::user()->role,
                            'manager_id' => Auth::user()->manager_id,
                        ],
                    ],
                ]);
            }

            return redirect()->intended(route('call-lists.index'));
        }

        RateLimiter::hit($throttleKey, 60);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah',
                'error_code' => 'INVALID_CREDENTIALS',
                'errors' => [
                    'email' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
                ],
            ], 422);
        }

        return back()->withErrors([
            'email' => 'Username atau kata sandi yang Anda masukkan salah.',
        ])->onlyInput('username', 'email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil',
                'data' => null,
            ]);
        }

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }

    public function me(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
                'errors' => (object) [],
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'manager_id' => $user->manager_id,
            ],
        ]);
    }

    public function csrfToken(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'CSRF token retrieved',
            'data' => [
                'csrf_token' => csrf_token(),
            ],
        ]);
    }
}
