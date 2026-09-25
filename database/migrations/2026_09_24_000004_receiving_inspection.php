<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receiving_records', fn (Blueprint $table) => $table->json('inspection')->nullable());
    }

    public function down(): void
    {
        // Preserve historical inspection reports.
    }
};
