<?php

namespace Tests\Feature;

use App\Models\CallList;
use App\Models\CallPlan;
use App\Models\MasterCustomer;
use App\Models\User;
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
            ->assertJsonCount(2, 'data');

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
}
