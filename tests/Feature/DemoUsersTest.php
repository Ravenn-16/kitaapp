<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo_users.password' => 'Test-only-password-123', 'demo_users.emails' => [
            'superadmin' => 'superadmin@example.com', 'admin' => 'admin@example.com',
            'manager' => 'manager@example.com', 'cashier' => 'cashier@example.com',
        ]]);
    }

    public function test_creates_four_active_roles_with_hashed_passwords_and_unique_ids(): void
    {
        $this->artisan('accounts:seed-demo')->assertSuccessful();
        $this->assertSame(4, User::count());
        $this->assertSame(4, User::pluck('id')->unique()->count());
        foreach (config('demo_users.emails') as $role => $email) {
            $user = User::findOrFail($email);
            $this->assertSame($role, $user->role);
            $this->assertTrue($user->isActive());
            $this->assertTrue(Hash::check('Test-only-password-123', $user->password));
        }
    }

    public function test_repeated_provisioning_does_not_reset_or_reactivate_accounts(): void
    {
        $this->artisan('accounts:seed-demo')->assertSuccessful();
        User::findOrFail('cashier@example.com')->update(['status' => 'Inactive']);
        $before = User::orderBy('email')->get()->toJson();
        $hash = User::findOrFail('cashier@example.com')->password;
        config(['demo_users.password' => 'Changed-test-password']);
        $this->artisan('accounts:seed-demo')->assertSuccessful();
        $this->assertSame($before, User::orderBy('email')->get()->toJson());
        $this->assertSame($hash, User::findOrFail('cashier@example.com')->password);
    }

    public function test_missing_credentials_or_duplicate_emails_create_nothing(): void
    {
        config(['demo_users.password' => null]);
        $this->artisan('accounts:seed-demo')->assertFailed();
        config(['demo_users.password' => 'Test-only-password-123', 'demo_users.emails.cashier' => 'admin@example.com']);
        $this->artisan('accounts:seed-demo')->assertFailed();
        $this->assertSame(0, User::count());
    }

    public function test_role_conflict_rolls_back_all_new_accounts(): void
    {
        User::factory()->create(['email' => 'cashier@example.com', 'role' => 'manager']);
        $this->artisan('accounts:seed-demo')->assertFailed();
        $this->assertSame(1, User::count());
        $this->assertSame('manager', User::findOrFail('cashier@example.com')->role);
    }
}
