<?php

namespace App\Services;

use App\Models\CallActual;
use App\Models\CallPlan;
use App\Models\MasterCustomer;
use App\Models\User;
use App\Models\VisitSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VisitSessionService
{
    /**
     * Memulai sesi kunjungan (Check-in server) atau resume jika sudah ada sesi aktif yang sama.
     */
    public function startSession(User $mr, ?int $planId, ?int $doctorId): VisitSession
    {
        if ($mr->role !== 'mr') {
            throw new AccessDeniedHttpException('Hanya MR yang dapat memulai sesi kunjungan.');
        }

        if (empty($planId) && empty($doctorId)) {
            throw new HttpException(422, 'Pilih jadwal kunjungan atau dokter sasaran.', null, ['X-Error-Code' => 'VALIDATION_FAILED']);
        }

        if (! empty($planId) && ! empty($doctorId)) {
            throw new HttpException(422, 'Kirim salah satu: jadwal kunjungan atau dokter sasaran.', null, ['X-Error-Code' => 'VALIDATION_FAILED']);
        }

        $today = Carbon::now()->toDateString();

        return DB::transaction(function () use ($mr, $planId, $doctorId, $today) {
            $plan = null;

            if (! empty($planId)) {
                $plan = CallPlan::whereKey($planId)
                    ->where('mr_id', $mr->id)
                    ->lockForUpdate()
                    ->first();

                if (! $plan) {
                    throw new AccessDeniedHttpException('Anda tidak memiliki akses ke Call Plan ini.');
                }

                if ($plan->status !== 'scheduled') {
                    throw new HttpException(409, 'Jadwal kunjungan tidak dalam status aktif/scheduled.', null, ['X-Error-Code' => 'PLAN_DATE_CONFLICT']);
                }

                if (Carbon::parse($plan->plan_date)->toDateString() !== $today) {
                    throw new HttpException(422, 'Sesi kunjungan hanya dapat dimulai pada tanggal rencana.', null, ['X-Error-Code' => 'VALIDATION_FAILED']);
                }

                $resolvedDoctorId = $plan->doctor_id;

                // Idempotent resume: jika MR sudah memiliki active session pada Plan ini
                $existingPlanSession = VisitSession::where('mr_id', $mr->id)
                    ->where('plan_id', $plan->id)
                    ->where('status', 'in_progress')
                    ->first();

                if ($existingPlanSession) {
                    return $existingPlanSession->load(['doctor', 'plan']);
                }
            } else {
                $resolvedDoctorId = (int) $doctorId;

                // Idempotent resume: jika MR sudah memiliki active session tanpa Plan pada dokter ini hari ini
                $existingDocSession = VisitSession::where('mr_id', $mr->id)
                    ->whereNull('plan_id')
                    ->where('doctor_id', $resolvedDoctorId)
                    ->where('visit_date', $today)
                    ->where('status', 'in_progress')
                    ->first();

                if ($existingDocSession) {
                    return $existingDocSession->load(['doctor', 'plan']);
                }
            }

            // Lock master customer dokter
            MasterCustomer::whereKey($resolvedDoctorId)->lockForUpdate()->firstOrFail();

            // 1. Cek apakah MR masih punya active session lain
            $otherActiveSession = VisitSession::where('mr_id', $mr->id)
                ->where('status', 'in_progress')
                ->first();

            if ($otherActiveSession) {
                throw new HttpException(
                    409,
                    'Anda masih memiliki sesi kunjungan aktif yang belum selesai. Selesaikan atau batalkan sesi tersebut terlebih dahulu.',
                    null,
                    ['X-Error-Code' => 'CONCURRENT_SESSION_DISALLOWED']
                );
            }

            // 2. Cek apakah dokter sudah memiliki Actual final di hari ini
            $hasActual = CallActual::where('doctor_id', $resolvedDoctorId)
                ->where('visit_date', $today)
                ->exists();

            if ($hasActual) {
                throw new HttpException(
                    409,
                    'Kunjungan untuk dokter dan tanggal ini sudah selesai tercatat.',
                    null,
                    ['X-Error-Code' => 'VISIT_ALREADY_EXISTS']
                );
            }

            // 3. Cek apakah dokter sedang dikunjungi MR lain hari ini
            $hasOtherMrSession = VisitSession::where('doctor_id', $resolvedDoctorId)
                ->where('visit_date', $today)
                ->where('status', 'in_progress')
                ->where('mr_id', '!=', $mr->id)
                ->exists();

            if ($hasOtherMrSession) {
                throw new HttpException(
                    409,
                    'Dokter sedang dalam sesi kunjungan aktif oleh rekan sales lain.',
                    null,
                    ['X-Error-Code' => 'ACTIVE_SESSION_CONFLICT']
                );
            }

            $session = VisitSession::create([
                'mr_id' => $mr->id,
                'plan_id' => $plan?->id,
                'doctor_id' => $resolvedDoctorId,
                'visit_date' => $today,
                'check_in_at' => Carbon::now(),
                'status' => 'in_progress',
                'draft_revision' => 0,
            ]);

            return $session->load(['doctor', 'plan']);
        });
    }

    /**
     * Mengambil sesi kunjungan MR yang saat ini sedang aktif (in_progress).
     */
    public function getActiveSession(User $mr): ?VisitSession
    {
        return VisitSession::with(['doctor', 'plan'])
            ->where('mr_id', $mr->id)
            ->where('status', 'in_progress')
            ->first();
    }

    /**
     * Mencatat waktu Check-out server pada sesi yang sedang berlangsung.
     */
    public function checkoutSession(User $mr, int $sessionId): VisitSession
    {
        $session = VisitSession::findOrFail($sessionId);

        if ($session->mr_id !== $mr->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke sesi kunjungan ini.');
        }

        if ($session->status !== 'in_progress') {
            throw new HttpException(422, 'Hanya sesi yang sedang berlangsung yang dapat di-checkout.', null, ['X-Error-Code' => 'SESSION_NOT_IN_PROGRESS']);
        }

        if (! $session->check_out_at) {
            $session->update([
                'check_out_at' => Carbon::now(),
            ]);
        }

        return $session->fresh(['doctor', 'plan']);
    }

    /**
     * Membatalkan sesi kunjungan yang sedang berlangsung dengan mencatat alasan.
     */
    public function cancelSession(User $mr, int $sessionId, string $reason): VisitSession
    {
        $session = VisitSession::findOrFail($sessionId);

        if ($session->mr_id !== $mr->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke sesi kunjungan ini.');
        }

        if ($session->status !== 'in_progress') {
            throw new HttpException(422, 'Hanya sesi yang sedang berlangsung yang dapat dibatalkan.', null, ['X-Error-Code' => 'SESSION_NOT_IN_PROGRESS']);
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 3) {
            throw new HttpException(422, 'Alasan pembatalan sesi kunjungan wajib diisi minimal 3 karakter.', null, ['X-Error-Code' => 'VALIDATION_FAILED']);
        }

        $session->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => Carbon::now(),
        ]);

        return $session->fresh(['doctor', 'plan']);
    }
}
