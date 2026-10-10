<?php

namespace Database\Seeders;

use App\Models\CallList;
use App\Models\CallListApprovalLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan master data sudah ada jika seeder dijalankan mandiri
        if (! User::where('email', 'mr@mersi.com')->exists()) {
            $this->call(DatabaseSeeder::class);
        }

        $mr = User::where('email', 'mr@mersi.com')->first();
        $dm = User::where('email', 'dm@mersi.com')->first();

        if (! $mr || ! $dm) {
            return;
        }

        $currentMonth = Carbon::now()->startOfMonth();
        $nextMonth = Carbon::now()->addMonth()->startOfMonth();

        // 1. Call List Approved untuk bulan berjalan (Siap digunakan untuk Call Plan & Actual)
        $approvedList = CallList::firstOrCreate(
            [
                'owner_id' => $mr->id,
                'period_month' => $currentMonth->format('Y-m-d'),
            ],
            [
                'status' => 'approved',
                'assigned_approver_id' => $dm->id,
                'submitted_at' => $currentMonth->copy()->subDays(3)->setTime(9, 30, 0),
                'approved_at' => $currentMonth->copy()->subDays(2)->setTime(14, 15, 0),
            ]
        );

        // Pasangkan dokter sasaran (dr. Andi, dr. Budi, dr. Citra, dr. Dewi)
        $approvedList->doctors()->syncWithoutDetaching([1, 2, 3, 4]);

        // Catat jejak audit approval log lengkap
        if ($approvedList->approvalLogs()->count() === 0) {
            CallListApprovalLog::create([
                'call_list_id' => $approvedList->id,
                'actor_id' => $mr->id,
                'event' => 'created_draft',
                'event_origin' => 'user_action',
                'from_status' => null,
                'to_status' => 'draft',
                'reason' => 'Draft rencana target bulanan dibuat',
                'created_at' => $currentMonth->copy()->subDays(4)->setTime(10, 0, 0),
            ]);

            CallListApprovalLog::create([
                'call_list_id' => $approvedList->id,
                'actor_id' => $mr->id,
                'event' => 'submitted',
                'event_origin' => 'user_action',
                'from_status' => 'draft',
                'to_status' => 'pending_approval',
                'reason' => 'Diajukan ke District Manager untuk persetujuan',
                'created_at' => $currentMonth->copy()->subDays(3)->setTime(9, 30, 0),
            ]);

            CallListApprovalLog::create([
                'call_list_id' => $approvedList->id,
                'actor_id' => $dm->id,
                'event' => 'approved',
                'event_origin' => 'user_action',
                'from_status' => 'pending_approval',
                'to_status' => 'approved',
                'reason' => 'Target dokter disetujui, silakan mulai jadwalkan rencana kunjungan.',
                'created_at' => $currentMonth->copy()->subDays(2)->setTime(14, 15, 0),
            ]);
        }

        // 2. Call List Draft untuk bulan depan (Contoh pengajuan yang masih dalam proses penyusunan)
        $draftList = CallList::firstOrCreate(
            [
                'owner_id' => $mr->id,
                'period_month' => $nextMonth->format('Y-m-d'),
            ],
            [
                'status' => 'draft',
                'assigned_approver_id' => $dm->id,
                'submitted_at' => null,
                'approved_at' => null,
            ]
        );

        $draftList->doctors()->syncWithoutDetaching([2, 3, 5]);

        if ($draftList->approvalLogs()->count() === 0) {
            CallListApprovalLog::create([
                'call_list_id' => $draftList->id,
                'actor_id' => $mr->id,
                'event' => 'created_draft',
                'event_origin' => 'user_action',
                'from_status' => null,
                'to_status' => 'draft',
                'reason' => 'Draft target bulan depan',
                'created_at' => Carbon::now(),
            ]);
        }
    }
}
