<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('call-lists.index') : redirect()->route('login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Web Pages (Protected)
Route::middleware('auth')->group(function () {
    Route::get('/call-lists', function () {
        return view('call-lists.index');
    })->name('call-lists.index');

    Route::get('/call-plans', function () {
        return view('call-plans.index');
    })->name('call-plans.index');

    Route::get('/call-actuals', function () {
        return view('call-actuals.index');
    })->name('call-actuals.index');

});

// Mount same-origin API routes with web middleware (session + CSRF) per ADR-003
Route::prefix('api')->middleware('web')->group(base_path('routes/api.php'));
