<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\CallActual;
use App\Models\CallList;
use App\Models\CallPlan;
use App\Models\MasterCustomer;
use App\Models\User;
use App\Models\VisitSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CallPlanService
{
    /**
     * Membuat rencana kunjungan (Call Plan) baru.
     *
     * Invariants:
     * 1. Call List harus milik MR bersangkutan.
     * 2. Call List harus berstatus 'approved' (PDF-02).
     * 3. Dokter harus terdaftar sebagai anggota Call List tersebut.
     * 4. Tanggal rencana kunjungan (plan_date) tidak boleh sebelum hari ini dan harus berada dalam bulan periode Call List.
     * 5. Lock baris dokter untuk mencegah race condition.
     * 6. Dokter belum memiliki Actual final pada tanggal tersebut (VISIT_ALREADY_EXISTS - 409).
     * 7. Dokter belum memiliki Plan aktif (scheduled/completed) pada tanggal tersebut (PLAN_DATE_CONFLICT - 409).
     */
    public function createPlan(User $mr, array $data): CallPlan
    {
        if ($mr->role !== 'mr') {
            throw new AccessDeniedHttpException('Hanya MR yang dapat membuat Call Plan.');
        }

        $callListId = (int) $data['call_list_id'];
        $doctorId = (int) $data['doctor_id'];
        $planDate = $data['plan_date'];
        $plannedTime = $data['planned_time'];

        $callList = CallList::findOrFail($callListId);

        // 1. Cek kepemilikan
        if ($callList->owner_id !== $mr->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke Call List ini.');
        }

        // 2. Cek status approval Call List (PDF-02)
        if ($callList->status !== 'approved') {
            throw new ApiException(
                'CALL_LIST_NOT_APPROVED',
                'Call Plan hanya dapat dibuat dari Call List yang telah disetujui (Approved).',
                422
            );
        }

        // 3. Cek apakah dokter terdaftar di Call List
        $isDoctorInList = $callList->doctors()->where('master_customers.id', $doctorId)->exists();
        if (! $isDoctorInList) {
            throw new ApiException(
                'DOCTOR_PLAN_MISMATCH',
                'Dokter tidak terdaftar dalam Call List sasaran.',
                422
            );
        }

        // 3b. Plan tidak boleh dibuat untuk tanggal yang sudah lewat; jadwal lewat tanggal otomatis expired.
        if (Carbon::parse($planDate)->toDateString() < Carbon::today('Asia/Jakarta')->toDateString()) {
            throw new ApiException(
                'VALIDATION_FAILED',
                'Tanggal rencana kunjungan tidak boleh sebelum hari ini.',
                422
            );
        }

        // 4. Cek apakah plan_date berada dalam bulan Call List
        $callListMonth = Carbon::parse($callList->period_month)->format('Y-m');
        $planDateMonth = Carbon::parse($planDate)->format('Y-m');
        if ($callListMonth !== $planDateMonth) {
            throw new ApiException(
                'VALIDATION_FAILED',
                "Tanggal rencana kunjungan ({$planDate}) harus berada dalam periode bulan Call List ({$callListMonth}).",
                422
            );
        }

        return DB::transaction(function () use ($callList, $mr, $doctorId, $planDate, $plannedTime) {
            // 5. Lock baris master customer dokter FOR UPDATE
            MasterCustomer::where('id', $doctorId)->lockForUpdate()->firstOrFail();

            // 6. Cek apakah sudah ada Actual final di tanggal ini
            $hasActual = CallActual::where('doctor_id', $doctorId)
                ->where('visit_date', $planDate)
                ->exists();

            if ($hasActual) {
                throw new ApiException(
                    'VISIT_ALREADY_EXISTS',
                    'Kunjungan untuk dokter dan tanggal ini sudah selesai tercatat.',
                    409
                );
            }

            // 7. Cek apakah sudah ada Call Plan aktif (scheduled atau completed) di tanggal ini
            $hasActivePlan = CallPlan::where('doctor_id', $doctorId)
                ->where('plan_date', $planDate)
                ->whereIn('status', ['scheduled', 'completed'])
                ->exists();

            if ($hasActivePlan) {
                throw new ApiException(
                    'PLAN_DATE_CONFLICT',
                    'Dokter sudah memiliki jadwal rencana kunjungan aktif pada tanggal tersebut.',
                    409
                );
            }

            return CallPlan::create([
                'call_list_id' => $callList->id,
                'mr_id' => $mr->id,
                'doctor_id' => $doctorId,
                'plan_date' => $planDate,
                'planned_time' => $plannedTime,
                'status' => 'scheduled',
            ]);
        });
    }

    /**
     * Mengubah status Plan yang lewat tanggal operasional tanpa Actual menjadi 'expired' (Tidak Terlaksana).
     *
     * Invariants:
     * 1. Plan masih berstatus 'scheduled'.
     * 2. plan_date < hari ini (Asia/Jakarta).
     * 3. Belum memiliki CallActual yang merealisasikan.
     */
    public function expireStalePlans(?User $mr = null): int
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        $query = CallPlan::where('status', CallPlan::STATUS_SCHEDULED)
            ->where('plan_date', '<', $today)
            ->whereDoesntHave('callActual');

        if ($mr) {
            $query->where('mr_id', $mr->id);
        }

        return $query->update(['status' => CallPlan::STATUS_EXPIRED]);
    }

    /**
     * Mengambil daftar Call Plan milik MR login dengan filter opsional.
     */
    public function getPlans(User $mr, ?string $date = null, ?string $status = null): Collection
    {
        $this->expireStalePlans($mr);

        $query = CallPlan::with(['doctor'])
            ->where('mr_id', $mr->id);

        if ($date) {
            $query->where('plan_date', $date);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('plan_date', 'asc')
            ->orderBy('planned_time', 'asc')
            ->get();
    }

    /**
     * Membatalkan rencana kunjungan (Call Plan).
     *
     * Invariants:
     * 1. Hanya MR pemilik yang dapat membatalkan.
     * 2. Hanya Plan dengan status 'scheduled' yang dapat dibatalkan.
     * 3. Wajib menyertakan alasan pembatalan.
     * 4. Plan yang sudah memiliki Actual final tidak dapat dibatalkan.
     */
    public function cancelPlan(User $mr, int $planId, string $reason): CallPlan
    {
        $plan = CallPlan::findOrFail($planId);

        if ($plan->mr_id !== $mr->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki hak untuk membatalkan jadwal kunjungan ini.');
        }

        if ($plan->status === 'completed') {
            throw new ApiException('PLAN_ALREADY_COMPLETED', 'Rencana kunjungan yang sudah selesai tidak dapat dibatalkan.', 422);
        }

        if ($plan->status === 'cancelled') {
            throw new ApiException('PLAN_ALREADY_CANCELLED', 'Rencana kunjungan sudah dalam status dibatalkan.', 422);
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 3) {
            throw new ApiException('VALIDATION_FAILED', 'Alasan pembatalan rencana kunjungan wajib diisi minimal 3 karakter.', 422);
        }

        // Cek apakah ada Actual terkait
        if ($plan->callActual()->exists()) {
            throw new ApiException('PLAN_HAS_ACTUAL', 'Rencana kunjungan sudah memiliki laporan kunjungan tercatat.', 422);
        }

        return DB::transaction(function () use ($plan, $reason) {
            $plan->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            return $plan->fresh(['doctor']);
        });
    }

    /**
     * Menjadwalkan ulang rencana kunjungan (Call Plan).
     *
     * Invariants (semua state di-validasi ulang di bawah lock dokter lalu plan):
     * 1. Hanya MR pemilik yang dapat melakukan reschedule.
     * 2. Rencana kunjungan harus berstatus 'scheduled' dan belum memiliki Call Actual.
     * 3. Tidak ada Visit Session in_progress pada Plan tersebut.
     * 4. Call List sumber masih berstatus 'approved'.
     * 5. Tanggal baru harus berada dalam bulan periode Call List yang disetujui.
     * 6. Dokter belum memiliki Plan aktif lain atau Actual final pada tanggal baru (409).
     */
    public function reschedulePlan(User $mr, int $planId, array $data): CallPlan
    {
        if ($mr->role !== 'mr') {
            throw new AccessDeniedHttpException('Hanya MR yang dapat menjadwalkan ulang rencana kunjungan.');
        }

        $reason = trim($data['reason'] ?? '');
        if (mb_strlen($reason) < 3) {
            throw new ApiException('VALIDATION_FAILED', 'Alasan penjadwalan ulang rencana kunjungan wajib diisi minimal 3 karakter.', 422);
        }

        $newDate = Carbon::parse($data['plan_date'])->toDateString();

        if ($newDate < Carbon::today('Asia/Jakarta')->toDateString()) {
            throw new ApiException('VALIDATION_FAILED', 'Tanggal baru tidak boleh sebelum hari ini.', 422);
        }

        // Bacaan awal tanpa lock hanya untuk mengetahui pemilik dan dokter yang harus di-lock.
        $planOwnership = CallPlan::findOrFail($planId, ['id', 'mr_id', 'doctor_id']);
        if ($planOwnership->mr_id !== $mr->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke rencana kunjungan ini.');
        }

        return DB::transaction(function () use ($planOwnership, $newDate, $reason, $data) {
            MasterCustomer::whereKey($planOwnership->doctor_id)->lockForUpdate()->firstOrFail();
            $plan = CallPlan::with('callList')->whereKey($planOwnership->id)->lockForUpdate()->firstOrFail();

            if ($plan->status !== 'scheduled') {
                throw new ApiException('INVALID_STATUS', 'Hanya rencana kunjungan berstatus scheduled yang dapat dijadwalkan ulang.', 422);
            }

            if ($plan->callActual()->exists()) {
                throw new ApiException('PLAN_HAS_ACTUAL', 'Rencana kunjungan sudah memiliki laporan kunjungan tercatat.', 422);
            }

            if (VisitSession::where('plan_id', $plan->id)->where('status', 'in_progress')->exists()) {
                throw new ApiException('ACTIVE_SESSION_CONFLICT', 'Rencana kunjungan sedang berlangsung. Batalkan sesi kunjungan terlebih dahulu.', 409);
            }

            if (! $plan->callList || $plan->callList->status !== 'approved') {
                throw new ApiException('CALL_LIST_NOT_APPROVED', 'Call List sumber tidak lagi dalam status disetujui.', 422);
            }

            if (Carbon::parse($plan->callList->period_month)->format('Y-m') !== Carbon::parse($newDate)->format('Y-m')) {
                throw new ApiException('DATE_OUT_OF_PERIOD', 'Tanggal baru harus berada dalam bulan periode Call List yang disetujui.', 422);
            }

            // Cek apakah dokter sudah memiliki Call Plan aktif lain pada tanggal baru
            $planConflict = CallPlan::where('doctor_id', $plan->doctor_id)
                ->where('plan_date', $newDate)
                ->whereIn('status', ['scheduled', 'completed'])
                ->whereKeyNot($plan->id)
                ->exists();

            if ($planConflict) {
                throw new ApiException('PLAN_DATE_CONFLICT', 'Sudah ada rencana kunjungan aktif untuk dokter ini pada tanggal tersebut.', 409);
            }

            // Cek apakah dokter sudah memiliki Call Actual final pada tanggal baru
            $actualConflict = CallActual::where('doctor_id', $plan->doctor_id)
                ->where('visit_date', $newDate)
                ->exists();

            if ($actualConflict) {
                throw new ApiException('VISIT_ALREADY_EXISTS', 'Sudah ada kunjungan final (Call Actual) untuk dokter ini pada tanggal tersebut.', 409);
            }

            $updateData = [
                'plan_date' => $newDate,
                'reschedule_reason' => $reason,
                'rescheduled_at' => now(),
            ];

            if (array_key_exists('planned_time', $data) && ! empty($data['planned_time'])) {
                $updateData['planned_time'] = Carbon::parse($data['planned_time'])->format('H:i:s');
            }

            $plan->update($updateData);

            return $plan->fresh(['doctor', 'callList']);
        });
    }

    /**
     * Mengambil daftar tanggal bentrok/terisi untuk dokter pada bulan tertentu.
     * Tidak membocorkan identitas MR lain demi privasi (privacy-safe).
     */
    public function getDoctorAvailability(int $doctorId, string $month): array
    {
        $monthStart = Carbon::parse($month)->startOfMonth()->toDateString();
        $monthEnd = Carbon::parse($month)->endOfMonth()->toDateString();
        $targetMonth = Carbon::parse($monthStart)->format('Y-m');

        // 1. Ambil tanggal dari Call Plan yang aktif (scheduled atau completed)
        $planDates = CallPlan::where('doctor_id', $doctorId)
            ->whereIn('status', ['scheduled', 'completed'])
            ->whereBetween('plan_date', [$monthStart, $monthEnd])
            ->pluck('plan_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // 2. Ambil tanggal dari Call Actual final
        $actualDates = CallActual::where('doctor_id', $doctorId)
            ->whereBetween('visit_date', [$monthStart, $monthEnd])
            ->pluck('visit_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // Gabungkan dan hilangkan duplikasi
        $busyDates = array_values(array_unique(array_merge($planDates, $actualDates)));
        sort($busyDates);

        return [
            'doctor_id' => $doctorId,
            'month' => $targetMonth,
            'busy_dates' => $busyDates,
        ];
    }

    /**
     * Memeriksa apakah MR memiliki jadwal aktif untuk dokter pada periode ini.
     */
    public function hasActiveSchedule(int $mrId, int $doctorId, string $referenceDate): bool
    {
        return CallPlan::hasActiveSchedule($mrId, $doctorId, $referenceDate);
    }
}
