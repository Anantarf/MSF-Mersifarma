<?php

namespace Tests\Feature;

use App\Models\CallList;
use App\Models\CallPlan;
use App\Models\MasterCustomer;
use App\Models\User;
use App\Models\VisitSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CallPlanTest extends TestCase
{
    use DatabaseTransactions;

    protected User $mr;

    protected User $otherMr;

    protected User $dm;

    protected MasterCustomer $doctor1;

    protected MasterCustomer $doctor2;

    protected MasterCustomer $unlistedDoctor;

    protected CallList $approvedCallList;

    protected CallList $draftCallList;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mr = User::where('email', 'mr@mersi.com')->firstOrFail();
        $this->otherMr = User::where('email', 'mr2@mersi.com')->firstOrFail();
        $this->dm = User::where('email', 'dm@mersi.com')->firstOrFail();

        $doctors = MasterCustomer::take(3)->get();
        $this->doctor1 = $doctors[0];
        $this->doctor2 = $doctors[1];
        $this->unlistedDoctor = $doctors[2];

        // Ambil atau buat Call List yang disetujui (Approved) untuk periode 2026-11
        $this->approvedCallList = CallList::firstOrCreate(
            [
                'owner_id' => $this->mr->id,
                'period_month' => '2026-11-01',
            ],
            [
                'status' => 'approved',
                'assigned_approver_id' => $this->dm->id,
                'approved_at' => Carbon::now(),
            ]
        );
        $this->approvedCallList->status = 'approved';
        $this->approvedCallList->save();
        $this->approvedCallList->doctors()->sync([$this->doctor1->id, $this->doctor2->id]);

        // Ambil atau buat Call List yang masih Draft untuk periode 2026-12
        $this->draftCallList = CallList::firstOrCreate(
            [
                'owner_id' => $this->mr->id,
                'period_month' => '2026-12-01',
            ],
            [
                'status' => 'draft',
                'assigned_approver_id' => $this->dm->id,
            ]
        );
        $this->draftCallList->status = 'draft';
        $this->draftCallList->save();
        $this->draftCallList->doctors()->sync([$this->doctor1->id]);
    }

    /**
     * Happy Path: MR dapat membuat Call Plan dari Call List Approved.
     */
    public function test_mr_can_create_call_plan_from_approved_call_list(): void
    {
        $response = $this->actingAs($this->mr)
            ->postJson('/api/call-plans', [
                'call_list_id' => $this->approvedCallList->id,
                'doctor_id' => $this->doctor1->id,
                'plan_date' => '2026-11-12',
                'planned_time' => '09:00:00',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Call Plan created',
                'data' => [
                    'call_list_id' => $this->approvedCallList->id,
                    'doctor_id' => $this->doctor1->id,
                    'plan_date' => '2026-11-12',
                    'planned_time' => '09:00:00',
                    'status' => 'scheduled',
                ],
            ]);

        $this->assertDatabaseHas('call_plans', [
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-12',
            'status' => 'scheduled',
        ]);
    }

    /**
     * Skenario PDF-02: Membuat Plan dari Call List yang belum Approved (Draft) DITOLAK.
     */
    public function test_pdf_02_create_call_plan_from_non_approved_list_is_rejected(): void
    {
        $response = $this->actingAs($this->mr)
            ->postJson('/api/call-plans', [
                'call_list_id' => $this->draftCallList->id,
                'doctor_id' => $this->doctor1->id,
                'plan_date' => '2026-12-05',
                'planned_time' => '10:00:00',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'CALL_LIST_NOT_APPROVED',
            ]);

        $this->assertDatabaseMissing('call_plans', [
            'call_list_id' => $this->draftCallList->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-12-05',
        ]);
    }

    /**
     * Dokter yang bukan anggota Call List ditolak (DOCTOR_PLAN_MISMATCH).
     */
    public function test_doctor_not_in_call_list_is_rejected(): void
    {
        $response = $this->actingAs($this->mr)
            ->postJson('/api/call-plans', [
                'call_list_id' => $this->approvedCallList->id,
                'doctor_id' => $this->unlistedDoctor->id,
                'plan_date' => '2026-11-15',
                'planned_time' => '11:00:00',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'DOCTOR_PLAN_MISMATCH',
            ]);
    }

    /**
     * Tanggal Plan di luar bulan Call List ditolak.
     */
    public function test_plan_date_outside_call_list_month_is_rejected(): void
    {
        // approvedCallList periode 2026-11, input tanggal 2026-10-31
        $response = $this->actingAs($this->mr)
            ->postJson('/api/call-plans', [
                'call_list_id' => $this->approvedCallList->id,
                'doctor_id' => $this->doctor1->id,
                'plan_date' => '2026-10-31',
                'planned_time' => '09:00:00',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_FAILED',
            ]);
    }

    /**
     * Jadwal duplikat untuk dokter dan tanggal yang sama ditolak (PLAN_DATE_CONFLICT - 409).
     */
    public function test_duplicate_active_call_plan_for_same_doctor_and_date_is_rejected(): void
    {
        // Buat plan pertama
        CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-12',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        // Coba buat plan kedua untuk dokter dan tanggal yang sama
        $response = $this->actingAs($this->mr)
            ->postJson('/api/call-plans', [
                'call_list_id' => $this->approvedCallList->id,
                'doctor_id' => $this->doctor1->id,
                'plan_date' => '2026-11-12',
                'planned_time' => '14:00:00',
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error_code' => 'PLAN_DATE_CONFLICT',
            ]);
    }

    /**
     * MR lain tidak boleh membuat Plan dari Call List orang lain.
     */
    public function test_cannot_create_plan_from_another_user_call_list(): void
    {
        $response = $this->actingAs($this->otherMr)
            ->postJson('/api/call-plans', [
                'call_list_id' => $this->approvedCallList->id,
                'doctor_id' => $this->doctor1->id,
                'plan_date' => '2026-11-12',
                'planned_time' => '09:00:00',
            ]);

        $response->assertStatus(403);
    }

    /**
     * GET /api/call-plans mengembalikan daftar Call Plan milik sendiri dengan filter tanggal opsional.
     */
    public function test_get_call_plans_is_scoped_to_owner(): void
    {
        $initialCount = CallPlan::where('mr_id', $this->mr->id)->count();

        CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-12',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor2->id,
            'plan_date' => '2026-11-15',
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        // Cek tanpa filter
        $responseAll = $this->actingAs($this->mr)->getJson('/api/call-plans');
        $responseAll->assertStatus(200)
            ->assertJsonCount($initialCount + 2, 'data');

        // Cek dengan filter tanggal
        $responseDate = $this->actingAs($this->mr)->getJson('/api/call-plans?date=2026-11-12');
        $responseDate->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.doctor_id', $this->doctor1->id);

        // MR lain tidak melihat plan milik MR ini
        $responseOther = $this->actingAs($this->otherMr)->getJson('/api/call-plans');
        $responseOther->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_non_mr_cannot_create_call_plan_even_with_owned_approved_list(): void
    {
        $managerList = CallList::firstOrCreate(
            [
                'owner_id' => $this->dm->id,
                'period_month' => '2027-08-01',
            ],
            [
                'status' => 'approved',
                'assigned_approver_id' => User::where('email', 'rsm@mersi.com')->firstOrFail()->id,
                'approved_at' => Carbon::now(),
            ]
        );
        $managerList->update([
            'status' => 'approved',
            'approved_at' => Carbon::now(),
        ]);
        $managerList->doctors()->sync([$this->doctor1->id]);

        $response = $this->actingAs($this->dm)
            ->postJson('/api/call-plans', [
                'call_list_id' => $managerList->id,
                'doctor_id' => $this->doctor1->id,
                'plan_date' => '2027-08-12',
                'planned_time' => '09:00:00',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('call_plans', [
            'call_list_id' => $managerList->id,
            'mr_id' => $this->dm->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2027-08-12',
        ]);
    }

    public function test_mr_can_cancel_scheduled_call_plan_with_valid_reason(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-20',
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/cancel", [
                'reason' => 'Dokter ada jadwal simposium mendadak di luar kota',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rencana kunjungan berhasil dibatalkan',
                'data' => [
                    'id' => $plan->id,
                    'status' => 'cancelled',
                    'cancellation_reason' => 'Dokter ada jadwal simposium mendadak di luar kota',
                ],
            ]);

        $this->assertDatabaseHas('call_plans', [
            'id' => $plan->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Dokter ada jadwal simposium mendadak di luar kota',
        ]);
    }

    public function test_cannot_cancel_call_plan_belonging_to_another_mr(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-21',
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->otherMr)
            ->patchJson("/api/call-plans/{$plan->id}/cancel", [
                'reason' => 'Mencoba membatalkan jadwal rekan sales lain',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('call_plans', [
            'id' => $plan->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_cannot_cancel_call_plan_with_invalid_reason(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-22',
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/cancel", [
                'reason' => 'no', // kurang dari 3 karakter
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('call_plans', [
            'id' => $plan->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_cannot_cancel_completed_call_plan(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-23',
            'planned_time' => '10:00:00',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/cancel", [
                'reason' => 'Membatalkan rencana yang sudah completed',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('call_plans', [
            'id' => $plan->id,
            'status' => 'completed',
        ]);
    }

    public function test_cancelling_call_plan_frees_up_slot_for_another_plan(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-24',
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        // Batalkan jadwal pertama
        $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/cancel", [
                'reason' => 'Revisi tanggal kunjungan dokter',
            ])
            ->assertStatus(200);

        // Buat jadwal baru di tanggal yang sama setelah dibatalkan (slot sudah bebas)
        $newResponse = $this->actingAs($this->mr)
            ->postJson('/api/call-plans', [
                'call_list_id' => $this->approvedCallList->id,
                'doctor_id' => $this->doctor1->id,
                'plan_date' => '2026-11-24',
                'planned_time' => '14:00:00',
            ]);

        $newResponse->assertStatus(201);
    }

    public function test_doctor_availability_endpoint_returns_busy_dates_and_respects_privacy(): void
    {
        CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-25',
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)
            ->getJson("/api/doctors/{$this->doctor1->id}/availability?month=2026-11");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'doctor_id' => $this->doctor1->id,
                    'month' => '2026-11',
                    'busy_dates' => ['2026-11-25'],
                ],
            ]);

        // Verifikasi tidak ada nama MR lain, produk, atau data sensitif yang bocor
        $response->assertJsonMissing(['mr_id' => $this->mr->id]);
        $response->assertJsonMissing(['mr_name' => $this->mr->name]);
    }

    public function test_stale_call_plan_past_plan_date_is_automatically_expired_on_get_plans(): void
    {
        $pastDate = Carbon::yesterday('Asia/Jakarta')->toDateString();

        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor2->id,
            'plan_date' => $pastDate,
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)->getJson('/api/call-plans');

        $response->assertStatus(200);

        $this->assertEquals('expired', $plan->fresh()->status);
        $this->assertTrue($plan->fresh()->isExpired());
    }

    public function test_artisan_command_msf_expire_plans_expires_stale_plans(): void
    {
        $pastDate = Carbon::yesterday('Asia/Jakarta')->toDateString();

        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor2->id,
            'plan_date' => $pastDate,
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        $this->artisan('msf:expire-plans')
            ->expectsOutputToContain('Expired 1 stale call plan(s).')
            ->assertSuccessful();

        $this->assertEquals('expired', $plan->fresh()->status);
    }

    public function test_expired_plan_frees_up_slot_and_doctor_can_be_rescheduled(): void
    {
        $currentMonth = Carbon::today('Asia/Jakarta')->format('Y-m');
        $callList = CallList::firstOrCreate(
            [
                'owner_id' => $this->mr->id,
                'period_month' => "{$currentMonth}-01",
            ],
            [
                'status' => 'approved',
                'assigned_approver_id' => $this->dm->id,
                'approved_at' => Carbon::now(),
            ]
        );
        $callList->status = 'approved';
        $callList->save();
        $callList->doctors()->syncWithoutDetaching([$this->doctor2->id]);

        $pastDate = Carbon::yesterday('Asia/Jakarta')->toDateString();
        $plan = CallPlan::create([
            'call_list_id' => $callList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor2->id,
            'plan_date' => $pastDate,
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        // Jalankan expiry
        $this->artisan('msf:expire-plans')->assertSuccessful();
        $this->assertEquals('expired', $plan->fresh()->status);

        // Buat jadwal baru untuk dokter yang sama di tanggal besok
        $rescheduleDate = Carbon::tomorrow('Asia/Jakarta')->toDateString();
        $response = $this->actingAs($this->mr)
            ->postJson('/api/call-plans', [
                'call_list_id' => $callList->id,
                'doctor_id' => $this->doctor2->id,
                'plan_date' => $rescheduleDate,
                'planned_time' => '11:00:00',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'scheduled',
                ],
            ]);
    }

    public function test_mr_can_reschedule_call_plan_with_valid_reason_and_new_date(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-10',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/reschedule", [
                'plan_date' => '2026-11-15',
                'planned_time' => '14:00',
                'reason' => 'Dokter meminta penyesuaian hari karena ada jadwal visit ruangan',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rencana kunjungan berhasil dijadwalkan ulang',
                'data' => [
                    'id' => $plan->id,
                    'plan_date' => '2026-11-15',
                    'planned_time' => '14:00:00',
                    'status' => 'scheduled',
                    'reschedule_reason' => 'Dokter meminta penyesuaian hari karena ada jadwal visit ruangan',
                ],
            ]);

        $fresh = $plan->fresh();
        $this->assertEquals('2026-11-15', Carbon::parse($fresh->plan_date)->format('Y-m-d'));
        $this->assertNotNull($fresh->rescheduled_at);
    }

    public function test_reschedule_rejects_date_outside_call_list_month(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-10',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/reschedule", [
                'plan_date' => '2026-12-05',
                'reason' => 'Dokter meminta pindah ke bulan depan',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'DATE_OUT_OF_PERIOD');
    }

    public function test_reschedule_rejects_conflicting_date_with_another_plan(): void
    {
        $plan1 = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-10',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $otherCallList = CallList::firstOrCreate(
            ['owner_id' => $this->otherMr->id, 'period_month' => '2026-11-01'],
            ['status' => 'approved', 'assigned_approver_id' => $this->dm->id, 'approved_at' => now()]
        );
        $otherCallList->status = 'approved';
        $otherCallList->save();
        $otherCallList->doctors()->syncWithoutDetaching([$this->doctor1->id]);

        // Plan lain untuk dokter yang sama pada tanggal 2026-11-18
        CallPlan::create([
            'call_list_id' => $otherCallList->id,
            'mr_id' => $this->otherMr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-18',
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan1->id}/reschedule", [
                'plan_date' => '2026-11-18',
                'reason' => 'Ingin pindah ke tanggal 18',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('error_code', 'PLAN_DATE_CONFLICT');
    }

    public function test_cannot_reschedule_plan_belonging_to_another_mr(): void
    {
        $otherCallList = CallList::firstOrCreate(
            ['owner_id' => $this->otherMr->id, 'period_month' => '2026-11-01'],
            ['status' => 'approved', 'assigned_approver_id' => $this->dm->id, 'approved_at' => now()]
        );
        $otherCallList->status = 'approved';
        $otherCallList->save();
        $otherCallList->doctors()->syncWithoutDetaching([$this->doctor1->id]);

        $plan = CallPlan::create([
            'call_list_id' => $otherCallList->id,
            'mr_id' => $this->otherMr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-10',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/reschedule", [
                'plan_date' => '2026-11-15',
                'reason' => 'Mencoba mengubah plan milik MR lain',
            ]);

        $response->assertStatus(403);
    }

    public function test_reschedule_rejects_plan_with_active_visit_session(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-10',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);
        VisitSession::create([
            'mr_id' => $this->mr->id,
            'plan_id' => $plan->id,
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-10',
            'check_in_at' => '2026-11-10 09:00:00',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/reschedule", [
                'plan_date' => '2026-11-15',
                'reason' => 'Dokter minta pindah hari',
            ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'ACTIVE_SESSION_CONFLICT');

        $this->assertSame('2026-11-10', Carbon::parse($plan->fresh()->plan_date)->toDateString());
    }

    public function test_reschedule_rejects_plan_that_is_not_scheduled(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-10',
            'planned_time' => '09:00:00',
            'status' => 'cancelled',
        ]);

        $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/reschedule", [
                'plan_date' => '2026-11-15',
                'reason' => 'Mencoba memindahkan plan batal',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVALID_STATUS');

        $this->assertSame('2026-11-10', Carbon::parse($plan->fresh()->plan_date)->toDateString());
    }

    public function test_non_mr_cannot_reschedule_plan(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-10',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->dm)
            ->patchJson("/api/call-plans/{$plan->id}/reschedule", [
                'plan_date' => '2026-11-15',
                'reason' => 'Manajer mencoba mengubah jadwal',
            ])
            ->assertStatus(403);

        $this->assertSame('2026-11-10', Carbon::parse($plan->fresh()->plan_date)->toDateString());
    }

    public function test_reschedule_rejects_date_before_today(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-10',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->mr)
            ->patchJson("/api/call-plans/{$plan->id}/reschedule", [
                'plan_date' => Carbon::yesterday('Asia/Jakarta')->toDateString(),
                'reason' => 'Mencoba memindahkan ke tanggal lampau',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_FAILED');

        $this->assertSame('2026-11-10', Carbon::parse($plan->fresh()->plan_date)->toDateString());
    }

    public function test_create_plan_rejects_date_before_today_and_creates_no_record(): void
    {
        $yesterday = Carbon::yesterday('Asia/Jakarta')->toDateString();

        $this->actingAs($this->mr)
            ->postJson('/api/call-plans', [
                'call_list_id' => $this->approvedCallList->id,
                'doctor_id' => $this->doctor1->id,
                'plan_date' => $yesterday,
                'planned_time' => '09:00:00',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_FAILED')
            ->assertJsonPath('message', 'Tanggal rencana kunjungan tidak boleh sebelum hari ini.');

        $this->assertDatabaseMissing('call_plans', [
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => $yesterday,
        ]);
    }
}
