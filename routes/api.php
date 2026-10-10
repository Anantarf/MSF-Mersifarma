<?php

use App\Http\Controllers\Api\CallActualApiController;
use App\Http\Controllers\Api\CallListApiController;
use App\Http\Controllers\Api\CallPlanApiController;
use App\Http\Controllers\Api\DoctorApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\VisitSessionApiController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/csrf-token', [AuthController::class, 'csrfToken']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/doctors', [DoctorApiController::class, 'index']);
    Route::get('/products', [ProductApiController::class, 'index']);

    Route::post('/call-lists', [CallListApiController::class, 'store']);
    Route::get('/call-lists', [CallListApiController::class, 'index']);
    Route::get('/call-lists/{id}', [CallListApiController::class, 'show']);
    Route::patch('/call-lists/{id}/approve', [CallListApiController::class, 'approve']);

    Route::match(['patch', 'put'], '/call-lists/{id}', [CallListApiController::class, 'update']);
    Route::post('/call-lists/{id}/submit', [CallListApiController::class, 'submit']);

    Route::post('/call-plans', [CallPlanApiController::class, 'store']);
    Route::get('/call-plans', [CallPlanApiController::class, 'index']);
    Route::patch('/call-plans/{id}/cancel', [CallPlanApiController::class, 'cancel']);
    Route::get('/doctors/{id}/availability', [CallPlanApiController::class, 'availability']);

    Route::post('/visit-sessions', [VisitSessionApiController::class, 'start']);
    Route::post('/visit-sessions/start', [VisitSessionApiController::class, 'start']);
    Route::get('/visit-sessions/active', [VisitSessionApiController::class, 'active']);
    Route::post('/visit-sessions/{id}/check-out', [VisitSessionApiController::class, 'checkout']);
    Route::post('/visit-sessions/{id}/checkout', [VisitSessionApiController::class, 'checkout']);
    Route::patch('/visit-sessions/{id}/cancel', [VisitSessionApiController::class, 'cancel']);
    Route::post('/visit-sessions/{id}/cancel', [VisitSessionApiController::class, 'cancel']);

    Route::post('/call-actuals', [CallActualApiController::class, 'store']);
    Route::get('/call-actuals', [CallActualApiController::class, 'index']);
});
