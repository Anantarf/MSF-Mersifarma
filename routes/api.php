<?php

use App\Http\Controllers\Api\CallActualApiController;
use App\Http\Controllers\Api\CallListApiController;
use App\Http\Controllers\Api\CallPlanApiController;
use App\Http\Controllers\Api\DoctorApiController;
use App\Http\Controllers\Api\ManagerialCampaignApiController;
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
    Route::get('/call-lists/summary', [CallListApiController::class, 'summary']);
    Route::get('/call-lists/monitoring-summary', [CallListApiController::class, 'summary']);
    Route::get('/call-lists/{id}', [CallListApiController::class, 'show']);
    Route::patch('/call-lists/{id}/approve', [CallListApiController::class, 'approve']);
    Route::post('/call-lists/{id}/reject', [CallListApiController::class, 'reject']);

    Route::match(['patch', 'put'], '/call-lists/{id}', [CallListApiController::class, 'update']);
    Route::post('/call-lists/{id}/submit', [CallListApiController::class, 'submit']);

    Route::post('/call-plans', [CallPlanApiController::class, 'store']);
    Route::get('/call-plans', [CallPlanApiController::class, 'index']);
    Route::patch('/call-plans/{id}/cancel', [CallPlanApiController::class, 'cancel']);
    Route::patch('/call-plans/{id}/reschedule', [CallPlanApiController::class, 'reschedule']);
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

    // Managerial Campaign & Recommendations (P1 Strategic Enhancement)
    Route::get('/managerial-campaigns', [ManagerialCampaignApiController::class, 'index']);
    Route::post('/managerial-campaigns', [ManagerialCampaignApiController::class, 'store']);
    Route::get('/managerial-campaigns/summary', [ManagerialCampaignApiController::class, 'summary']);
    Route::get('/managerial-campaigns/recommendations', [ManagerialCampaignApiController::class, 'doctorRecommendations']);
    Route::get('/managerial-campaigns/{id}', [ManagerialCampaignApiController::class, 'show']);
    Route::patch('/managerial-campaigns/{id}/review', [ManagerialCampaignApiController::class, 'review']);
});
