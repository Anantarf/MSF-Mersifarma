<?php

namespace Database\Seeders;

use App\Models\CallActual;
use App\Models\CallList;
use App\Models\CallListApprovalLog;
use App\Models\CallPlan;
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
        $mr2 = User::where('email', 'mr2@mersi.com')->first();
        $dm = User::where('email', 'dm@mersi.com')->first();
        $rsm = User::where('email', 'rsm@mersi.com')->first();

        if (! $mr || ! $dm) {
            return;
        }

        $today = Carbon::today();
        $currentMonth = $today->copy()->startOfMonth();
        $lastMonth = $today->copy()->subMonth()->startOfMonth();
        $nextMonth = $today->copy()->addMonth()->startOfMonth();

        // =========================================================================
        // 1. CALL LISTS (TARGET DOKTER)
        // =========================================================================

        // A. MR Rudi - Call List Approved Bulan Berjalan
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
        $approvedList->doctors()->syncWithoutDetaching([1, 2, 3, 4]);

        if ($approvedList->approvalLogs()->count() === 0) {
            CallListApprovalLog::create([
                'call_list_id' => $approvedList->id,
                'actor_id' => $mr->id,
                'event' => 'created_draft',
                'event_origin' => 'user_action',
                'from_status' => null,
                'to_status' => 'draft',
                'reason' => 'Draft rencana target bulanan disusun oleh MR',
                'created_at' => $currentMonth->copy()->subDays(4)->setTime(10, 0, 0),
            ]);
            CallListApprovalLog::create([
                'call_list_id' => $approvedList->id,
                'actor_id' => $mr->id,
                'event' => 'submitted',
                'event_origin' => 'user_action',
                'from_status' => 'draft',
                'to_status' => 'pending_approval',
                'reason' => 'Pengajuan target 4 dokter sasaran area Jakarta Barat & Pusat',
                'created_at' => $currentMonth->copy()->subDays(3)->setTime(9, 30, 0),
            ]);
            CallListApprovalLog::create([
                'call_list_id' => $approvedList->id,
                'actor_id' => $dm->id,
                'event' => 'approved',
                'event_origin' => 'user_action',
                'from_status' => 'pending_approval',
                'to_status' => 'approved',
                'reason' => 'Target dokter disetujui. Silakan lanjutkan ke penyusunan jadwal kunjungan.',
                'created_at' => $currentMonth->copy()->subDays(2)->setTime(14, 15, 0),
            ]);
        }

        // B. MR Rudi - Call List "Perlu Revisi" Bulan Lalu (Contoh Status Rejection di UI)
        $revisionList = CallList::firstOrCreate(
            [
                'owner_id' => $mr->id,
                'period_month' => $lastMonth->format('Y-m-d'),
            ],
            [
                'status' => 'draft',
                'assigned_approver_id' => $dm->id,
                'submitted_at' => $lastMonth->copy()->subDays(2)->setTime(11, 0, 0),
                'approved_at' => null,
            ]
        );
        $revisionList->doctors()->syncWithoutDetaching([1, 5]);

        if ($revisionList->approvalLogs()->count() === 0) {
            CallListApprovalLog::create([
                'call_list_id' => $revisionList->id,
                'actor_id' => $mr->id,
                'event' => 'created_draft',
                'event_origin' => 'user_action',
                'from_status' => null,
                'to_status' => 'draft',
                'reason' => 'Draft awal bulan lalu',
                'created_at' => $lastMonth->copy()->subDays(4)->setTime(9, 0, 0),
            ]);
            CallListApprovalLog::create([
                'call_list_id' => $revisionList->id,
                'actor_id' => $mr->id,
                'event' => 'submitted',
                'event_origin' => 'user_action',
                'from_status' => 'draft',
                'to_status' => 'pending_approval',
                'reason' => 'Diajukan ke District Manager',
                'created_at' => $lastMonth->copy()->subDays(2)->setTime(11, 0, 0),
            ]);
            CallListApprovalLog::create([
                'call_list_id' => $revisionList->id,
                'actor_id' => $dm->id,
                'event' => 'rejected',
                'event_origin' => 'user_action',
                'from_status' => 'pending_approval',
                'to_status' => 'draft',
                'reason' => 'Target dokter bedah perlu diganti dengan spesialis penyakit dalam sesuai fokus kuartal.',
                'created_at' => $lastMonth->copy()->subDays(1)->setTime(16, 30, 0),
            ]);
        }

        // C. MR Rudi - Call List Draft Bulan Depan
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

        // D. MR Ahmad (MR2) - Call List "Menunggu Persetujuan" (Antrean Approval untuk DM Joko)
        if ($mr2) {
            $pendingListMR2 = CallList::firstOrCreate(
                [
                    'owner_id' => $mr2->id,
                    'period_month' => $currentMonth->format('Y-m-d'),
                ],
                [
                    'status' => 'pending_approval',
                    'assigned_approver_id' => $dm->id,
                    'submitted_at' => Carbon::now()->subHours(4),
                    'approved_at' => null,
                ]
            );
            $pendingListMR2->doctors()->syncWithoutDetaching([3, 4, 6]);

            if ($pendingListMR2->approvalLogs()->count() === 0) {
                CallListApprovalLog::create([
                    'call_list_id' => $pendingListMR2->id,
                    'actor_id' => $mr2->id,
                    'event' => 'created_draft',
                    'event_origin' => 'user_action',
                    'from_status' => null,
                    'to_status' => 'draft',
                    'reason' => 'Draft target wilayah 2',
                    'created_at' => Carbon::now()->subHours(6),
                ]);
                CallListApprovalLog::create([
                    'call_list_id' => $pendingListMR2->id,
                    'actor_id' => $mr2->id,
                    'event' => 'submitted',
                    'event_origin' => 'user_action',
                    'from_status' => 'draft',
                    'to_status' => 'pending_approval',
                    'reason' => 'Mohon persetujuan target bulanan wilayah Jakarta Pusat & Timur.',
                    'created_at' => Carbon::now()->subHours(4),
                ]);
            }
        }

        // E. DM Joko - Call List "Menunggu Persetujuan" (Antrean Approval untuk RSM Siti)
        if ($rsm) {
            $pendingListDM = CallList::firstOrCreate(
                [
                    'owner_id' => $dm->id,
                    'period_month' => $currentMonth->format('Y-m-d'),
                ],
                [
                    'status' => 'pending_approval',
                    'assigned_approver_id' => $rsm->id,
                    'submitted_at' => Carbon::now()->subHours(2),
                    'approved_at' => null,
                ]
            );
            $pendingListDM->doctors()->syncWithoutDetaching([1, 2, 5]);

            if ($pendingListDM->approvalLogs()->count() === 0) {
                CallListApprovalLog::create([
                    'call_list_id' => $pendingListDM->id,
                    'actor_id' => $dm->id,
                    'event' => 'created_draft',
                    'event_origin' => 'user_action',
                    'from_status' => null,
                    'to_status' => 'draft',
                    'reason' => 'Draft key accounts District Manager',
                    'created_at' => Carbon::now()->subHours(3),
                ]);
                CallListApprovalLog::create([
                    'call_list_id' => $pendingListDM->id,
                    'actor_id' => $dm->id,
                    'event' => 'submitted',
                    'event_origin' => 'user_action',
                    'from_status' => 'draft',
                    'to_status' => 'pending_approval',
                    'reason' => 'Pengajuan supervisi target dokter spesialis utama regional.',
                    'created_at' => Carbon::now()->subHours(2),
                ]);
            }
        }

        // =========================================================================
        // 2. CALL PLANS (JADWAL KUNJUNGAN)
        // =========================================================================

        // Plan 1: Hari ini - dr. Budi Santoso (Terjadwal, siap dites Mulai Kunjungan / Manual)
        CallPlan::firstOrCreate(
            [
                'call_list_id' => $approvedList->id,
                'doctor_id' => 2,
                'plan_date' => $today->toDateString(),
            ],
            [
                'mr_id' => $mr->id,
                'planned_time' => '09:30:00',
                'status' => 'scheduled',
            ]
        );

        // Plan 2: Hari ini - dr. Citra Lestari (Terjadwal)
        CallPlan::firstOrCreate(
            [
                'call_list_id' => $approvedList->id,
                'doctor_id' => 3,
                'plan_date' => $today->toDateString(),
            ],
            [
                'mr_id' => $mr->id,
                'planned_time' => '14:00:00',
                'status' => 'scheduled',
            ]
        );

        // Plan 3: Kemarin - dr. Dewi Anggraini (Selesai/Completed)
        $yesterday = $today->copy()->subDay();
        if ($yesterday->isSameMonth($currentMonth)) {
            $planYesterday = CallPlan::firstOrCreate(
                [
                    'call_list_id' => $approvedList->id,
                    'doctor_id' => 4,
                    'plan_date' => $yesterday->toDateString(),
                ],
                [
                    'mr_id' => $mr->id,
                    'planned_time' => '10:00:00',
                    'status' => 'completed',
                ]
            );

            // Buat Actual yang terhubung ke Plan 3 (Planned Actual)
            $actualYesterday = CallActual::firstOrCreate(
                [
                    'doctor_id' => 4,
                    'visit_date' => $yesterday->toDateString(),
                ],
                [
                    'mr_id' => $mr->id,
                    'plan_id' => $planYesterday->id,
                    'visit_session_id' => null,
                    'visit_type' => 'planned',
                    'check_in_at' => $yesterday->copy()->setTime(10, 5, 0),
                    'check_out_at' => $yesterday->copy()->setTime(10, 40, 0),
                    'photo_url' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=600&q=80',
                    'signature_url' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="60"><path d="M10 40 Q 50 10 90 35 T 180 20" fill="none" stroke="%230f766e" stroke-width="3"/></svg>',
                    'visit_reason' => null,
                    'deviation_reason' => null,
                ]
            );
            $actualYesterday->products()->syncWithoutDetaching([1, 3]); // Mersitropic & Mersiclav
        }

        // Plan 4: Besok - dr. Citra Lestari (Dibatalkan / Cancelled - mencontohkan badge Dibatalkan)
        $tomorrow = $today->copy()->addDay();
        if ($tomorrow->isSameMonth($currentMonth)) {
            CallPlan::firstOrCreate(
                [
                    'call_list_id' => $approvedList->id,
                    'doctor_id' => 3,
                    'plan_date' => $tomorrow->toDateString(),
                ],
                [
                    'mr_id' => $mr->id,
                    'planned_time' => '11:00:00',
                    'status' => 'cancelled',
                    'cancellation_reason' => 'Dokter ada jadwal operasi mendadak di RS Jantung Harapan Kita.',
                    'cancelled_at' => Carbon::now()->subHour(),
                ]
            );
        }

        // Plan 5: 3 Hari Lagi - dr. Citra Lestari (Terjadwal ulang)
        $threeDaysLater = $today->copy()->addDays(3);
        if ($threeDaysLater->isSameMonth($currentMonth)) {
            CallPlan::firstOrCreate(
                [
                    'call_list_id' => $approvedList->id,
                    'doctor_id' => 3,
                    'plan_date' => $threeDaysLater->toDateString(),
                ],
                [
                    'mr_id' => $mr->id,
                    'planned_time' => '13:30:00',
                    'status' => 'scheduled',
                ]
            );
        }

        // =========================================================================
        // 3. CALL ACTUALS (REALISASI KUNJUNGAN LAINNYA)
        // =========================================================================

        // Actual 2: 2 Hari Lalu - dr. Citra Lestari (Unplanned / Kunjungan di Luar Rencana)
        $twoDaysAgo = $today->copy()->subDays(2);
        if ($twoDaysAgo->isSameMonth($currentMonth)) {
            $unplannedActual = CallActual::firstOrCreate(
                [
                    'doctor_id' => 3,
                    'visit_date' => $twoDaysAgo->toDateString(),
                ],
                [
                    'mr_id' => $mr->id,
                    'plan_id' => null,
                    'visit_session_id' => null,
                    'visit_type' => 'unplanned',
                    'check_in_at' => $twoDaysAgo->copy()->setTime(13, 15, 0),
                    'check_out_at' => $twoDaysAgo->copy()->setTime(13, 50, 0),
                    'photo_url' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=600&q=80',
                    'signature_url' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="60"><path d="M20 30 Q 70 50 120 20 T 170 40" fill="none" stroke="%230f766e" stroke-width="3"/></svg>',
                    'visit_reason' => 'Permintaan dokter untuk konsultasi ketersediaan obat kardiovaskular Mersiclav di apotek RS.',
                    'deviation_reason' => null,
                ]
            );
            $unplannedActual->products()->syncWithoutDetaching([3, 5]); // Mersiclav & Mersizol
        }

        // Actual 3: 4 Hari Lalu - dr. Farhan Hakim (Non Target / Dokter di luar Call List Target)
        $fourDaysAgo = $today->copy()->subDays(4);
        if ($fourDaysAgo->isSameMonth($currentMonth)) {
            $nonTargetActual = CallActual::firstOrCreate(
                [
                    'doctor_id' => 6,
                    'visit_date' => $fourDaysAgo->toDateString(),
                ],
                [
                    'mr_id' => $mr->id,
                    'plan_id' => null,
                    'visit_session_id' => null,
                    'visit_type' => 'non_target',
                    'check_in_at' => $fourDaysAgo->copy()->setTime(15, 0, 0),
                    'check_out_at' => $fourDaysAgo->copy()->setTime(15, 30, 0),
                    'photo_url' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=600&q=80',
                    'signature_url' => 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="60"><path d="M15 25 Q 60 5 110 30 T 175 15" fill="none" stroke="%230f766e" stroke-width="3"/></svg>',
                    'visit_reason' => 'Kunjungan pengenalan produk Mersidryl ke spesialis mata rujukan.',
                    'deviation_reason' => null,
                ]
            );
            $nonTargetActual->products()->syncWithoutDetaching([2]); // Mersidryl Expectorant
        }
    }
}
