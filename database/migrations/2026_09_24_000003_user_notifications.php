<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->integer('recipientId')->nullable();
            $table->string('recipientRole', 30)->nullable();
            $table->integer('actorId')->nullable();
            $table->string('actorRole', 30)->nullable();
            $table->string('screen', 60)->nullable();
            $table->string('recordId', 160)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->index(['recipientId', 'recipientRole', 'id'], 'notifications_recipient_index');
        });
        DB::table('inventory_sequences')->insertOrIgnore(['name' => 'notifications', 'lastValue' => (int) DB::table('notifications')->max('id')]);
    }

    public function down(): void
    {
        // Retain delivery ownership and read history on rollback.
    }
};
