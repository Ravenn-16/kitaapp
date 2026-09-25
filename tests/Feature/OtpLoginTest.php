<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_failure_returns_a_clear_error_without_leaving_a_pending_login(): void
    {
        $user = User::factory()->create(['role' => 'manager', 'password' => 'secret123']);
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')->once()
            ->andThrow(new \Symfony\Component\Mailer\Exception\TransportException('SMTP unavailable'));

        $this->postJson('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'Unable to send your sign-in code. Please contact your administrator to check email delivery, then try again.')
            ->assertSessionMissing('pending_login_email');
        $this->assertDatabaseCount('login_otps', 0);
        $this->assertGuest();
    }

    public function test_email_password_login_preserves_all_role_dashboards(): void
    {
        foreach (['cashier' => 'pos'] as $role => $dashboard) {
            $user = User::factory()->create(['role' => $role, 'email' => $role.'@example.com', 'password' => 'secret123']);
            $this->postJson('/login', ['email' => strtoupper($user->email), 'password' => 'secret123', 'role' => 'superadmin'])->assertOk()->assertJsonPath('role', $role)->assertJsonPath('dashboard', $dashboard);
            $this->assertAuthenticatedAs($user);
            $this->postJson('/logout')->assertOk();
            $this->assertGuest();
        }
    }

    public function test_invalid_email_password_and_inactive_users_are_rejected(): void
    {
        User::factory()->create(['email' => 'cashier@example.com', 'password' => 'secret123', 'status' => 'Inactive']);
        foreach ([['bad', 'secret123'], ['missing@example.com', 'secret123'], ['cashier@example.com', 'wrong'], ['cashier@example.com', 'secret123']] as [$email,$password]) {
            $this->postJson('/login', compact('email', 'password'))->assertUnprocessable();
            $this->assertGuest();
        }
    }

    public function test_manager_admin_and_super_admin_login_requires_otp_after_email_password(): void
    {
        foreach (['manager' => 'mgrDashboard', 'admin' => 'admDashboard', 'superadmin' => 'saDashboard'] as $role => $dashboard) {
            $user = User::factory()->create(['role' => $role, 'email' => $role.'@example.com', 'password' => 'secret123']);

            $login = $this->postJson('/login', ['email' => $user->email, 'password' => 'secret123'])
                ->assertOk()
                ->assertJsonPath('otp_required', true)
                ->assertJsonPath('email', $user->email);

            $this->assertGuest();

            $otpRecord = \App\Models\LoginOtp::query()->where('user_id', $user->id)->latest('id')->first();
            $this->assertNotNull($otpRecord);
            $otp = $login->json('test_otp');
            $this->assertSame(6, strlen($otp));
            $this->assertTrue(password_verify($otp, $otpRecord->otp_hash));

            $this->postJson('/otp/verify', ['email' => $user->email, 'otp' => $otp])
                ->assertOk()
                ->assertJsonPath('role', $role === 'superadmin' ? 'superadmin' : $role)
                ->assertJsonPath('dashboard', $dashboard);

            $this->assertAuthenticatedAs($user);
            $this->postJson('/logout')->assertOk();
            $this->assertGuest();
        }
    }

    public function test_otp_endpoints_cannot_bypass_password_and_guest_api_is_protected(): void
    {
        $this->postJson('/otp/request', ['email' => 'cashier@example.com'])->assertNotFound();
        $this->postJson('/otp/verify', ['email' => 'cashier@example.com', 'otp' => '123456'])->assertNotFound();
        $this->getJson('/api/kita-data')->assertUnauthorized();
        $this->postJson('/api/products', [])->assertUnauthorized();
    }

    public function test_legacy_plaintext_passwords_are_accepted_and_rehashed(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy@example.com',
            'password' => 'LegacyPass123',
            'role' => 'manager',
            'status' => 'Active',
        ]);

        $login = $this->postJson('/login', ['email' => $user->email, 'password' => 'LegacyPass123'])
            ->assertOk()
            ->assertJsonPath('otp_required', true);

        $this->postJson('/otp/verify', ['email' => $user->email, 'otp' => $login->json('test_otp')])
            ->assertOk()
            ->assertJsonPath('role', 'manager')
            ->assertJsonPath('dashboard', 'mgrDashboard');

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotSame('LegacyPass123', $user->password);
        $this->assertTrue(password_verify('LegacyPass123', $user->password));
    }

    public function test_inactive_existing_session_is_rejected(): void
    {
        $user = User::factory()->create(['status' => 'Inactive']);
        $this->actingAs($user)->getJson('/api/kita-data')->assertUnauthorized();
    }
}
