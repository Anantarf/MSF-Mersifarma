<?php

namespace App\Services;

use App\Models\CallActual;
use App\Models\CallList;
use App\Models\CallPlan;
use App\Models\MasterCustomer;
use App\Models\Product;
use App\Models\User;
use App\Models\VisitSession;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CallActualService
{
    public function recordDirect(User $mr, array $data): CallActual
    {
        if ($mr->role !== 'mr') {
            throw new AccessDeniedHttpException('Hanya MR yang dapat mencatat Call Actual.');
        }

        $this->ensureRequiredEvidence($data);

        $visitDate = Carbon::createFromFormat('Y-m-d', $data['visit_date'])->toDateString();
        $checkIn = $this->combineVisitDateAndTime($visitDate, $data['check_in']);
        $checkOut = $this->combineVisitDateAndTime($visitDate, $data['check_out']);

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            throw new HttpException(422, 'Check-out harus setelah check-in.', null, ['X-Error-Code' => 'VALIDATION_FAILED']);
        }

        if (! app()->environment('testing') && ! config('app.allow_future_visit_dates', false)) {
            if ($checkIn->isFuture() || $checkOut->isFuture()) {
                throw new HttpException(422, 'Waktu kunjungan tidak boleh di masa depan.', null, ['X-Error-Code' => 'VISIT_TIME_IN_FUTURE']);
            }
        }

        $productIds = collect($data['products'])->map(fn ($id) => (int) $id)->unique()->values();
        $this->ensureProductsExist($productIds);

        return DB::transaction(function () use ($mr, $data, $visitDate, $checkIn, $checkOut, $productIds) {
            $doctorId = (int) $data['doctor_id'];
            MasterCustomer::whereKey($doctorId)->lockForUpdate()->firstOrFail();

            $sessionId = ! empty($data['visit_session_id']) ? (int) $data['visit_session_id'] : null;
            $session = null;

            if ($sessionId) {
                $session = VisitSession::whereKey($sessionId)->lockForUpdate()->first();
                if (! $session || $session->mr_id !== $mr->id) {
                    throw new AccessDeniedHttpException('Anda tidak memiliki akses ke sesi kunjungan ini.');
                }
                if ($session->status !== 'in_progress') {
                    throw new HttpException(422, 'Sesi kunjungan tidak dalam status in_progress.', null, ['X-Error-Code' => 'SESSION_NOT_IN_PROGRESS']);
                }
                if ($session->doctor_id !== $doctorId) {
                    throw new HttpException(422, 'Dokter payload tidak sesuai dengan sesi kunjungan.', null, ['X-Error-Code' => 'DOCTOR_PLAN_MISMATCH']);
                }
                if ($session->plan_id && empty($data['plan_id'])) {
                    $data['plan_id'] = $session->plan_id;
                }
            }

            $this->ensureNoActiveSession($doctorId, $visitDate, $sessionId);
            $this->ensureNoExistingActual($mr, $doctorId, $visitDate);

            $plan = null;
            $visitType = 'non_target';

            if (! empty($data['plan_id'])) {
                $plan = $this->validatePlannedActual($mr, (int) $data['plan_id'], $doctorId, $visitDate, $data['deviation_reason'] ?? null);
                $visitType = 'planned';
            } else {
                $visitType = $this->classifyWithoutPlan($mr, $doctorId, $visitDate, $checkIn);
            }

            $actual = CallActual::create([
                'mr_id' => $mr->id,
                'plan_id' => $plan?->id,
                'visit_session_id' => $session?->id,
                'doctor_id' => $doctorId,
                'visit_date' => $visitDate,
                'check_in_at' => $session?->check_in_at ?? $checkIn,
                'check_out_at' => $session?->check_out_at ?? $checkOut,
                'visit_type' => $visitType,
                'photo_url' => trim($data['photo_url']),
                'signature_url' => trim($data['signature_url']),
                'visit_reason' => $data['visit_reason'] ?? null,
                'deviation_reason' => $data['deviation_reason'] ?? null,
            ]);

            $actual->products()->sync($productIds->all());

            if ($session) {
                $session->update([
                    'status' => 'completed',
                    'completed_at' => Carbon::now(),
                    'check_out_at' => $session->check_out_at ?? $checkOut,
                ]);
            }

            if ($plan) {
                $plan->update(['status' => 'completed']);
            }

            return $actual->load(['doctor', 'products', 'plan']);
        });
    }

    public function getActuals(User $mr, ?string $date = null, ?int $doctorId = null): Collection
    {
        $query = CallActual::with(['doctor', 'products', 'plan'])
            ->where('mr_id', $mr->id);

        if ($date) {
            $query->where('visit_date', $date);
        }

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        return $query->orderByDesc('visit_date')
            ->orderByDesc('check_in_at')
            ->get();
    }

    private function ensureRequiredEvidence(array $data): void
    {
        if (empty(trim($data['photo_url'] ?? ''))) {
            throw new HttpException(422, 'Foto kunjungan wajib diisi.', null, ['X-Error-Code' => 'PHOTO_REQUIRED']);
        }

        if (empty(trim($data['signature_url'] ?? ''))) {
            throw new HttpException(422, 'Tanda tangan dokter wajib diisi.', null, ['X-Error-Code' => 'SIGNATURE_REQUIRED']);
        }

        if (empty($data['products']) || ! is_array($data['products'])) {
            throw new HttpException(422, 'Minimal satu produk detailing wajib dipilih.', null, ['X-Error-Code' => 'PRODUCT_REQUIRED']);
        }
    }

    private function combineVisitDateAndTime(string $visitDate, string $time): Carbon
    {
        $normalizedTime = strlen($time) === 5 ? $time.':00' : $time;

        return Carbon::createFromFormat('Y-m-d H:i:s', $visitDate.' '.$normalizedTime, config('app.timezone'));
    }

    private function ensureProductsExist(Collection $productIds): void
    {
        if ($productIds->isEmpty()) {
            throw new HttpException(422, 'Minimal satu produk detailing wajib dipilih.', null, ['X-Error-Code' => 'PRODUCT_REQUIRED']);
        }

        $validCount = Product::whereIn('id', $productIds)->count();
        if ($validCount !== $productIds->count()) {
            throw new HttpException(422, 'Produk detailing tidak valid.', null, ['X-Error-Code' => 'PRODUCT_REQUIRED']);
        }
    }

    private function ensureNoActiveSession(int $doctorId, string $visitDate, ?int $allowedSessionId = null): void
    {
        $query = VisitSession::where('doctor_id', $doctorId)
            ->where('visit_date', $visitDate)
            ->where('status', 'in_progress');

        if ($allowedSessionId) {
            $query->whereKeyNot($allowedSessionId);
        }

        if ($query->exists()) {
            throw new HttpException(409, 'Masih ada sesi kunjungan aktif untuk dokter dan tanggal ini.', null, ['X-Error-Code' => 'ACTIVE_SESSION_CONFLICT']);
        }
    }

    private function ensureNoExistingActual(User $mr, int $doctorId, string $visitDate): void
    {
        $existing = CallActual::where('doctor_id', $doctorId)
            ->where('visit_date', $visitDate)
            ->first();

        if (! $existing) {
            return;
        }

        $isOwnedByMe = ($existing->mr_id === $mr->id);
        $detailData = ['owned_by_me' => $isOwnedByMe];
        if ($isOwnedByMe) {
            $detailData['existing_actual_id'] = $existing->id;
        }

        $message = $isOwnedByMe
            ? 'Kunjungan sudah tercatat oleh Anda.'
            : 'Kunjungan untuk dokter dan tanggal ini sudah tercatat.';

        $response = response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'VISIT_ALREADY_EXISTS',
            'errors' => (object) [],
            'data' => $detailData,
        ], 409);

        throw new HttpResponseException($response);
    }

    private function validatePlannedActual(User $mr, int $planId, int $doctorId, string $visitDate, ?string $deviationReason): CallPlan
    {
        $plan = CallPlan::with('callList')
            ->whereKey($planId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($plan->mr_id !== $mr->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke Call Plan ini.');
        }

        if ($plan->doctor_id !== $doctorId) {
            throw new HttpException(422, 'Dokter payload tidak sesuai dengan Call Plan.', null, ['X-Error-Code' => 'DOCTOR_PLAN_MISMATCH']);
        }

        if ($plan->status !== 'scheduled') {
            throw new HttpException(409, 'Call Plan tidak dalam status scheduled.', null, ['X-Error-Code' => 'PLAN_DATE_CONFLICT']);
        }

        if (! $plan->callList || $plan->callList->status !== 'approved') {
            throw new HttpException(422, 'Call Plan harus berasal dari Call List yang telah disetujui.', null, ['X-Error-Code' => 'CALL_LIST_NOT_APPROVED']);
        }

        $callListMonth = Carbon::parse($plan->callList->period_month)->format('Y-m');
        $actualMonth = Carbon::parse($visitDate)->format('Y-m');
        if ($callListMonth !== $actualMonth) {
            throw new HttpException(422, 'Tanggal actual harus berada dalam periode Call List sumber.', null, ['X-Error-Code' => 'VALIDATION_FAILED']);
        }

        if (Carbon::parse($plan->plan_date)->toDateString() !== $visitDate && empty(trim($deviationReason ?? ''))) {
            throw new HttpException(422, 'Alasan deviasi wajib diisi jika tanggal actual berbeda dari jadwal.', null, ['X-Error-Code' => 'VALIDATION_FAILED']);
        }

        $hasScheduledPlanOnActualDate = CallPlan::where('mr_id', $mr->id)
            ->where('doctor_id', $doctorId)
            ->where('plan_date', $visitDate)
            ->where('status', 'scheduled')
            ->whereKeyNot($plan->id)
            ->exists();

        if ($hasScheduledPlanOnActualDate) {
            throw new HttpException(409, 'Gunakan Call Plan yang sesuai dengan tanggal actual.', null, ['X-Error-Code' => 'PLAN_DATE_CONFLICT']);
        }

        return $plan;
    }

    private function classifyWithoutPlan(User $mr, int $doctorId, string $visitDate, Carbon $checkIn): string
    {
        $periodMonth = Carbon::parse($visitDate)->startOfMonth()->toDateString();

        $hasApprovedTarget = CallList::where('owner_id', $mr->id)
            ->where('period_month', $periodMonth)
            ->where('status', 'approved')
            ->where('approved_at', '<=', $checkIn)
            ->whereHas('doctors', fn ($query) => $query->where('master_customers.id', $doctorId))
            ->exists();

        return $hasApprovedTarget ? 'unplanned' : 'non_target';
    }
}
