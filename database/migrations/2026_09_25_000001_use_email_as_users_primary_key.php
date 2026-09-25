<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->whereNull('email')->orWhereRaw("TRIM(email) = ''")->exists()
            || DB::table('users')->selectRaw('LOWER(TRIM(email)) AS address')->groupByRaw('LOWER(TRIM(email))')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Every user needs a nonempty, unique email before changing the primary key. No accounts were changed.');
        }

        // Keep imported role-scoped IDs available to historical records and account URLs.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL, MODIFY email VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (email), ADD UNIQUE KEY users_role_id_unique (role, id)');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->autoIncrement(false)->change();
                $table->string('email')->nullable(false)->change();
                $table->dropPrimary();
                $table->primary('email');
                $table->unique(['role', 'id'], 'users_role_id_unique');
            });
        }

        // Laravel's database session driver stores the authentication identifier here.
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary();
            $table->primary(['role', 'id']);
            $table->dropUnique('users_role_id_unique');
        });
        // Retain email session identifiers and all existing account data.
    }
};
