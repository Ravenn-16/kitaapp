<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->whereNotNull('email')->selectRaw('LOWER(TRIM(email)) AS address')->groupByRaw('LOWER(TRIM(email))')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate account emails must be resolved before enabling email login. No accounts were changed.');
        }

        foreach (['status', 'username', 'refundLimit', 'schedule', 'createdBy', 'created_at', 'updated_at', 'remember_token', 'email_verified_at'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    match ($column) {
                        'status' => $table->string('status', 20)->default('Active'),
                        'username' => $table->string('username', 80)->nullable(),
                        'refundLimit' => $table->decimal('refundLimit', 10, 2)->nullable(),
                        'schedule', 'createdBy' => $table->string($column, 120)->nullable(),
                        'remember_token' => $table->rememberToken(),
                        default => $table->timestamp($column)->nullable(),
                    };
                });
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('normalized_email')->virtualAs('LOWER(TRIM(email))');
            $table->unique('normalized_email', 'users_normalized_email_unique');
        });

        Schema::create('account_sequences', function (Blueprint $table) {
            $table->string('name', 30)->primary();
            $table->unsignedBigInteger('next_id');
        });
        DB::table('account_sequences')->insert(['name' => 'users', 'next_id' => ((int) DB::table('users')->max('id')) + 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('account_sequences');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_normalized_email_unique');
            $table->dropColumn('normalized_email');
        });
        // Keep account status and compatibility fields: rolling back must not lose account data.
    }
};
