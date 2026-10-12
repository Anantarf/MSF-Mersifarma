<?php

namespace Tests\Feature;

use App\Models\CallList;
use App\Models\CallPlan;
use App\Models\MasterCustomer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CallActualTest extends TestCase
{
    use DatabaseTransactions;

    protected User $mr;

    protected User $otherMr;

    protected User $dm;

    protected MasterCustomer $doctor1;

    protected MasterCustomer $doctor2;

    protected CallList $approvedCallList;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00:00'));

        $this->mr = User::where('email', 'mr@mersi.com')->firstOrFail();
        $this->otherMr = User::where('email', 'mr2@mersi.com')->firstOrFail();
        $this->dm = User::where('email', 'dm@mersi.com')->firstOrFail();
        $this->doctor1 = MasterCustomer::findOrFail(1);
        $this->doctor2 = MasterCustomer::findOrFail(2);

        $this->approvedCallList = CallList::firstOrCreate(
            ['owner_id' => $this->mr->id, 'period_month' => '2026-11-01'],
            ['assigned_approver_id' => $this->dm->id]
        );
        $this->approvedCallList->update([
            'status' => 'approved',
            'assigned_approver_id' => $this->dm->id,
            'approved_at' => Carbon::parse('2026-11-01 09:00:00'),
        ]);
        $this->approvedCallList->doctors()->sync([$this->doctor1->id, $this->doctor2->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_mr_can_record_planned_actual_and_complete_only_referenced_plan(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-12',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'plan_id' => $plan->id,
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-12',
            'check_in' => '08:30:00',
            'check_out' => '09:00:00',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/signature.png',
            'products' => [1, 3],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Call Actual completed',
                'data' => [
                    'doctor_id' => $this->doctor1->id,
                    'plan_id' => $plan->id,
                    'visit_date' => '2026-11-12',
                    'visit_type' => 'planned',
                    'status' => 'completed',
                    'products' => [1, 3],
                ],
            ]);

        $this->assertDatabaseHas('call_plans', [
            'id' => $plan->id,
            'status' => 'completed',
        ]);
    }

    public function test_direct_actual_without_plan_is_accepted_without_visit_reason(): void
    {
        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'plan_id' => null,
            'doctor_id' => $this->doctor2->id,
            'visit_date' => '2026-11-13',
            'check_in' => '10:00',
            'check_out' => '10:30',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/signature.png',
            'products' => [2],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.visit_type', 'unplanned')
            ->assertJsonPath('data.plan_id', null);
    }

    public function test_actual_accepts_compressed_data_url_evidence_from_ui(): void
    {
        $photoDataUrl = 'data:image/jpeg;base64,'.str_repeat('a', 3000);
        $signatureDataUrl = 'data:image/png;base64,'.str_repeat('b', 3000);

        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'plan_id' => null,
            'doctor_id' => $this->doctor2->id,
            'visit_date' => '2026-11-21',
            'check_in' => '10:00',
            'check_out' => '10:30',
            'photo_url' => $photoDataUrl,
            'signature_url' => $signatureDataUrl,
            'products' => [2],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.visit_type', 'unplanned');

        $this->assertDatabaseHas('call_actuals', [
            'doctor_id' => $this->doctor2->id,
            'visit_date' => '2026-11-21',
            'photo_url' => $photoDataUrl,
            'signature_url' => $signatureDataUrl,
        ]);
    }

    public function test_pdf_03_duplicate_actual_for_same_doctor_and_date_is_rejected_across_mrs(): void
    {
        $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-14',
            'check_in' => '08:00',
            'check_out' => '08:20',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/signature.png',
            'products' => [1],
        ])->assertStatus(201);

        $response = $this->actingAs($this->otherMr)->postJson('/api/call-actuals', [
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-14',
            'check_in' => '09:00',
            'check_out' => '09:20',
            'photo_url' => 'https://example.test/other-photo.jpg',
            'signature_url' => 'https://example.test/other-signature.png',
            'products' => [2],
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error_code' => 'VISIT_ALREADY_EXISTS',
            ]);
    }

    public function test_pdf_04_actual_without_photo_is_rejected_and_creates_no_partial_record(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-15',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'plan_id' => $plan->id,
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-15',
            'check_in' => '08:30:00',
            'check_out' => '09:00:00',
            'photo_url' => '',
            'signature_url' => 'https://example.test/signature.png',
            'products' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'PHOTO_REQUIRED',
            ])
            ->assertJsonPath('errors.photo_url.0', 'Foto kunjungan wajib diisi.');

        $this->assertDatabaseMissing('call_actuals', [
            'plan_id' => $plan->id,
        ]);
        $this->assertDatabaseHas('call_plans', [
            'id' => $plan->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_actual_without_signature_is_rejected(): void
    {
        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-16',
            'check_in' => '08:00',
            'check_out' => '08:30',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => '',
            'products' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'SIGNATURE_REQUIRED',
            ]);
    }

    public function test_actual_without_products_is_rejected(): void
    {
        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-16',
            'check_in' => '08:00',
            'check_out' => '08:30',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/sig.png',
            'products' => [],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'PRODUCT_REQUIRED',
            ]);
    }

    public function test_without_plan_non_target_doctor_is_classified_as_non_target(): void
    {
        $doctor3 = MasterCustomer::findOrFail(3);

        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'plan_id' => null,
            'doctor_id' => $doctor3->id,
            'visit_date' => '2026-11-17',
            'check_in' => '11:00',
            'check_out' => '11:30',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/sig.png',
            'products' => [1],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.visit_type', 'non_target');
    }

    public function test_doctor_plan_mismatch_is_rejected(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-18',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'plan_id' => $plan->id,
            'doctor_id' => $this->doctor2->id,
            'visit_date' => '2026-11-18',
            'check_in' => '09:00',
            'check_out' => '09:30',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/sig.png',
            'products' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'DOCTOR_PLAN_MISMATCH',
            ]);
    }

    public function test_get_call_actuals_is_scoped_to_owner(): void
    {
        $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-19',
            'check_in' => '08:00',
            'check_out' => '08:30',
            'photo_url' => 'https://example.test/p1.jpg',
            'signature_url' => 'https://example.test/s1.png',
            'products' => [1],
        ])->assertStatus(201);

        $responseMr = $this->actingAs($this->mr)->getJson('/api/call-actuals?date=2026-11-19');
        $responseMr->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $responseOther = $this->actingAs($this->otherMr)->getJson('/api/call-actuals?date=2026-11-19');
        $responseOther->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_non_mr_cannot_record_direct_actual(): void
    {
        $response = $this->actingAs($this->dm)->postJson('/api/call-actuals', [
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-20',
            'check_in' => '08:00',
            'check_out' => '08:30',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/signature.png',
            'products' => [1],
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('call_actuals', [
            'mr_id' => $this->dm->id,
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-20',
        ]);
    }

    public function test_manager_can_view_subordinate_mr_actuals_and_evidence(): void
    {
        $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-19',
            'check_in' => '08:00',
            'check_out' => '08:30',
            'photo_url' => 'https://example.test/photo-bukti.jpg',
            'signature_url' => 'https://example.test/signature-dokter.png',
            'products' => [1],
        ])->assertStatus(201);

        // 1. DM direct manager of MR can view
        $dmResponse = $this->actingAs($this->dm)->getJson('/api/call-actuals?date=2026-11-19');
        $dmResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'mr_name' => $this->mr->name,
                'photo_url' => 'https://example.test/photo-bukti.jpg',
                'signature_url' => 'https://example.test/signature-dokter.png',
            ]);

        // 2. DM2 (Hendra) non-manager of MR cannot view
        $dm2 = User::where('email', 'dm2@mersi.com')->firstOrFail();
        $dm2Response = $this->actingAs($dm2)->getJson('/api/call-actuals?date=2026-11-19');
        $dm2Response->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // 3. RSM (Siti Rahma) can view subordinate MR
        $rsm = User::where('email', 'rsm@mersi.com')->firstOrFail();
        $rsmResponse = $this->actingAs($rsm)->getJson('/api/call-actuals?date=2026-11-19');
        $rsmResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 4. MM (Budi Santoso) can view national MR
        $mm = User::where('email', 'mm@mersi.com')->firstOrFail();
        $mmResponse = $this->actingAs($mm)->getJson('/api/call-actuals?date=2026-11-19');
        $mmResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_manager_filter_scoping_and_forbidden_mr_access(): void
    {
        $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-19',
            'check_in' => '08:00',
            'check_out' => '08:30',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/sig.png',
            'products' => [1],
        ])->assertStatus(201);

        // Filter valid subordinate MR
        $response = $this->actingAs($this->dm)->getJson("/api/call-actuals?mr_id={$this->mr->id}&date=2026-11-19");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // DM trying to filter non-subordinate MR
        $dm2 = User::where('email', 'dm2@mersi.com')->firstOrFail();
        $otherMrNotInDm = User::create([
            'name' => 'MR Cabang Lain',
            'username' => 'mrlain',
            'email' => 'mrlain@mersi.com',
            'password' => bcrypt('password'),
            'role' => 'mr',
            'manager_id' => $dm2->id,
        ]);

        $unauthorizedFilter = $this->actingAs($this->dm)->getJson("/api/call-actuals?mr_id={$otherMrNotInDm->id}");
        $unauthorizedFilter->assertStatus(403);

        // MR trying to query another MR's data
        $mrAttempt = $this->actingAs($this->mr)->getJson("/api/call-actuals?mr_id={$this->otherMr->id}");
        $mrAttempt->assertStatus(403);
    }

    public function test_planned_actual_rejects_visit_date_different_from_plan_date_with_reschedule_required(): void
    {
        $plan = CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-12',
            'planned_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'plan_id' => $plan->id,
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-14', // Beda tanggal dari jadwal
            'check_in' => '08:30:00',
            'check_out' => '09:00:00',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/signature.png',
            'products' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'RESCHEDULE_REQUIRED')
            ->assertJson([
                'success' => false,
                'message' => 'Tanggal kunjungan tidak sesuai jadwal. Reschedule jadwal terlebih dahulu.',
            ]);
    }

    public function test_unplanned_actual_rejects_doctor_with_active_scheduled_plan_with_reschedule_required(): void
    {
        // Dokter 1 punya Call Plan scheduled aktif di bulan November
        CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-20',
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        // MR mencoba mengisi kunjungan luar rencana (plan_id null) untuk dokter 1
        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'plan_id' => null,
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-21',
            'check_in' => '11:00:00',
            'check_out' => '11:30:00',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/signature.png',
            'products' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'RESCHEDULE_REQUIRED');
    }

    public function test_unplanned_actual_allows_doctor_with_stale_past_scheduled_plan(): void
    {
        // Dokter 1 punya Call Plan scheduled yang tanggalnya sudah lewat (kemarin, 2026-11-19)
        CallPlan::create([
            'call_list_id' => $this->approvedCallList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor1->id,
            'plan_date' => '2026-11-19',
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);

        // MR mengisi kunjungan luar rencana pada hari ini (2026-11-20)
        // Karena jadwal 2026-11-19 sudah basi/terlewat, tidak boleh memblokir dengan RESCHEDULE_REQUIRED
        $response = $this->actingAs($this->mr)->postJson('/api/call-actuals', [
            'plan_id' => null,
            'doctor_id' => $this->doctor1->id,
            'visit_date' => '2026-11-20',
            'check_in' => '11:00:00',
            'check_out' => '11:30:00',
            'photo_url' => 'https://example.test/photo.jpg',
            'signature_url' => 'https://example.test/signature.png',
            'products' => [1],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.visit_type', 'unplanned');
    }

    public function test_doctor_api_supports_all_mcl_query_param(): void
    {
        // Set user district
        $this->mr->district = 'Jakarta Selatan';
        $this->mr->save();

        $defaultResponse = $this->actingAs($this->mr)->getJson('/api/doctors');
        $defaultResponse->assertStatus(200);

        $allResponse = $this->actingAs($this->mr)->getJson('/api/doctors?all=1');
        $allResponse->assertStatus(200);

        $this->assertGreaterThanOrEqual(
            count($defaultResponse->json('data')),
            count($allResponse->json('data'))
        );
    }
}
