<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes — MSF Mobile Sales Force
|--------------------------------------------------------------------------
|
| Mounted with web middleware (same-origin session + CSRF protection)
| as mandated by ADR-003 and API_CONTRACT.md.
|
*/

Route::get('/csrf-token', [AuthController::class, 'csrfToken']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth');
