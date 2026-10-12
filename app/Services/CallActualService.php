<?php

namespace App\Services;

use App\Exceptions\ApiException;
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

class CallActualService
{
    public function recordDirect(User $mr, array $data): CallActual
    {
        if ($mr->role !== 'mr') {
            throw new AccessDeniedHttpException('Hanya MR yang dapat mencatat Call Actual.');
        }

        $this->ensureRequiredEvidence($data);

        $productIds = collect($data['products'])->map(fn ($id) => (int) $id)->unique()->values();
        $this->ensureProductsExist($productIds);

        $sessionId = ! empty($data['visit_session_id']) ? (int) $data['visit_session_id'] : null;
        if ($sessionId) {
            return $this->recordSessionBacked($mr, $data, $sessionId, $productIds);
        }

        return $this->recordManualDirect($mr, $data, $productIds);
    }

    private function recordManualDirect(User $mr, array $data, Collection $productIds): CallActual
    {
        $visitDate = Carbon::createFromFormat('Y-m-d', $data['visit_date'])->toDateString();
        $checkIn = $this->combineVisitDateAndTime($visitDate, $data['check_in']);
        $checkOut = $this->combineVisitDateAndTime($visitDate, $data['check_out']);

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            throw new ApiException('VALIDATION_FAILED', 'Check-out harus setelah check-in.', 422);
        }

        if (! app()->environment('testing') && ! config('app.allow_future_visit_dates', false)) {
            if ($checkIn->isFuture() || $checkOut->isFuture()) {
                throw new ApiException('VISIT_TIME_IN_FUTURE', 'Waktu kunjungan tidak boleh di masa depan.', 422);
            }
        }

        return DB::transaction(function () use ($mr, $data, $visitDate, $checkIn, $checkOut, $productIds) {
            $doctorId = (int) $data['doctor_id'];
            MasterCustomer::whereKey($doctorId)->lockForUpdate()->firstOrFail();

            $this->ensureNoActiveSession($doctorId, $visitDate);
            $this->ensureNoExistingActual($mr, $doctorId, $visitDate);

            $plan = null;
            $visitType = 'non_target';

            if (! empty($data['plan_id'])) {
                $plan = $this->validatePlannedActual($mr, (int) $data['plan_id'], $doctorId, $visitDate, $data['deviation_reason'] ?? null);
                $visitType = 'planned';
            } else {
                $visitType = $this->classifyWithoutPlan($mr, $doctorId, $visitDate, $checkIn);
            }

            $actual = $this->createCompletedActual(
                $mr,
                $data,
                $productIds,
                $doctorId,
                $visitDate,
                $checkIn,
                $checkOut,
                $visitType,
                $plan,
            );

            if ($plan) {
                $plan->update(['status' => 'completed']);
            }

            return $actual->load(['doctor', 'products']);
        });
    }

    private function recordSessionBacked(User $mr, array $data, int $sessionId, Collection $productIds): CallActual
    {
        $this->ensureSessionPayloadIsPure($data);

        $sessionIdentity = VisitSession::whereKey($sessionId)->firstOrFail();

        return DB::transaction(function () use ($mr, $data, $sessionIdentity, $productIds) {
            MasterCustomer::whereKey($sessionIdentity->doctor_id)->lockForUpdate()->firstOrFail();

            $session = VisitSession::whereKey($sessionIdentity->id)->lockForUpdate()->firstOrFail();
            if ($session->mr_id !== $mr->id) {
                throw new AccessDeniedHttpException('Anda tidak memiliki akses ke sesi kunjungan ini.');
            }

            if ($session->status !== 'in_progress') {
                throw new ApiException('SESSION_NOT_IN_PROGRESS', 'Sesi kunjungan tidak dalam status in_progress.', 422);
            }

            if (! $session->check_out_at) {
                throw new ApiException('SESSION_CHECKOUT_REQUIRED', 'Sesi kunjungan harus check-out sebelum laporan diselesaikan.', 422);
            }

            $doctorId = $session->doctor_id;
            $visitDate = Carbon::parse($session->visit_date)->toDateString();
            $checkIn = Carbon::parse($session->check_in_at);
            $checkOut = Carbon::parse($session->check_out_at);

            if ($checkOut->lessThanOrEqualTo($checkIn)) {
                throw new ApiException('VALIDATION_FAILED', 'Check-out harus setelah check-in.', 422);
            }

            $this->ensureNoActiveSession($doctorId, $visitDate, $session->id);
            $this->ensureNoExistingActual($mr, $doctorId, $visitDate);

            $plan = null;
            if ($session->plan_id) {
                $plan = $this->validatePlannedActual($mr, $session->plan_id, $doctorId, $visitDate, $data['deviation_reason'] ?? null);
                $visitType = 'planned';
            } else {
                $visitType = $this->classifyWithoutPlan($mr, $doctorId, $visitDate, $checkIn);
            }

            $actual = $this->createCompletedActual(
                $mr,
                $data,
                $productIds,
                $doctorId,
                $visitDate,
                $checkIn,
                $checkOut,
                $visitType,
                $plan,
                $session,
            );

            $session->update([
                'status' => 'completed',
                'completed_at' => Carbon::now(),
            ]);

            if ($plan) {
                $plan->update(['status' => 'completed']);
            }

            return $actual->load(['doctor', 'products']);
        });
    }

    private function ensureSessionPayloadIsPure(array $data): void
    {
        foreach (['plan_id', 'doctor_id', 'visit_date', 'check_in', 'check_out'] as $field) {
            if (array_key_exists($field, $data)) {
                throw new ApiException('SESSION_PAYLOAD_MISMATCH', 'Payload sesi tidak boleh mengirim identitas atau waktu kunjungan manual.', 422);
            }
        }
    }

    private function createCompletedActual(
        User $mr,
        array $data,
        Collection $productIds,
        int $doctorId,
        string $visitDate,
        Carbon $checkIn,
        Carbon $checkOut,
        string $visitType,
        ?CallPlan $plan = null,
        ?VisitSession $session = null
    ): CallActual {
        $actual = CallActual::create([
            'mr_id' => $mr->id,
            'plan_id' => $plan?->id,
            'visit_session_id' => $session?->id,
            'doctor_id' => $doctorId,
            'visit_date' => $visitDate,
            'check_in_at' => $checkIn,
            'check_out_at' => $checkOut,
            'visit_type' => $visitType,
            'photo_url' => trim($data['photo_url']),
            'signature_url' => trim($data['signature_url']),
            'visit_reason' => $data['visit_reason'] ?? null,
            'deviation_reason' => $data['deviation_reason'] ?? null,
        ]);

        $actual->products()->sync($productIds->all());

        return $actual;
    }

    public function getActuals(
        User $user,
        ?string $date = null,
        ?int $doctorId = null,
        ?int $targetMrId = null,
        ?string $month = null,
        ?string $visitType = null
    ): Collection {
        $query = CallActual::with(['doctor', 'products', 'mr']);

        if ($user->role === 'mr') {
            if ($targetMrId && $targetMrId !== $user->id) {
                throw new AccessDeniedHttpException('Anda tidak memiliki akses ke data MR ini.');
            }
            $query->where('mr_id', $user->id);
        } else {
            $allowedMrIds = $user->getSubordinateMrIds();
            if ($targetMrId) {
                if (! in_array($targetMrId, $allowedMrIds, true)) {
                    throw new AccessDeniedHttpException('Anda tidak memiliki akses ke data MR ini.');
                }
                $query->where('mr_id', $targetMrId);
            } else {
                $query->whereIn('mr_id', $allowedMrIds);
            }
        }

        if ($date) {
            $query->where('visit_date', $date);
        }

        if ($month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
            $query->whereBetween('visit_date', [$startOfMonth, $endOfMonth]);
        }

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        if ($visitType) {
            $query->where('visit_type', $visitType);
        }

        return $query->orderByDesc('visit_date')
            ->orderByDesc('check_in_at')
            ->get();
    }

    private function ensureRequiredEvidence(array $data): void
    {
        if (empty(trim($data['photo_url'] ?? ''))) {
            throw new ApiException('PHOTO_REQUIRED', 'Foto kunjungan wajib diisi.', 422);
        }

        if (empty(trim($data['signature_url'] ?? ''))) {
            throw new ApiException('SIGNATURE_REQUIRED', 'Tanda tangan dokter wajib diisi.', 422);
        }

        if (empty($data['products']) || ! is_array($data['products'])) {
            throw new ApiException('PRODUCT_REQUIRED', 'Minimal satu produk detailing wajib dipilih.', 422);
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
            throw new ApiException('PRODUCT_REQUIRED', 'Minimal satu produk detailing wajib dipilih.', 422);
        }

        $validCount = Product::whereIn('id', $productIds)->count();
        if ($validCount !== $productIds->count()) {
            throw new ApiException('PRODUCT_REQUIRED', 'Produk detailing tidak valid.', 422);
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
            throw new ApiException('ACTIVE_SESSION_CONFLICT', 'Masih ada sesi kunjungan aktif untuk dokter dan tanggal ini.', 409);
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
            throw new ApiException('DOCTOR_PLAN_MISMATCH', 'Dokter payload tidak sesuai dengan Call Plan.', 422);
        }

        if ($plan->status !== 'scheduled') {
            throw new ApiException('PLAN_DATE_CONFLICT', 'Call Plan tidak dalam status scheduled.', 409);
        }

        if (! $plan->callList || $plan->callList->status !== 'approved') {
            throw new ApiException('CALL_LIST_NOT_APPROVED', 'Call Plan harus berasal dari Call List yang telah disetujui.', 422);
        }

        $callListMonth = Carbon::parse($plan->callList->period_month)->format('Y-m');
        $actualMonth = Carbon::parse($visitDate)->format('Y-m');
        if ($callListMonth !== $actualMonth) {
            throw new ApiException('VALIDATION_FAILED', 'Tanggal actual harus berada dalam periode Call List sumber.', 422);
        }

        if (Carbon::parse($plan->plan_date)->toDateString() !== $visitDate) {
            throw new ApiException('RESCHEDULE_REQUIRED', 'Tanggal kunjungan tidak sesuai jadwal. Reschedule jadwal terlebih dahulu.', 422);
        }

        return $plan;
    }

    private function classifyWithoutPlan(User $mr, int $doctorId, string $visitDate, Carbon $checkIn): string
    {
        // Kunjungan di Luar Rencana bukan untuk salah jadwal:
        // Jika MR masih memiliki Plan scheduled yang aktif (belum lewat) pada periode ini, wajib Reschedule.
        if (CallPlan::hasActiveSchedule($mr->id, $doctorId, $visitDate)) {
            throw new ApiException('RESCHEDULE_REQUIRED', 'Dokter ini sudah memiliki jadwal aktif pada periode ini. Silakan lakukan kunjungan sesuai jadwal atau Reschedule jadwal terlebih dahulu.', 422);
        }

        $periodStart = Carbon::parse($visitDate)->startOfMonth()->toDateString();

        $hasApprovedTarget = CallList::where('owner_id', $mr->id)
            ->where('period_month', $periodStart)
            ->where('status', 'approved')
            ->where('approved_at', '<=', $checkIn)
            ->whereHas('doctors', fn ($query) => $query->where('master_customers.id', $doctorId))
            ->exists();

        return $hasApprovedTarget ? 'unplanned' : 'non_target';
    }
}
