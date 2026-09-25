<?php

namespace Tests\Feature;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_credentials_have_exact_message_without_disclosing_status_for_wrong_password(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'Inactive', 'password' => 'secret123']);
        $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Email or password is incorrect.');
        $this->postJson('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', User::DEACTIVATED_MESSAGE);
        $this->assertGuest();
        $this->assertDatabaseCount('login_otps', 0);
    }

    public function test_deactivation_between_password_and_otp_blocks_login(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'password' => 'secret123']);
        $otp = $this->postJson('/login', ['email' => $user->email, 'password' => 'secret123'])->assertOk()->json('test_otp');
        $user->update(['status' => 'Inactive']);
        $this->postJson('/otp/verify', ['email' => $user->email, 'otp' => $otp])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', User::DEACTIVATED_MESSAGE);
        $this->assertGuest();
        $this->assertNull(LoginOtp::first()->used_at);
    }

    public function test_otp_request_requires_password_authenticated_pending_session(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->postJson('/otp/request', ['email' => $user->email])->assertForbidden();
        $this->assertDatabaseCount('login_otps', 0);
    }

    public function test_super_admin_can_change_status_but_cannot_delete_accounts_or_deactivate_self(): void
    {
        $super = User::factory()->create(['role' => 'superadmin']);
        $user = User::factory()->create(['role' => 'cashier', 'password' => 'secret123']);
        $url = '/api/accounts/cashier/'.$user->id;
        $this->actingAs($super)->patchJson($url, ['status' => 'Inactive'])->assertOk()->assertJsonPath('account.status', 'Inactive');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'Inactive']);
        $this->deleteJson($url)->assertStatus(405);
        $this->deleteJson('/api/accounts/superadmin/'.$super->id)->assertStatus(405);
        $this->patchJson('/api/accounts/superadmin/'.$super->id, ['status' => 'Inactive'])->assertUnprocessable();
        $this->patchJson($url, ['status' => 'invalid'])->assertUnprocessable();
        $this->patchJson($url, ['status' => 'Active'])->assertOk()->assertJsonPath('account.status', 'Active');
        $this->postJson('/logout')->assertOk();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'secret123'])->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_revoked_session_is_logged_out_for_api_and_page_requests(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        $this->actingAs($user);
        $user->update(['status' => 'Inactive']);
        $this->getJson('/api/kita-data')->assertUnauthorized()->assertJsonPath('message', User::DEACTIVATED_MESSAGE)->assertJsonPath('deactivated', true);
        $this->assertGuest();
        $this->actingAs($user)->get('/')->assertRedirect('/')->assertSessionHas('login_error', User::DEACTIVATED_MESSAGE);
        $this->assertGuest();
    }

    public function test_dashboard_counts_database_records_and_averages_seven_complete_days(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 24)->setTime(12, 0));
        $super = User::factory()->create(['role' => 'superadmin']);
        User::factory()->create(['status' => 'Inactive']);
        foreach ([now()->subDay(), now()->subDays(7), now(), now()->subDays(8)] as $date) {
            DB::table('audit_logs')->insert(['ts' => $date, 'user' => 'Recorded actor', 'action' => 'Updated account', 'record' => 'Account']);
        }
        $response = $this->actingAs($super)->getJson('/api/super-admin/dashboard')->assertOk()
            ->assertJsonPath('total_users', 2)->assertJsonPath('active_users', 1)->assertJsonPath('inactive_users', 1)
            ->assertJsonPath('average_daily_events', 0.29)->assertJsonCount(7, 'activity_days')->assertJsonCount(4, 'recent_activity');
        $this->assertGreaterThan(0, $response->json('database_response_ms'));
        $this->assertGreaterThan(0, $response->json('generation_ms'));
        $this->assertSame(2, array_sum(array_column($response->json('activity_days'), 'events')));
    }

    public function test_dashboard_is_restricted_and_empty_activity_is_real_zero(): void
    {
        $this->getJson('/api/super-admin/dashboard')->assertUnauthorized();
        foreach (['cashier', 'manager', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->getJson('/api/super-admin/dashboard')->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'superadmin']))->getJson('/api/super-admin/dashboard')
            ->assertOk()->assertJsonPath('average_daily_events', 0)->assertJsonCount(0, 'recent_activity');
    }

    public function test_accounts_show_retained_otp_login_time_without_exposing_otp_secrets(): void
    {
        $user = User::factory()->create(['role' => 'superadmin', 'password' => 'secret123']);
        $otp = $this->postJson('/login', ['email' => $user->email, 'password' => 'secret123'])->assertOk()->json('test_otp');
        $this->postJson('/otp/verify', ['email' => $user->email, 'otp' => $otp])->assertOk();
        $response = $this->getJson('/api/accounts')->assertOk();
        $this->assertNotNull($response->json('accounts.0.lastOtpLogin'));
        $this->assertArrayNotHasKey('password', $response->json('accounts.0'));
        $this->assertArrayNotHasKey('otp_hash', $response->json('accounts.0'));
    }

    public function test_dashboard_query_failure_returns_unavailable_not_fake_metrics(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        DB::shouldReceive('select')->once()->with('SELECT 1')->andThrow(new \RuntimeException('Probe failed'));
        $response = $this->getJson('/api/super-admin/dashboard')->assertStatus(503)
            ->assertJsonPath('message', 'Unable to retrieve system performance information.');
        $this->assertArrayNotHasKey('database_response_ms', $response->json());
    }
}
