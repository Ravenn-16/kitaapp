<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $last = 0;
        foreach (['item_requests', 'purchase_orders'] as $name) {
            foreach (DB::table($name)->select('id')->cursor() as $record) {
                if (ctype_digit((string) $record->id)) {
                    $number = ltrim((string) $record->id, '0');
                    if (strlen($number) <= 18) {
                        $last = max($last, (int) $number);
                    }
                }
            }
        }
        DB::table('inventory_sequences')->insertOrIgnore(['name' => 'purchase_orders', 'lastValue' => $last]);
        Schema::table('item_requests', function (Blueprint $table) {
            $table->uuid('submissionKey')->nullable()->unique();
            $table->string('submissionHash', 64)->nullable();
        });
    }

    public function down(): void
    {
        // Preserve numbering and retry protection; never reuse a previously issued PO number.
    }
};
