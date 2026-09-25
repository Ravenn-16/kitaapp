<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserEmailPrimaryKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_the_database_and_model_primary_key_while_roles_remain(): void
    {
        $primary = collect(Schema::getIndexes('users'))->firstWhere('primary', true);
        $this->assertSame(['email'], $primary['columns']);

        foreach (['cashier', 'manager', 'admin', 'superadmin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertSame($user->email, $user->getKey());
            $this->assertSame($role, User::findOrFail($user->email)->role);
            $this->assertNotNull($user->id);
        }
    }

    public function test_changing_email_preserves_identity_and_other_accounts_with_the_same_numeric_id(): void
    {
        $manager = User::factory()->create(['id' => 42, 'role' => 'manager']);
        $cashier = User::factory()->create(['id' => 42, 'role' => 'cashier']);
        $oldEmail = $manager->email;
        $manager->email = ' Updated@Example.com ';
        $manager->save();

        $this->assertNull(User::find($oldEmail));
        $this->assertSame(42, (int) $manager->fresh()->id);
        $this->assertSame('manager', User::findOrFail('updated@example.com')->role);
        $this->assertSame('cashier', $cashier->fresh()->role);
        $this->assertSame(2, User::count());
    }

    public function test_migration_preserves_existing_accounts_and_role_scoped_ids(): void
    {
        User::factory()->create(['id' => 7, 'role' => 'manager']);
        User::factory()->create(['id' => 7, 'role' => 'cashier']);
        $before = DB::table('users')->orderBy('email')->get()->toJson();
        $migration = require database_path('migrations/2026_09_25_000001_use_email_as_users_primary_key.php');
        $migration->down();
        $migration->up();
        $this->assertSame($before, DB::table('users')->orderBy('email')->get()->toJson());
    }
}
