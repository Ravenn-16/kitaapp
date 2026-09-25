<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', fn (Blueprint $table) => $table->string('cashier', 120)->nullable()->change());
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('user', 120)->nullable()->change();
            $table->string('action', 255)->nullable()->change();
        });
        foreach (['transaction_approvals', 'discount_approvals'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('requested_by_role', 30)->nullable());
        }
    }

    public function down(): void
    {
        // Retain actor identity and text capacity when rolling back, preserving audit records.
    }
};
