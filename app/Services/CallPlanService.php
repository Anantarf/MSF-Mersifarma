<?php

namespace App\Services;

use App\Models\CallActual;
use App\Models\CallList;
use App\Models\CallPlan;
use App\Models\MasterCustomer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CallPlanService
{
    /**
     * Membuat rencana kunjungan (Call Plan) baru.
     *
     * Invariants:
     * 1. Call List harus milik MR bersangkutan.
     * 2. Call List harus berstatus 'approved' (PDF-02).
     * 3. Dokter harus terdaftar sebagai anggota Call List tersebut.
     * 4. Tanggal rencana kunjungan (plan_date) harus berada dalam bulan periode Call List.
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
            throw new HttpException(
                422,
                'Call Plan hanya dapat dibuat dari Call List yang telah disetujui (Approved).',
                null,
                ['X-Error-Code' => 'CALL_LIST_NOT_APPROVED']
            );
        }

        // 3. Cek apakah dokter terdaftar di Call List
        $isDoctorInList = $callList->doctors()->where('master_customers.id', $doctorId)->exists();
        if (! $isDoctorInList) {
            throw new HttpException(
                422,
                'Dokter tidak terdaftar dalam Call List sasaran.',
                null,
                ['X-Error-Code' => 'DOCTOR_PLAN_MISMATCH']
            );
        }

        // 4. Cek apakah plan_date berada dalam bulan Call List
        $callListMonth = Carbon::parse($callList->period_month)->format('Y-m');
        $planDateMonth = Carbon::parse($planDate)->format('Y-m');
        if ($callListMonth !== $planDateMonth) {
            throw new HttpException(
                422,
                "Tanggal rencana kunjungan ({$planDate}) harus berada dalam periode bulan Call List ({$callListMonth}).",
                null,
                ['X-Error-Code' => 'VALIDATION_FAILED']
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
                throw new HttpException(
                    409,
                    'Kunjungan untuk dokter dan tanggal ini sudah selesai tercatat.',
                    null,
                    ['X-Error-Code' => 'VISIT_ALREADY_EXISTS']
                );
            }

            // 7. Cek apakah sudah ada Call Plan aktif (scheduled atau completed) di tanggal ini
            $hasActivePlan = CallPlan::where('doctor_id', $doctorId)
                ->where('plan_date', $planDate)
                ->whereIn('status', ['scheduled', 'completed'])
                ->exists();

            if ($hasActivePlan) {
                throw new HttpException(
                    409,
                    'Dokter sudah memiliki jadwal rencana kunjungan aktif pada tanggal tersebut.',
                    null,
                    ['X-Error-Code' => 'PLAN_DATE_CONFLICT']
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
     * Mengambil daftar Call Plan milik MR login dengan filter opsional.
     */
    public function getPlans(User $mr, ?string $date = null, ?string $status = null): Collection
    {
        $query = CallPlan::with(['doctor', 'callList'])
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
            throw new HttpException(422, 'Rencana kunjungan yang sudah selesai tidak dapat dibatalkan.', null, ['X-Error-Code' => 'PLAN_ALREADY_COMPLETED']);
        }

        if ($plan->status === 'cancelled') {
            throw new HttpException(422, 'Rencana kunjungan sudah dalam status dibatalkan.', null, ['X-Error-Code' => 'PLAN_ALREADY_CANCELLED']);
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 3) {
            throw new HttpException(422, 'Alasan pembatalan rencana kunjungan wajib diisi minimal 3 karakter.', null, ['X-Error-Code' => 'VALIDATION_FAILED']);
        }

        // Cek apakah ada Actual terkait
        if ($plan->callActual()->exists()) {
            throw new HttpException(422, 'Rencana kunjungan sudah memiliki laporan kunjungan tercatat.', null, ['X-Error-Code' => 'PLAN_HAS_ACTUAL']);
        }

        return DB::transaction(function () use ($plan, $reason) {
            $plan->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            return $plan->fresh(['doctor', 'callList']);
        });
    }

    /**
     * Mengambil daftar tanggal bentrok/terisi untuk dokter pada bulan tertentu.
     * Tidak membocorkan identitas MR lain demi privasi (privacy-safe).
     */
    public function getDoctorAvailability(int $doctorId, string $month): array
    {
        $targetMonth = Carbon::parse($month)->format('Y-m');

        // 1. Ambil tanggal dari Call Plan yang aktif (scheduled atau completed)
        $planDates = CallPlan::where('doctor_id', $doctorId)
            ->whereIn('status', ['scheduled', 'completed'])
            ->whereRaw("DATE_FORMAT(plan_date, '%Y-%m') = ?", [$targetMonth])
            ->pluck('plan_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // 2. Ambil tanggal dari Call Actual final
        $actualDates = CallActual::where('doctor_id', $doctorId)
            ->whereRaw("DATE_FORMAT(visit_date, '%Y-%m') = ?", [$targetMonth])
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
}
