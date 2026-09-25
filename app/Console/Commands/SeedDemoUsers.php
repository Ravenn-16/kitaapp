<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class SeedDemoUsers extends Command
{
    protected $signature = 'accounts:seed-demo';

    protected $description = 'Create one account per role using private deployment environment values';

    public function handle(): int
    {
        $emails = array_map(fn ($email) => strtolower(trim((string) $email)), config('demo_users.emails'));
        $password = config('demo_users.password');
        $validator = Validator::make(['password' => $password, 'emails' => $emails], [
            'password' => ['required', 'string', 'min:12', 'max:72'],
            'emails.*' => ['required', 'email', 'max:255', 'distinct'],
        ]);
        if ($validator->fails()) {
            $this->error('Set DEMO_USERS_PASSWORD (12–72 characters) and four distinct, deliverable DEMO_*_EMAIL values.');

            return self::FAILURE;
        }

        try {
            $created = DB::transaction(function () use ($emails, $password) {
                // Share the same allocation lock as normal account creation.
                $counter = DB::table('account_sequences')->where('name', 'users')->lockForUpdate()->first();
                if (! $counter) {
                    throw new RuntimeException('Account sequence is missing. Complete the reviewed migrations first.');
                }

                $created = 0;
                foreach ($emails as $role => $email) {
                    $existing = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
                    if ($existing) {
                        $normalizedRole = $role === 'superadmin' ? 'super_admin' : $role;
                        if ($existing->normalizedRole() !== $normalizedRole) {
                            throw new RuntimeException('A configured demo email belongs to a different role. No accounts were created.');
                        }
                        // Never reset passwords, reactivate users, or change existing accounts.
                        continue;
                    }
                    User::create([
                        'name' => 'Demo '.($role === 'superadmin' ? 'Super Admin' : ucfirst($role)),
                        'email' => $email,
                        'password' => $password,
                        'role' => $role,
                        'status' => 'Active',
                        'refundLimit' => 300,
                        'createdBy' => 'Demo provisioning',
                    ]);
                    $created++;
                }

                return $created;
            }, 3);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Created {$created} demo accounts. Existing accounts were unchanged. Disable SEED_DEMO_USERS after provisioning.");

        return self::SUCCESS;
    }
}
