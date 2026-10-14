<?php

namespace Tests\Feature;

use App\Models\CallActual;
use App\Models\CallActualProduct;
use App\Models\ManagerialCampaign;
use App\Models\MasterCustomer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ManagerialCampaignTest extends TestCase
{
    use DatabaseTransactions;

    protected User $mr;

    protected User $dm;

    protected User $dm2;

    protected User $rsm;

    protected ?User $mm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mr = User::where('email', 'mr@mersi.com')->first();
        $this->dm = User::where('email', 'dm@mersi.com')->first();
        $this->dm2 = User::where('email', 'dm2@mersi.com')->first() ?? $this->dm;
        $this->rsm = User::where('email', 'rsm@mersi.com')->first();
        $this->mm = User::where('role', 'mm')->first() ?? User::where('email', 'mm@mersi.com')->first();
    }

    public function test_dm_can_create_managerial_campaign(): void
    {
        $products = Product::take(2)->pluck('id')->toArray();
        $doctors = MasterCustomer::take(3)->pluck('id')->toArray();

        $response = $this->actingAs($this->dm)->postJson('/api/managerial-campaigns', [
            'title' => 'Program Fokus Pediatri Jaksel',
            'district_name' => 'Jakarta Selatan',
            'specialization' => 'Spesialis Anak',
            'duration_months' => 2,
            'product_ids' => $products,
            'doctor_ids' => $doctors,
            'notes' => 'Fokus edukasi antipiretik dan hidrasi oral.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Program Fokus Pediatri Jaksel',
                    'district_name' => 'Jakarta Selatan',
                    'status' => 'pending_approval',
                    'doctor_count' => 3,
                    'product_count' => 2,
                ],
            ]);

        $campaignId = $response->json('data.id');
        $this->assertDatabaseHas('managerial_campaigns', [
            'id' => $campaignId,
            'created_by' => $this->dm->id,
            'status' => 'pending_approval',
            'duration_months' => 2,
        ]);

        $this->assertDatabaseHas('campaign_products', [
            'campaign_id' => $campaignId,
            'product_id' => $products[0],
        ]);

        $this->assertDatabaseHas('campaign_doctors', [
            'campaign_id' => $campaignId,
            'doctor_id' => $doctors[0],
        ]);
    }

    public function test_dm_campaign_district_is_taken_from_authenticated_user(): void
    {
        $products = Product::take(1)->pluck('id')->toArray();
        $doctors = MasterCustomer::where('district', $this->dm->district)->take(1)->pluck('id')->toArray();
        $this->assertNotEmpty($doctors);

        $response = $this->actingAs($this->dm)->postJson('/api/managerial-campaigns', [
            'title' => 'Program dengan distrik yang dimanipulasi',
            'district_name' => 'Regional',
            'duration_months' => 1,
            'product_ids' => $products,
            'doctor_ids' => $doctors,
        ]);

        $response->assertCreated()->assertJsonPath('data.district_name', $this->dm->district);
    }

    public function test_mr_cannot_create_managerial_campaign(): void
    {
        $response = $this->actingAs($this->mr)->postJson('/api/managerial-campaigns', [
            'title' => 'Program Ilegal MR',
            'duration_months' => 1,
            'product_ids' => [1],
            'doctor_ids' => [1],
        ]);

        $response->assertStatus(403);
    }

    public function test_campaign_rejects_invalid_duration(): void
    {
        $response = $this->actingAs($this->dm)->postJson('/api/managerial-campaigns', [
            'title' => 'Program Durasi Lebih dari 3 Bulan',
            'duration_months' => 4,
            'product_ids' => [1],
            'doctor_ids' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_FAILED',
            ]);
    }

    public function test_direct_manager_can_approve_campaign(): void
    {
        $products = Product::take(2)->pluck('id')->toArray();
        $doctors = MasterCustomer::take(2)->pluck('id')->toArray();

        $campaign = ManagerialCampaign::create([
            'title' => 'Program Kampanye Jaksel',
            'district_name' => 'Jakarta Selatan',
            'duration_months' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'pending_approval',
            'created_by' => $this->dm->id,
        ]);
        $campaign->products()->sync($products);
        $campaign->doctors()->sync($doctors);

        $response = $this->actingAs($this->rsm)->patchJson("/api/managerial-campaigns/{$campaign->id}/review", [
            'decision' => 'approved',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $campaign->id,
                    'status' => 'approved',
                    'approved_by' => $this->rsm->id,
                ],
            ]);

        $this->assertDatabaseHas('managerial_campaigns', [
            'id' => $campaign->id,
            'status' => 'approved',
            'approved_by' => $this->rsm->id,
        ]);
    }

    public function test_non_direct_manager_cannot_review_campaign(): void
    {
        $campaign = ManagerialCampaign::create([
            'title' => 'Program Kampanye Jaksel',
            'district_name' => 'Jakarta Selatan',
            'duration_months' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'pending_approval',
            'created_by' => $this->dm->id,
        ]);

        // Coba di-review oleh sesama DM atau MR
        $response = $this->actingAs($this->mr)->patchJson("/api/managerial-campaigns/{$campaign->id}/review", [
            'decision' => 'approved',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'APPROVAL_NOT_ALLOWED',
            ]);
    }

    public function test_direct_manager_can_reject_campaign_with_reason(): void
    {
        $campaign = ManagerialCampaign::create([
            'title' => 'Program Kampanye Jaksel',
            'district_name' => 'Jakarta Selatan',
            'duration_months' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'pending_approval',
            'created_by' => $this->dm->id,
        ]);

        $response = $this->actingAs($this->rsm)->patchJson("/api/managerial-campaigns/{$campaign->id}/review", [
            'decision' => 'rejected',
            'reason' => 'Perbaiki fokus produk ke kategori sirup anak.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $campaign->id,
                    'status' => 'rejected',
                    'rejection_reason' => 'Perbaiki fokus produk ke kategori sirup anak.',
                ],
            ]);

        $this->assertDatabaseHas('managerial_campaigns', [
            'id' => $campaign->id,
            'status' => 'rejected',
            'rejection_reason' => 'Perbaiki fokus produk ke kategori sirup anak.',
        ]);
    }

    public function test_campaign_progress_counts_each_priority_product_visit_once(): void
    {
        $products = Product::take(2)->get();
        $doctors = MasterCustomer::take(3)->get();

        $campaign = ManagerialCampaign::create([
            'title' => 'Program Uji Progress',
            'district_name' => 'Jakarta Selatan',
            'duration_months' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'approved',
            'created_by' => $this->dm->id,
            'approved_by' => $this->rsm->id,
        ]);
        $campaign->products()->sync($products->pluck('id')->toArray());
        $campaign->doctors()->sync($doctors->pluck('id')->toArray());

        // Simulasikan satu kunjungan yang membahas dua produk prioritas.
        $actual = CallActual::create([
            'mr_id' => $this->mr->id,
            'doctor_id' => $doctors[0]->id,
            'visit_date' => now()->toDateString(),
            'check_in_at' => now()->setTime(9, 0, 0),
            'check_out_at' => now()->setTime(9, 30, 0),
            'visit_type' => 'unplanned',
            'photo_url' => 'http://example.com/photo.jpg',
            'signature_url' => 'http://example.com/signature.png',
        ]);
        CallActualProduct::create([
            'call_actual_id' => $actual->id,
            'product_id' => $products[0]->id,
        ]);
        CallActualProduct::create([
            'call_actual_id' => $actual->id,
            'product_id' => $products[1]->id,
        ]);

        $response = $this->actingAs($this->dm)->getJson("/api/managerial-campaigns/{$campaign->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'campaign_id' => $campaign->id,
                    'total_doctors' => 3,
                    'visited_doctors_count' => 1,
                    'unvisited_doctors_count' => 2,
                    'reach_percentage' => 33.3,
                    'focus_detailing_count' => 1,
                ],
            ]);
    }

    public function test_executive_summary_accessible_by_rsm(): void
    {
        $response = $this->actingAs($this->rsm)->getJson('/api/managerial-campaigns/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'viewer_role' => 'rsm',
                    'summary_scope' => 'Regional Executive Summary',
                ],
            ]);
    }

    public function test_active_recommendations_returned_for_mr_district(): void
    {
        $products = Product::take(1)->get();
        $doctor = MasterCustomer::first();

        $campaign = ManagerialCampaign::create([
            'title' => 'Program Aktif Hari Ini',
            'district_name' => $this->mr->district ?? 'Jakarta Selatan',
            'duration_months' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'approved',
            'created_by' => $this->dm->id,
            'approved_by' => $this->rsm->id,
        ]);
        $campaign->products()->sync($products->pluck('id')->toArray());
        $campaign->doctors()->sync([$doctor->id => ['target_visits' => 1]]);

        $response = $this->actingAs($this->mr)->getJson('/api/managerial-campaigns/recommendations');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals($doctor->id, $data[0]['doctor_id']);
        $this->assertEquals('Rekomendasi Distrik', $data[0]['badge']);
    }

    public function test_dm_cannot_propose_cross_district_doctors(): void
    {
        $products = Product::take(2)->pluck('id')->toArray();
        // Dokter dari distrik Jakarta Timur
        $otherDistrictDoctor = MasterCustomer::where('district', '!=', $this->dm->district)->first();

        if (! $otherDistrictDoctor) {
            $otherDistrictDoctor = MasterCustomer::create([
                'name' => 'Dr. Luar Distrik',
                'specialization' => 'Spesialis Penyakit Dalam',
                'district' => 'Surabaya',
                'address' => 'Jl. Pahlawan',
                'phone' => '081234567899',
            ]);
        }

        $response = $this->actingAs($this->dm)->postJson('/api/managerial-campaigns', [
            'title' => 'Program Ilegal Lintas Distrik',
            'district_name' => $this->dm->district,
            'duration_months' => 2,
            'product_ids' => $products,
            'doctor_ids' => [$otherDistrictDoctor->id],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'CROSS_DISTRICT_DOCTOR',
            ]);
    }

    public function test_rsm_can_create_regional_campaign_without_doctors(): void
    {
        $products = Product::take(2)->pluck('id')->toArray();

        $response = $this->actingAs($this->rsm)->postJson('/api/managerial-campaigns', [
            'title' => 'Arahan Fokus Regional Q4',
            'duration_months' => 3,
            'product_ids' => $products,
            'doctor_ids' => [],
            'specialization' => 'Spesialis Anak',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Arahan Fokus Regional Q4',
                    'district_name' => 'Regional',
                    'status' => 'approved',
                    'doctor_count' => 0,
                    'product_count' => 2,
                ],
            ]);

        $campaignId = $response->json('data.id');
        $this->assertDatabaseHas('managerial_campaigns', [
            'id' => $campaignId,
            'created_by' => $this->rsm->id,
            'status' => 'approved',
            'approved_by' => $this->rsm->id,
            'duration_months' => 3,
        ]);
    }

    public function test_dm_can_view_rsm_regional_campaign_in_index(): void
    {
        $products = Product::take(1)->pluck('id')->toArray();

        $campaign = ManagerialCampaign::create([
            'title' => 'Program Regional RSM Aktif',
            'district_name' => 'Regional',
            'duration_months' => 2,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(1)->endOfMonth()->toDateString(),
            'status' => 'approved',
            'created_by' => $this->rsm->id,
            'approved_by' => $this->rsm->id,
        ]);
        $campaign->products()->sync($products);

        $response = $this->actingAs($this->dm)->getJson('/api/managerial-campaigns');

        $response->assertStatus(200);
        $campaignIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($campaignId = $campaign->id, $campaignIds);
    }

    public function test_mr_only_sees_active_campaigns_in_district_or_from_its_rsm(): void
    {
        $dm = $this->mr->manager;
        $dm->manager_id = $this->rsm->id;
        $dm->save();

        $campaigns = collect([
            ['title' => 'Kampanye Distrik', 'district_name' => $this->mr->district, 'created_by' => $this->dm->id],
            ['title' => 'Arahan Regional Atasan', 'district_name' => 'Regional', 'created_by' => $this->rsm->id],
            ['title' => 'Arahan Regional Lain', 'district_name' => 'Regional', 'created_by' => $this->dm2->id],
            ['title' => 'Kampanye Distrik Lain', 'district_name' => 'Surabaya', 'created_by' => $this->dm2->id],
        ])->map(function (array $data) {
            return ManagerialCampaign::create($data + [
                'duration_months' => 1,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'status' => 'approved',
                'approved_by' => $this->rsm->id,
            ]);
        });

        $response = $this->actingAs($this->mr)->getJson('/api/managerial-campaigns');

        $response->assertOk();
        $visibleIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($campaigns[0]->id, $visibleIds);
        $this->assertContains($campaigns[1]->id, $visibleIds);
        $this->assertNotContains($campaigns[2]->id, $visibleIds);
        $this->assertNotContains($campaigns[3]->id, $visibleIds);
    }

    public function test_mr_without_district_receives_no_campaign_data(): void
    {
        $campaign = ManagerialCampaign::create([
            'title' => 'Kampanye Distrik Terbatas',
            'district_name' => $this->dm->district,
            'duration_months' => 1,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'approved',
            'created_by' => $this->dm->id,
        ]);
        $campaign->products()->sync(Product::take(1)->pluck('id'));

        $mrWithoutDistrict = User::factory()->create([
            'role' => 'mr',
            'district' => null,
            'manager_id' => $this->dm->id,
        ]);

        $response = $this->actingAs($mrWithoutDistrict)->getJson('/api/managerial-campaigns');

        $response->assertOk()->assertJsonPath('data', []);
    }

    public function test_user_cannot_view_campaign_detail_outside_scope(): void
    {
        $products = Product::take(1)->pluck('id')->toArray();

        // Kampanye milik DM di Jakarta Selatan
        $campaign = ManagerialCampaign::create([
            'title' => 'Program Khusus Jaksel',
            'district_name' => 'Jakarta Selatan',
            'duration_months' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'pending_approval',
            'created_by' => $this->dm->id,
        ]);
        $campaign->products()->sync($products);

        // User MR dari distrik lain atau role lain yang tidak terkait
        $otherMr = User::where('role', 'mr')->where('district', '!=', 'Jakarta Selatan')->first();
        if (! $otherMr) {
            $otherMr = User::factory()->create([
                'name' => 'MR Surabaya',
                'email' => 'mr_sby_test@mersi.com',
                'role' => 'mr',
                'district' => 'Surabaya',
                'manager_id' => $this->dm2->id,
            ]);
        }

        // MR luar distrik tidak boleh melihat detail
        $unauthorizedRes = $this->actingAs($otherMr)->getJson("/api/managerial-campaigns/{$campaign->id}");
        $unauthorizedRes->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'FORBIDDEN',
            ]);

        // Pemilik kampanye (DM) berhak melihat
        $authorizedRes = $this->actingAs($this->dm)->getJson("/api/managerial-campaigns/{$campaign->id}");
        $authorizedRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'campaign_id' => $campaign->id,
                ],
            ]);
    }

    public function test_mr_cannot_request_doctor_recommendations_outside_assigned_district(): void
    {
        // MR Jakarta Selatan mencoba meminta rekomendasi Surabaya
        $response = $this->actingAs($this->mr)->getJson('/api/managerial-campaigns/recommendations?district=Surabaya');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'CROSS_DISTRICT_FORBIDDEN',
            ]);

        // Permintaan tanpa query atau sesuai distrik MR berhasil
        $validResponse = $this->actingAs($this->mr)->getJson('/api/managerial-campaigns/recommendations');
        $validResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_dm_cannot_see_regional_campaign_from_unrelated_rsm(): void
    {
        $products = Product::take(1)->pluck('id')->toArray();

        // Buat RSM lain yang bukan atasan langsung dari $this->dm
        $otherRsm = User::factory()->create([
            'name' => 'RSM Jawa Barat',
            'email' => 'rsm_jabar_test@mersi.com',
            'role' => 'rsm',
            'district' => 'Regional Jabar',
        ]);

        $unrelatedCampaign = ManagerialCampaign::create([
            'title' => 'Arahan Regional Jabar',
            'district_name' => 'Regional',
            'duration_months' => 2,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(1)->endOfMonth()->toDateString(),
            'status' => 'approved',
            'created_by' => $otherRsm->id,
            'approved_by' => $otherRsm->id,
        ]);
        $unrelatedCampaign->products()->sync($products);

        $response = $this->actingAs($this->dm)->getJson('/api/managerial-campaigns');
        $response->assertStatus(200);

        $campaignIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($unrelatedCampaign->id, $campaignIds);
    }

    public function test_product_priority_is_scoped_to_mr_district_and_hierarchy(): void
    {
        $districtProduct = Product::create([
            'name' => 'Priority District Scope Test',
            'category' => 'Scope Test',
        ]);
        $hierarchyRegionalProduct = Product::create([
            'name' => 'Priority Hierarchy Regional Scope Test',
            'category' => 'Scope Test',
        ]);
        $unrelatedRegionalProduct = Product::create([
            'name' => 'Priority Unrelated Regional Scope Test',
            'category' => 'Scope Test',
        ]);

        $districtCampaign = ManagerialCampaign::create([
            'title' => 'Kampanye Distrik Produk',
            'district_name' => $this->mr->district,
            'duration_months' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonth()->endOfMonth()->toDateString(),
            'status' => 'approved',
            'created_by' => $this->dm->id,
            'approved_by' => $this->rsm->id,
        ]);
        $districtCampaign->products()->sync([$districtProduct->id]);

        $hierarchyRegionalCampaign = ManagerialCampaign::create([
            'title' => 'Arahan Regional Hierarki Produk',
            'district_name' => 'Regional',
            'duration_months' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonth()->endOfMonth()->toDateString(),
            'status' => 'approved',
            'created_by' => $this->rsm->id,
            'approved_by' => $this->mm?->id ?? $this->rsm->id,
        ]);
        $hierarchyRegionalCampaign->products()->sync([$hierarchyRegionalProduct->id]);

        $otherRsm = User::factory()->create([
            'name' => 'RSM Tidak Terkait Produk',
            'email' => 'rsm_product_scope_test@mersi.com',
            'role' => 'rsm',
            'district' => null,
        ]);
        $unrelatedRegionalCampaign = ManagerialCampaign::create([
            'title' => 'Arahan Regional Lain Produk',
            'district_name' => 'Regional',
            'duration_months' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonth()->endOfMonth()->toDateString(),
            'status' => 'approved',
            'created_by' => $otherRsm->id,
            'approved_by' => $otherRsm->id,
        ]);
        $unrelatedRegionalCampaign->products()->sync([$unrelatedRegionalProduct->id]);

        $response = $this->actingAs($this->mr)->getJson('/api/products');

        $response->assertOk();
        $products = collect($response->json('data'))->keyBy('id');

        $this->assertTrue($products[$districtProduct->id]['is_priority']);
        $this->assertTrue($products[$hierarchyRegionalProduct->id]['is_priority']);
        $this->assertFalse($products[$unrelatedRegionalProduct->id]['is_priority']);
    }
}
