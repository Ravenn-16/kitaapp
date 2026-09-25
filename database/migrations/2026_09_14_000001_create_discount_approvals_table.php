<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_uuid', 30)->unique();
            $table->unsignedBigInteger('manager_id');
            $table->unsignedBigInteger('requested_by');
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_approvals');
    }
};
