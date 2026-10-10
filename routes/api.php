<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\CallListApiController;
use App\Http\Controllers\Api\CallPlanApiController;
use App\Http\Controllers\Api\DoctorApiController;

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

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Master Customer List (Doctors) Picker
    Route::get('/doctors', [DoctorApiController::class, 'index']);

    // 4 Mandatory Call List Routes (PDF P0)
    Route::post('/call-lists', [CallListApiController::class, 'store']);
    Route::get('/call-lists', [CallListApiController::class, 'index']);
    Route::get('/call-lists/{id}', [CallListApiController::class, 'show']);
    Route::patch('/call-lists/{id}/approve', [CallListApiController::class, 'approve']);

    // Supporting Workflow Actions
    Route::put('/call-lists/{id}', [CallListApiController::class, 'update']);
    Route::post('/call-lists/{id}/submit', [CallListApiController::class, 'submit']);

    // 2 Mandatory Call Plan Routes (PDF P0)
    Route::post('/call-plans', [CallPlanApiController::class, 'store']);
    Route::get('/call-plans', [CallPlanApiController::class, 'index']);
});
