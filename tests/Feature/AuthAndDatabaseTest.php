<?php

namespace Tests\Feature;

use App\Models\MasterCustomer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthAndDatabaseTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('PT Mersifarma');
    }

    public function test_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'mr@mersi.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/call-lists');
        $this->assertAuthenticated();
    }

    public function test_login_with_username_succeeds(): void
    {
        $response = $this->post('/login', [
            'username' => 'mr',
            'password' => 'password',
        ]);

        $response->assertRedirect('/call-lists');
        $this->assertAuthenticated();
    }

    public function test_api_login_returns_json_envelope(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'mr@mersi.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'user' => [
                        'email' => 'mr@mersi.com',
                        'role' => 'mr',
                    ],
                ],
            ]);
    }

    public function test_login_with_name_prefix_is_rejected(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'Rudi',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'INVALID_CREDENTIALS',
            ]);

        $this->assertGuest();
    }

    public function test_login_rate_limit_is_shared_between_username_and_email_alias(): void
    {
        $user = User::where('email', 'mr@mersi.com')->firstOrFail();
        RateLimiter::clear('user:'.$user->id.'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'username' => 'mr',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'mr@mersi.com',
            'password' => 'wrong-password',
        ])->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error_code' => 'TOO_MANY_ATTEMPTS',
            ]);
    }

    public function test_api_me_returns_authenticated_user(): void
    {
        $user = User::where('email', 'mr@mersi.com')->first();

        $response = $this->actingAs($user)->getJson('/api/me');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'mr@mersi.com',
                    'role' => 'mr',
                ],
            ]);
    }

    public function test_api_me_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'UNAUTHENTICATED',
            ]);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'mr@mersi.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    public function test_logout_clears_session(): void
    {
        $user = User::where('email', 'mr@mersi.com')->first();

        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_database_hierarchy_and_seeds_integrity(): void
    {
        $this->assertEquals(4, Role::count());

        $mr = User::where('email', 'mr@mersi.com')->first();
        $dm = User::where('email', 'dm@mersi.com')->first();
        $rsm = User::where('email', 'rsm@mersi.com')->first();
        $mm = User::where('email', 'mm@mersi.com')->first();

        $this->assertNotNull($mr);
        $this->assertNotNull($dm);
        $this->assertNotNull($rsm);
        $this->assertNotNull($mm);

        // Verify hierarchy: MR -> DM -> RSM -> MM
        $this->assertEquals($dm->id, $mr->manager_id);
        $this->assertEquals($rsm->id, $dm->manager_id);
        $this->assertEquals($mm->id, $rsm->manager_id);
        $this->assertNull($mm->manager_id);

        // Verify doctors and products
        $this->assertGreaterThanOrEqual(6, MasterCustomer::count());
        $this->assertGreaterThanOrEqual(5, Product::count());
    }

    public function test_api_csrf_token_endpoint_returns_token(): void
    {
        $response = $this->getJson('/api/csrf-token');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'csrf_token',
                ],
            ]);
    }
}
