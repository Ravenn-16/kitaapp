<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_requests', function (Blueprint $table) {
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->integer('approvedById')->nullable();
            $table->string('approvedByRole', 30)->nullable();
            $table->string('approvedBy', 120)->nullable();
            $table->string('supplierName', 160)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('revision')->default(0);
        });
        foreach (['item_request_lines', 'purchase_order_lines'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('category', 100)->nullable();
                $table->string('unit', 40)->nullable();
            });
        }
        Schema::table('item_request_lines', fn (Blueprint $table) => $table->decimal('unitCost', 10, 2)->nullable());
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('supplierName', 160)->nullable();
            $table->unsignedInteger('receivingVersion')->default(0);
            $table->timestamp('received_at')->nullable();
        });
        Schema::table('receiving_records', function (Blueprint $table) {
            $table->uuid('idempotencyKey')->nullable()->unique();
            $table->string('deliveryReference', 100)->nullable();
            $table->string('payloadHash', 64)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->integer('receivedById')->nullable();
            $table->string('receivedByRole', 30)->nullable();
            $table->string('receivedBy', 120)->nullable();
            $table->string('supplierName', 160)->nullable();
            $table->unique(['poId', 'deliveryReference'], 'receiving_po_delivery_unique');
        });
        Schema::table('receiving_record_lines', function (Blueprint $table) {
            $table->string('name', 160)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('unit', 40)->nullable();
        });
        DB::table('inventory_sequences')->insertOrIgnore(['name' => 'suppliers', 'lastValue' => (int) DB::table('suppliers')->max('id')]);
    }

    public function down(): void
    {
        // Retain purchase audit history and idempotency records on application rollback.
    }
};
