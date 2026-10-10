<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VisitSessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitSessionApiController extends Controller
{
    protected VisitSessionService $visitSessionService;

    public function __construct(VisitSessionService $visitSessionService)
    {
        $this->visitSessionService = $visitSessionService;
    }

    /**
     * POST /api/visit-sessions/start
     * Memulai sesi kunjungan (Check-in server) atau resume jika sudah aktif.
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'nullable|integer|exists:call_plans,id',
            'doctor_id' => 'nullable|integer|exists:master_customers,id',
        ]);

        $user = Auth::user();
        $session = $this->visitSessionService->startSession(
            $user,
            $request->input('plan_id') ? (int) $request->input('plan_id') : null,
            $request->input('doctor_id') ? (int) $request->input('doctor_id') : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Sesi kunjungan berhasil dimulai',
            'data' => $this->formatSession($session),
        ], 201);
    }

    /**
     * GET /api/visit-sessions/active
     * Mengambil sesi kunjungan MR yang saat ini sedang berlangsung.
     */
    public function active(): JsonResponse
    {
        $user = Auth::user();
        $session = $this->visitSessionService->getActiveSession($user);

        return response()->json([
            'success' => true,
            'message' => $session ? 'Sesi aktif ditemukan' : 'Tidak ada sesi aktif',
            'data' => $session ? $this->formatSession($session) : null,
        ]);
    }

    /**
     * POST /api/visit-sessions/{id}/checkout
     * Mencatat waktu Check-out server pada sesi yang sedang berlangsung.
     */
    public function checkout(int $id): JsonResponse
    {
        $user = Auth::user();
        $session = $this->visitSessionService->checkoutSession($user, $id);

        return response()->json([
            'success' => true,
            'message' => 'Waktu check-out berhasil dicatat oleh server',
            'data' => $this->formatSession($session),
        ]);
    }

    /**
     * POST /api/visit-sessions/{id}/cancel
     * Membatalkan sesi kunjungan lapangan dengan menyertakan alasan.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ], [
            'reason.required' => 'Alasan pembatalan kunjungan wajib diisi.',
            'reason.min' => 'Alasan pembatalan minimal 3 karakter.',
        ]);

        $user = Auth::user();
        $session = $this->visitSessionService->cancelSession($user, $id, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Sesi kunjungan berhasil dibatalkan',
            'data' => $this->formatSession($session),
        ]);
    }

    private function formatSession($session): array
    {
        return [
            'id' => $session->id,
            'plan_id' => $session->plan_id,
            'doctor_id' => $session->doctor_id,
            'doctor_name' => $session->doctor ? $session->doctor->name : null,
            'doctor_specialization' => $session->doctor ? $session->doctor->specialization : null,
            'doctor_address' => $session->doctor ? $session->doctor->address : null,
            'visit_date' => Carbon::parse($session->visit_date)->format('Y-m-d'),
            'check_in_at' => $session->check_in_at ? $session->check_in_at->toIso8601String() : null,
            'check_out_at' => $session->check_out_at ? $session->check_out_at->toIso8601String() : null,
            'status' => $session->status,
            'cancellation_reason' => $session->cancellation_reason,
        ];
    }
}
