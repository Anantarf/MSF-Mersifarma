<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CallListTest extends TestCase
{
    use DatabaseTransactions;

    protected User $mr;

    protected User $mr2;

    protected User $dm;

    protected User $dm2;

    protected User $rsm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mr = User::where('email', 'mr@mersi.com')->first();
        $this->mr2 = User::where('email', 'mr2@mersi.com')->first();
        $this->dm = User::where('email', 'dm@mersi.com')->first();
        $this->dm2 = User::where('email', 'dm2@mersi.com')->first();
        $this->rsm = User::where('email', 'rsm@mersi.com')->first();
    }

    public function test_mr_can_create_call_list_draft(): void
    {
        $period = '2027-01';

        $response = $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => $period,
            'doctor_ids' => [1, 2, 3],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Call List draft created',
                'data' => [
                    'period_month' => $period,
                    'status' => 'draft',
                    'doctor_ids' => [1, 2, 3],
                ],
            ]);

        $callListId = $response->json('data.id');
        $this->assertDatabaseHas('call_lists', [
            'id' => $callListId,
            'owner_id' => $this->mr->id,
            'status' => 'draft',
            'assigned_approver_id' => $this->dm->id,
        ]);

        $this->assertDatabaseHas('call_list_approval_logs', [
            'call_list_id' => $callListId,
            'actor_id' => $this->mr->id,
            'event' => 'created_draft',
            'to_status' => 'draft',
        ]);
    }

    public function test_cannot_create_duplicate_call_list_for_same_owner_and_period(): void
    {
        $period = '2027-02';

        $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => $period,
            'doctor_ids' => [1, 2],
        ])->assertStatus(201);

        // Percobaan kedua di bulan yang sama harus ditolak
        $response = $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => $period,
            'doctor_ids' => [3, 4],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period_month']);
    }

    public function test_owner_can_update_draft_and_others_are_forbidden(): void
    {
        $callList = $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => '2027-03',
            'doctor_ids' => [1, 2],
        ])->json('data');

        // MR 2 (bukan pemilik) mencoba mengubah -> 403 Forbidden
        $this->actingAs($this->mr2)->patchJson("/api/call-lists/{$callList['id']}", [
            'doctor_ids' => [3, 4],
        ])->assertStatus(403);

        // MR pemilik mengubah draft via PATCH -> 200 OK
        $response = $this->actingAs($this->mr)->patchJson("/api/call-lists/{$callList['id']}", [
            'doctor_ids' => [2, 3, 5],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'doctor_ids' => [2, 3, 5],
                ],
            ]);
    }

    public function test_owner_can_submit_draft_to_direct_manager(): void
    {
        $createRes = $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => '2027-04',
            'doctor_ids' => [1, 2],
        ]);
        $callListId = $createRes->json('data.id');

        $response = $this->actingAs($this->mr)->postJson("/api/call-lists/{$callListId}/submit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Call List submitted for approval',
                'data' => [
                    'id' => $callListId,
                    'status' => 'pending_approval',
                    'assigned_approver_id' => $this->dm->id,
                ],
            ]);

        $this->assertDatabaseHas('call_lists', [
            'id' => $callListId,
            'status' => 'pending_approval',
        ]);

        // Mengubah saat pending approval harus ditolak (409 Conflict)
        $this->actingAs($this->mr)->putJson("/api/call-lists/{$callListId}", [
            'doctor_ids' => [1],
        ])->assertStatus(409);
    }

    public function test_rejection_flow_returns_to_draft_and_requires_reason(): void
    {
        $createRes = $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => '2027-05',
            'doctor_ids' => [1, 2],
        ]);
        $callListId = $createRes->json('data.id');
        $this->actingAs($this->mr)->postJson("/api/call-lists/{$callListId}/submit");

        // Reject tanpa alasan harus gagal validasi (422)
        $this->actingAs($this->dm)->patchJson("/api/call-lists/{$callListId}/approve", [
            'status' => 'rejected',
            'reason' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        // Reject dengan alasan sah
        $response = $this->actingAs($this->dm)->patchJson("/api/call-lists/{$callListId}/approve", [
            'status' => 'rejected',
            'reason' => 'Target dokter anak kurang banyak',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'draft',
                    'decision' => 'rejected',
                    'reason' => 'Target dokter anak kurang banyak',
                ],
            ]);

        // Status kembali draft
        $this->assertDatabaseHas('call_lists', [
            'id' => $callListId,
            'status' => 'draft',
        ]);

        // Pemilik mengedit dan mengajukan ulang (resubmit)
        $this->actingAs($this->mr)->putJson("/api/call-lists/{$callListId}", [
            'doctor_ids' => [1, 2, 4],
        ])->assertStatus(200);

        $resubmitRes = $this->actingAs($this->mr)->postJson("/api/call-lists/{$callListId}/submit");
        $resubmitRes->assertStatus(200);

        $this->assertDatabaseHas('call_list_approval_logs', [
            'call_list_id' => $callListId,
            'event' => 'resubmitted',
        ]);
    }

    public function test_resubmit_preserves_original_assigned_approver(): void
    {
        $createRes = $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => '2027-08',
            'doctor_ids' => [1, 2],
        ]);
        $callListId = $createRes->json('data.id');

        $this->actingAs($this->mr)->postJson("/api/call-lists/{$callListId}/submit")
            ->assertStatus(200)
            ->assertJsonPath('data.assigned_approver_id', $this->dm->id);

        $this->actingAs($this->dm)->patchJson("/api/call-lists/{$callListId}/approve", [
            'status' => 'rejected',
            'reason' => 'Perlu revisi target',
        ])->assertStatus(200);

        $this->mr->update(['manager_id' => $this->dm2->id]);

        $this->actingAs($this->mr)->postJson("/api/call-lists/{$callListId}/submit")
            ->assertStatus(200)
            ->assertJsonPath('data.assigned_approver_id', $this->dm->id);

        $this->assertDatabaseHas('call_lists', [
            'id' => $callListId,
            'assigned_approver_id' => $this->dm->id,
            'status' => 'pending_approval',
        ]);
    }

    public function test_post_reject_endpoint_returns_list_to_draft(): void
    {
        $createRes = $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => '2027-09',
            'doctor_ids' => [1, 2],
        ]);
        $callListId = $createRes->json('data.id');
        $this->actingAs($this->mr)->postJson("/api/call-lists/{$callListId}/submit");

        $this->actingAs($this->dm)->postJson("/api/call-lists/{$callListId}/reject", [
            'reason' => 'Target belum sesuai prioritas distrik',
        ])->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'draft',
                    'decision' => 'rejected',
                    'reason' => 'Target belum sesuai prioritas distrik',
                ],
            ]);

        $this->assertDatabaseHas('call_lists', [
            'id' => $callListId,
            'status' => 'draft',
        ]);
    }
    /**
     * Skenario Uji Wajib PDF: PDF-05
     * RSM/MM (atau manager lain yang bukan direct manager tertugaskan) dilarang approve pengajuan MR.
     */
    public function test_pdf_05_unassigned_manager_or_higher_level_cannot_approve(): void
    {
        $createRes = $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => '2027-06',
            'doctor_ids' => [1, 2],
        ]);
        $callListId = $createRes->json('data.id');
        $this->actingAs($this->mr)->postJson("/api/call-lists/{$callListId}/submit");

        // 1. Dicoba oleh RSM Siti (atasan dari DM Joko, bukan direct manager Rudi MR) -> Wajib 403 Forbidden!
        $rsmResponse = $this->actingAs($this->rsm)->patchJson("/api/call-lists/{$callListId}/approve", [
            'status' => 'approved',
        ]);
        $rsmResponse->assertStatus(403);

        // 2. Dicoba oleh DM Hendra (DM 2 lain yang bukan atasan Rudi) -> Wajib 403 Forbidden!
        $dm2Response = $this->actingAs($this->dm2)->patchJson("/api/call-lists/{$callListId}/approve", [
            'status' => 'approved',
        ]);
        $dm2Response->assertStatus(403);

        // Pastikan status di database tetap pending_approval
        $this->assertDatabaseHas('call_lists', [
            'id' => $callListId,
            'status' => 'pending_approval',
        ]);

        // 3. Hanya direct manager yang sah (DM Joko) yang berhasil approve -> 200 OK
        $dmResponse = $this->actingAs($this->dm)->patchJson("/api/call-lists/{$callListId}/approve", [
            'status' => 'approved',
            'reason' => 'Target disetujui',
        ]);

        $dmResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'approved',
                    'decision' => 'approved',
                ],
            ]);

        $this->assertDatabaseHas('call_lists', [
            'id' => $callListId,
            'status' => 'approved',
        ]);
    }

    public function test_view_list_and_details_authorization(): void
    {
        $createRes = $this->actingAs($this->mr)->postJson('/api/call-lists', [
            'period_month' => '2027-07',
            'doctor_ids' => [1, 2],
        ]);
        $callListId = $createRes->json('data.id');

        // Owner bisa melihat daftar miliknya
        $indexRes = $this->actingAs($this->mr)->getJson('/api/call-lists');
        $indexRes->assertStatus(200);

        // Owner bisa melihat detail
        $detailRes = $this->actingAs($this->mr)->getJson("/api/call-lists/{$callListId}");
        $detailRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'owner_name',
                    'doctors',
                    'approval_history',
                ],
            ]);

        // Pengguna lain (MR 2) dilarang melihat detail Call List milik MR 1
        $this->actingAs($this->mr2)->getJson("/api/call-lists/{$callListId}")
            ->assertStatus(403);
    }

    public function test_manager_can_access_managerial_monitoring_summary(): void
    {
        // DM Joko mengakses ringkasan monitoring bawahannya
        $response = $this->actingAs($this->dm)->getJson('/api/call-lists/summary');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'period_month',
                    'total_subordinates',
                    'submitted_count',
                    'approved_count',
                    'pending_count',
                    'revision_count',
                    'total_target_doctors',
                    'subordinates_detail',
                ],
            ]);

        // DM Joko membawahi minimal 2 MR (Rudi dan Ahmad)
        $this->assertGreaterThanOrEqual(2, $response->json('data.total_subordinates'));
    }

    public function test_non_manager_cannot_access_managerial_monitoring_summary(): void
    {
        // MR Rudi dilarang mengakses ringkasan monitoring manajerial
        $response = $this->actingAs($this->mr)->getJson('/api/call-lists/summary');
        $response->assertStatus(403);
    }
}
