<?php

namespace Tests\Feature;

use App\Models\CallList;
use App\Models\CallPlan;
use App\Models\MasterCustomer;
use App\Models\Product;
use App\Models\User;
use App\Models\VisitSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VisitSessionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $mr;

    protected User $otherMr;

    protected User $dm;

    protected MasterCustomer $doctor;

    protected Product $product;

    protected CallPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mr = User::where('email', 'mr@mersi.com')->firstOrFail();
        $this->otherMr = User::where('email', 'mr2@mersi.com')->firstOrFail();
        $this->dm = User::where('email', 'dm@mersi.com')->firstOrFail();

        $this->doctor = MasterCustomer::firstOrFail();
        $this->product = Product::firstOrFail();

        $callList = CallList::firstOrCreate(
            [
                'owner_id' => $this->mr->id,
                'period_month' => Carbon::now()->startOfMonth()->toDateString(),
            ],
            [
                'status' => 'approved',
                'assigned_approver_id' => $this->dm->id,
                'approved_at' => Carbon::now(),
            ]
        );
        $callList->doctors()->syncWithoutDetaching([$this->doctor->id]);

        $this->plan = CallPlan::create([
            'call_list_id' => $callList->id,
            'mr_id' => $this->mr->id,
            'doctor_id' => $this->doctor->id,
            'plan_date' => Carbon::now()->toDateString(),
            'planned_time' => '10:00:00',
            'status' => 'scheduled',
        ]);
    }

    public function test_mr_can_start_visit_session_from_scheduled_plan(): void
    {
        $response = $this->actingAs($this->mr)
            ->postJson('/api/visit-sessions/start', [
                'plan_id' => $this->plan->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'plan_id' => $this->plan->id,
                    'doctor_id' => $this->doctor->id,
                    'status' => 'in_progress',
                ],
            ]);

        $this->assertDatabaseHas('visit_sessions', [
            'mr_id' => $this->mr->id,
            'plan_id' => $this->plan->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_starting_same_plan_session_again_returns_idempotent_resume(): void
    {
        $session = VisitSession::create([
            'mr_id' => $this->mr->id,
            'plan_id' => $this->plan->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => Carbon::now()->toDateString(),
            'check_in_at' => Carbon::now(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->mr)
            ->postJson('/api/visit-sessions/start', [
                'plan_id' => $this->plan->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $session->id,
                    'status' => 'in_progress',
                ],
            ]);
    }

    public function test_cannot_start_concurrent_session_when_another_session_is_in_progress(): void
    {
        VisitSession::create([
            'mr_id' => $this->mr->id,
            'plan_id' => $this->plan->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => Carbon::now()->toDateString(),
            'check_in_at' => Carbon::now(),
            'status' => 'in_progress',
        ]);

        $doctor2 = MasterCustomer::where('id', '!=', $this->doctor->id)->firstOrFail();

        $response = $this->actingAs($this->mr)
            ->postJson('/api/visit-sessions/start', [
                'doctor_id' => $doctor2->id,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_mr_can_checkout_active_session(): void
    {
        $session = VisitSession::create([
            'mr_id' => $this->mr->id,
            'plan_id' => $this->plan->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => Carbon::now()->toDateString(),
            'check_in_at' => Carbon::now()->subMinutes(20),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->mr)
            ->postJson("/api/visit-sessions/{$session->id}/checkout");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $session->id,
                    'status' => 'in_progress',
                ],
            ]);

        $session->refresh();
        $this->assertNotNull($session->check_out_at);
    }

    public function test_mr_can_cancel_session_with_reason_and_plan_remains_scheduled(): void
    {
        $session = VisitSession::create([
            'mr_id' => $this->mr->id,
            'plan_id' => $this->plan->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => Carbon::now()->toDateString(),
            'check_in_at' => Carbon::now()->subMinutes(10),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->mr)
            ->postJson("/api/visit-sessions/{$session->id}/cancel", [
                'reason' => 'Dokter ada operasi darurat sehingga kunjungan dibatalkan',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'cancelled',
                    'cancellation_reason' => 'Dokter ada operasi darurat sehingga kunjungan dibatalkan',
                ],
            ]);

        // Plan tetap scheduled
        $this->plan->refresh();
        $this->assertEquals('scheduled', $this->plan->status);
    }

    public function test_mr_can_complete_session_backed_actual(): void
    {
        $session = VisitSession::create([
            'mr_id' => $this->mr->id,
            'plan_id' => $this->plan->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => Carbon::now()->toDateString(),
            'check_in_at' => Carbon::now()->subMinutes(30),
            'check_out_at' => Carbon::now()->subMinutes(5),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->mr)
            ->postJson('/api/call-actuals', [
                'plan_id' => $this->plan->id,
                'visit_session_id' => $session->id,
                'doctor_id' => $this->doctor->id,
                'visit_date' => Carbon::now()->toDateString(),
                'check_in' => '09:00',
                'check_out' => '09:30',
                'photo_url' => 'https://storage.mersi.com/photos/session1.jpg',
                'signature_url' => 'https://storage.mersi.com/signatures/session1.png',
                'products' => [$this->product->id],
            ]);

        $response->assertStatus(201);

        // Session status must become completed
        $session->refresh();
        $this->assertEquals('completed', $session->status);
        $this->assertNotNull($session->completed_at);

        // Plan status must become completed
        $this->plan->refresh();
        $this->assertEquals('completed', $this->plan->status);
    }
}
