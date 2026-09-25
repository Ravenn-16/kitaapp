<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stock ID is the existing products.id primary key; retain that key and its references.
        if (! Schema::hasColumn('products', 'unitPrice')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->decimal('unitPrice', 10, 2)->nullable();
            });
            // Legacy catalog cost represented unit cost. New registrations persist both values.
            DB::table('products')->update(['unitPrice' => DB::raw('cost')]);
        }
        if (! Schema::hasColumn('categories', 'classification')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->string('classification', 30)->nullable();
            });
        }
        if (! Schema::hasColumn('products', 'registrationQuantity')) {
            Schema::table('products', fn (Blueprint $table) => $table->unsignedInteger('registrationQuantity')->nullable());
        }
        if (! Schema::hasColumn('categories', 'normalized_name')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('normalized_name', 100)->virtualAs('LOWER(TRIM(name))');
                $table->unique('normalized_name');
            });
        }
        if (! Schema::hasTable('inventory_sequences')) {
            Schema::create('inventory_sequences', function (Blueprint $table): void {
                $table->string('name', 40)->primary();
                $table->unsignedBigInteger('lastValue')->default(0);
            });
            DB::table('inventory_sequences')->insert(['name' => 'products', 'lastValue' => (int) DB::table('products')->max('id')]);
        }
        if (! Schema::hasIndex('products', 'products_barcode_unique')) {
            if (DB::table('products')->whereNotNull('barcode')->select('barcode')->groupBy('barcode')->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException('Duplicate barcodes must be reviewed before adding the barcode unique index.');
            }
            Schema::table('products', function (Blueprint $table): void {
                $table->unique('barcode', 'products_barcode_unique');
            });
        }
        if (! Schema::hasColumn('purchase_order_lines', 'lineTotal')) {
            Schema::table('purchase_order_lines', function (Blueprint $table): void {
                $table->decimal('lineTotal', 12, 2)->default(0);
            });
            DB::table('purchase_order_lines')->update(['lineTotal' => DB::raw('ROUND(orderedQty * unitCost, 2)')]);
        }
        foreach (['item_requests' => 'requestedBy', 'adjustments' => 'requestedBy', 'purchase_orders' => 'createdBy'] as $name => $prefix) {
            if (! Schema::hasColumn($name, $prefix.'Id')) {
                Schema::table($name, function (Blueprint $table) use ($prefix): void {
                    $table->integer($prefix.'Id')->nullable();
                    $table->string($prefix.'Role', 30)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['item_requests' => 'requestedBy', 'adjustments' => 'requestedBy', 'purchase_orders' => 'createdBy'] as $name => $prefix) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn([$prefix.'Id', $prefix.'Role']));
        }
        Schema::table('purchase_order_lines', fn (Blueprint $table) => $table->dropColumn('lineTotal'));
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_barcode_unique');
            $table->dropColumn('unitPrice');
        });
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('classification'));
        Schema::dropIfExists('inventory_sequences');
    }
};
