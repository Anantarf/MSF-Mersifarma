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

    public function test_campaign_progress_calculates_reach_and_detailing(): void
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

        // Simulasikan 1 kunjungan aktual ke dokter pertama dengan produk fokus
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
}
