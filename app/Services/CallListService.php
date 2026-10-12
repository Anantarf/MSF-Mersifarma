<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\CallList;
use App\Models\CallListApprovalLog;
use App\Models\MasterCustomer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CallListService
{
    /**
     * Membuat draft Call List baru.
     */
    public function createDraft(User $owner, string $periodMonth, array $doctorIds): CallList
    {
        // Normalisasi format period_month menjadi tanggal awal bulan (YYYY-MM-01)
        $normalizedDate = Carbon::createFromFormat('Y-m', $periodMonth)->startOfMonth()->format('Y-m-d');

        $this->ensureDoctorsWithinOwnerDistrict($owner, $doctorIds);

        return DB::transaction(function () use ($owner, $normalizedDate, $periodMonth, $doctorIds) {
            // Cek apakah owner sudah memiliki Call List di periode bulan yang sama
            $exists = CallList::where('owner_id', $owner->id)
                ->where('period_month', $normalizedDate)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'period_month' => ["Call List untuk periode {$periodMonth} sudah ada."],
                ]);
            }

            $callList = CallList::create([
                'owner_id' => $owner->id,
                'period_month' => $normalizedDate,
                'status' => 'draft',
                'assigned_approver_id' => $owner->manager_id,
            ]);

            $callList->doctors()->sync($doctorIds);

            CallListApprovalLog::create([
                'call_list_id' => $callList->id,
                'actor_id' => $owner->id,
                'event' => 'created_draft',
                'event_origin' => 'user_action',
                'from_status' => null,
                'to_status' => 'draft',
                'reason' => 'Draft Call List dibuat',
                'created_at' => Carbon::now(),
            ]);

            return $callList->load(['doctors', 'approvalLogs']);
        });
    }

    /**
     * Memperbarui daftar dokter pada draft Call List.
     */
    public function updateDraft(CallList $callList, User $user, array $doctorIds): CallList
    {
        if ($callList->owner_id !== $user->id) {
            throw ApiException::forbidden('APPROVAL_NOT_ALLOWED', 'Anda tidak memiliki wewenang untuk mengubah Call List ini.');
        }

        if ($callList->status !== 'draft') {
            throw ApiException::conflict('CONFLICT', 'Hanya Call List berstatus draft yang dapat diubah.');
        }

        $this->ensureDoctorsWithinOwnerDistrict($user, $doctorIds);

        return DB::transaction(function () use ($callList, $doctorIds) {
            $callList->doctors()->sync($doctorIds);

            return $callList->fresh(['doctors', 'approvalLogs']);
        });
    }

    private function ensureDoctorsWithinOwnerDistrict(User $owner, array $doctorIds): void
    {
        if ($owner->role !== 'mr' || blank($owner->district)) {
            return;
        }

        $outOfDistrict = MasterCustomer::whereIn('id', $doctorIds)
            ->where(function ($query) use ($owner) {
                $query->whereNull('district')
                    ->orWhere('district', '!=', $owner->district);
            })
            ->pluck('name')
            ->values();

        if ($outOfDistrict->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'doctor_ids' => [
                'Dokter target harus berada dalam distrik MR yang sama: '.$outOfDistrict->implode(', '),
            ],
        ]);
    }

    /**
     * Mengajukan Call List ke direct manager untuk disetujui.
     */
    public function submit(CallList $callList, User $user): CallList
    {
        if ($callList->owner_id !== $user->id) {
            throw ApiException::forbidden('APPROVAL_NOT_ALLOWED', 'Hanya pemilik yang dapat mengajukan Call List ini.');
        }

        if ($callList->status !== 'draft') {
            throw ApiException::conflict('CONFLICT', 'Hanya Call List berstatus draft yang dapat diajukan.');
        }

        if ($callList->doctors()->count() === 0) {
            throw ValidationException::withMessages([
                'doctor_ids' => ['Call List harus memiliki minimal 1 dokter target sebelum diajukan.'],
            ]);
        }

        return DB::transaction(function () use ($callList, $user) {
            $lockedList = CallList::where('id', $callList->id)->lockForUpdate()->first();

            if ($lockedList->owner_id !== $user->id) {
                throw ApiException::forbidden('APPROVAL_NOT_ALLOWED', 'Hanya pemilik yang dapat mengajukan Call List ini.');
            }

            if ($lockedList->status !== 'draft') {
                throw ApiException::conflict('CONFLICT', 'Hanya Call List berstatus draft yang dapat diajukan.');
            }

            if ($lockedList->doctors()->count() === 0) {
                throw ValidationException::withMessages([
                    'doctor_ids' => ['Call List harus memiliki minimal 1 dokter target sebelum diajukan.'],
                ]);
            }

            $assignedApproverId = $lockedList->assigned_approver_id ?: $user->manager_id;
            if (! $assignedApproverId) {
                throw ValidationException::withMessages([
                    'manager_id' => ['Pengguna tidak memiliki atasan langsung (direct manager) untuk persetujuan.'],
                ]);
            }

            // Cek apakah pernah di-reject sebelumnya (resubmission)
            $hasBeenRejected = $lockedList->approvalLogs()
                ->where('event', 'rejected')
                ->exists();

            $event = $hasBeenRejected ? 'resubmitted' : 'submitted';

            $lockedList->update([
                'status' => 'pending_approval',
                'assigned_approver_id' => $assignedApproverId,
                'submitted_at' => Carbon::now(),
            ]);

            CallListApprovalLog::create([
                'call_list_id' => $lockedList->id,
                'actor_id' => $user->id,
                'event' => $event,
                'event_origin' => 'user_action',
                'from_status' => 'draft',
                'to_status' => 'pending_approval',
                'reason' => $hasBeenRejected ? 'Pengajuan ulang setelah revisi draft' : 'Pengajuan Call List ke atasan',
                'created_at' => Carbon::now(),
            ]);

            return $lockedList->fresh(['doctors', 'approvalLogs']);
        });
    }

    /**
     * Memproses persetujuan (Approve atau Reject) oleh direct manager yang sah.
     */
    public function review(CallList $callList, User $approver, string $decision, ?string $reason = null): CallList
    {
        // 1. Verifikasi wewenang: approver harus assigned_approver_id dan merupakan atasan langsung owner
        if ($callList->assigned_approver_id !== $approver->id || $callList->owner->manager_id !== $approver->id) {
            throw ApiException::forbidden('APPROVAL_NOT_ALLOWED', 'Hanya atasan langsung (direct manager) yang ditugaskan yang dapat menyetujui atau menolak Call List ini.');
        }

        // 2. Status harus pending_approval
        if ($callList->status !== 'pending_approval') {
            throw ApiException::conflict('CONFLICT', "Call List tidak dalam status menunggu persetujuan (status saat ini: {$callList->status}).");
        }

        // 3. Jika reject, alasan wajib diisi
        if ($decision === 'rejected' && empty(trim($reason ?? ''))) {
            throw ValidationException::withMessages([
                'reason' => ['Alasan penolakan (reason) wajib diisi jika menolak Call List.'],
            ]);
        }

        return DB::transaction(function () use ($callList, $approver, $decision, $reason) {
            $lockedList = CallList::with('owner')->where('id', $callList->id)->lockForUpdate()->first();

            if ($lockedList->assigned_approver_id !== $approver->id || $lockedList->owner->manager_id !== $approver->id) {
                throw ApiException::forbidden('APPROVAL_NOT_ALLOWED', 'Hanya atasan langsung (direct manager) yang ditugaskan yang dapat menyetujui atau menolak Call List ini.');
            }

            if ($lockedList->status !== 'pending_approval') {
                throw ApiException::conflict('CONFLICT', "Call List tidak dalam status menunggu persetujuan (status saat ini: {$lockedList->status}).");
            }

            if ($decision === 'approved') {
                $lockedList->update([
                    'status' => 'approved',
                    'approved_at' => Carbon::now(),
                ]);

                CallListApprovalLog::create([
                    'call_list_id' => $lockedList->id,
                    'actor_id' => $approver->id,
                    'event' => 'approved',
                    'event_origin' => 'user_action',
                    'from_status' => 'pending_approval',
                    'to_status' => 'approved',
                    'reason' => $reason ?: 'Disetujui oleh atasan langsung',
                    'created_at' => Carbon::now(),
                ]);
            } else {
                // Reject: Mengembalikan ke status draft (ADR-006 & ADR-007)
                $lockedList->update([
                    'status' => 'draft',
                ]);

                CallListApprovalLog::create([
                    'call_list_id' => $lockedList->id,
                    'actor_id' => $approver->id,
                    'event' => 'rejected',
                    'event_origin' => 'user_action',
                    'from_status' => 'pending_approval',
                    'to_status' => 'draft',
                    'reason' => $reason,
                    'created_at' => Carbon::now(),
                ]);
            }

            return $lockedList->fresh(['doctors', 'approvalLogs', 'owner', 'assignedApprover']);
        });
    }

    /**
     * Menghitung rangkuman monitoring manajerial (read-only) untuk atasan (DM, RSM, MM).
     */
    public function getManagerialSummary(User $manager, ?string $periodMonth = null): array
    {
        $normalizedDate = $periodMonth
            ? Carbon::createFromFormat('Y-m', $periodMonth)->startOfMonth()->format('Y-m-d')
            : Carbon::now()->startOfMonth()->format('Y-m-d');

        // Ambil bawahan langsung berdasarkan relasi hierarki manager_id
        $subordinates = User::where('manager_id', $manager->id)->get();
        $subordinateIds = $subordinates->pluck('id');

        if ($subordinateIds->isEmpty()) {
            return [
                'period_month' => $normalizedDate,
                'total_subordinates' => 0,
                'submitted_count' => 0,
                'approved_count' => 0,
                'pending_count' => 0,
                'revision_count' => 0,
                'total_target_doctors' => 0,
                'subordinates_detail' => [],
            ];
        }

        // Ambil Call Lists milik bawahan langsung pada periode yang diminta
        $callLists = CallList::with(['doctors', 'owner'])
            ->whereIn('owner_id', $subordinateIds)
            ->where('period_month', $normalizedDate)
            ->get();

        $submittedCount = $callLists->count();
        $approvedCount = $callLists->where('status', 'approved')->count();
        $pendingCount = $callLists->where('status', 'pending_approval')->count();
        $revisionCount = $callLists->where('status', 'draft')->count();

        // Kumpulkan dokter unik yang ditargetkan di seluruh distrik/wilayah ini
        $uniqueDoctorIds = $callLists->flatMap(fn ($cl) => $cl->doctors->pluck('id'))->unique();

        $subordinatesDetail = $subordinates->map(function ($sub) use ($callLists) {
            $cl = $callLists->firstWhere('owner_id', $sub->id);

            return [
                'id' => $sub->id,
                'name' => $sub->name,
                'role' => $sub->role,
                'role_subtitle' => $sub->role_subtitle,
                'call_list_id' => $cl?->id,
                'has_submitted' => ! is_null($cl),
                'status' => $cl?->status ?? 'not_submitted',
                'doctor_count' => $cl ? $cl->doctors->count() : 0,
            ];
        });

        return [
            'period_month' => $normalizedDate,
            'total_subordinates' => $subordinates->count(),
            'submitted_count' => $submittedCount,
            'approved_count' => $approvedCount,
            'pending_count' => $pendingCount,
            'revision_count' => $revisionCount,
            'total_target_doctors' => $uniqueDoctorIds->count(),
            'subordinates_detail' => $subordinatesDetail->values()->all(),
        ];
    }
}
