<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCallPlanRequest;
use App\Services\CallPlanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallPlanApiController extends Controller
{
    protected CallPlanService $callPlanService;

    public function __construct(CallPlanService $callPlanService)
    {
        $this->callPlanService = $callPlanService;
    }

    /**
     * POST /api/call-plans
     * Membuat rencana kunjungan (Call Plan) baru.
     */
    public function store(StoreCallPlanRequest $request): JsonResponse
    {
        $user = Auth::user();
        $callPlan = $this->callPlanService->createPlan($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Call Plan created',
            'data' => [
                'id' => $callPlan->id,
                'call_list_id' => $callPlan->call_list_id,
                'doctor_id' => $callPlan->doctor_id,
                'doctor_name' => $callPlan->doctor ? $callPlan->doctor->name : null,
                'plan_date' => Carbon::parse($callPlan->plan_date)->format('Y-m-d'),
                'planned_time' => (string) $callPlan->planned_time,
                'status' => $callPlan->status,
            ],
        ], 201);
    }

    /**
     * GET /api/call-plans
     * Mengambil daftar Call Plan milik MR login.
     * Menerima opsional ?date=YYYY-MM-DD dan ?status=scheduled.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $date = $request->query('date');
        $status = $request->query('status');

        $plans = $this->callPlanService->getPlans($user, $date, $status);

        return response()->json([
            'success' => true,
            'message' => 'Call Plans retrieved',
            'data' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'call_list_id' => $plan->call_list_id,
                    'doctor_id' => $plan->doctor_id,
                    'doctor_name' => $plan->doctor ? $plan->doctor->name : null,
                    'doctor_specialization' => $plan->doctor ? $plan->doctor->specialization : null,
                    'doctor_address' => $plan->doctor ? $plan->doctor->address : null,
                    'plan_date' => Carbon::parse($plan->plan_date)->format('Y-m-d'),
                    'planned_time' => (string) $plan->planned_time,
                    'status' => $plan->status,
                    'cancellation_reason' => $plan->cancellation_reason,
                    'cancelled_at' => $plan->cancelled_at ? $plan->cancelled_at->toIso8601String() : null,
                ];
            }),
        ]);
    }

    /**
     * PATCH /api/call-plans/{id}/cancel
     * Membatalkan jadwal kunjungan (Call Plan).
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ], [
            'reason.required' => 'Alasan pembatalan rencana kunjungan wajib diisi.',
            'reason.min' => 'Alasan pembatalan minimal 3 karakter.',
        ]);

        $user = Auth::user();
        $plan = $this->callPlanService->cancelPlan($user, $id, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Rencana kunjungan berhasil dibatalkan',
            'data' => [
                'id' => $plan->id,
                'status' => $plan->status,
                'cancellation_reason' => $plan->cancellation_reason,
                'cancelled_at' => $plan->cancelled_at ? $plan->cancelled_at->toIso8601String() : null,
            ],
        ]);
    }

    /**
     * GET /api/doctors/{id}/availability?month=YYYY-MM
     * Mengecek tanggal-tanggal yang sudah terisi untuk dokter tertentu di bulan bersangkutan.
     */
    public function availability(Request $request, int $id): JsonResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        $data = $this->callPlanService->getDoctorAvailability($id, $month);

        return response()->json([
            'success' => true,
            'message' => 'Doctor availability retrieved',
            'data' => $data,
        ]);
    }
}
