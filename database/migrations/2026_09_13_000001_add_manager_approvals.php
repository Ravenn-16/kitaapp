<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('approval_pin')->nullable();
        });
        Schema::create('transaction_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_uuid', 30)->unique();
            $table->unsignedBigInteger('manager_id');
            $table->unsignedBigInteger('requested_by');
            $table->string('action', 20);
            $table->string('reason');
            $table->unsignedBigInteger('replacement_product_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_approvals');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('approval_pin');
        });
    }
};
