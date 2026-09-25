<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('cashierId')->nullable()->index();
            $table->string('cashierRole', 40)->nullable();
            $table->string('cashierEmail')->nullable()->index();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('discountAmount', 12, 2)->default(0);
            $table->decimal('refundedAmount', 12, 2)->default(0);
            $table->string('discountType', 20)->default('none');
            $table->string('paymentReference', 80)->nullable()->unique();
            $table->text('checkoutUrl')->nullable();
        });
        Schema::create('transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_uuid', 30)->index();
            $table->unsignedBigInteger('productId');
            $table->string('name', 160);
            $table->string('stockId', 100)->nullable();
            $table->string('unit', 40)->nullable();
            $table->unsignedInteger('qty');
            $table->decimal('unitPrice', 12, 2);
            $table->decimal('discountAmount', 12, 2)->default(0);
            $table->decimal('lineTotal', 12, 2);
            $table->unsignedInteger('refundedQty')->default(0);
            $table->decimal('refundedAmount', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['transaction_uuid', 'productId']);
        });
        Schema::table('transaction_approvals', function (Blueprint $table) {
            $table->dropUnique(['transaction_uuid']);
            $table->index('transaction_uuid');
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('restock')->default(true);
            $table->string('provider_refund_id', 80)->nullable()->unique();
            $table->string('request_key', 80)->nullable()->unique();
        });
        Schema::create('transaction_return_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_id')->index();
            $table->unsignedBigInteger('transaction_line_id')->index();
            $table->unsignedInteger('qty');
            $table->decimal('amount', 12, 2);
            $table->boolean('restocked');
            $table->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('productId')->index();
            $table->integer('quantityChange');
            $table->unsignedInteger('quantityBefore');
            $table->unsignedInteger('quantityAfter');
            $table->string('referenceType', 40);
            $table->string('referenceId', 80)->index();
            $table->unsignedBigInteger('userId')->nullable();
            $table->string('userRole', 40)->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('transaction_return_lines');
        Schema::table('transaction_approvals', function (Blueprint $table) {
            $table->dropUnique(['provider_refund_id']);
            $table->dropUnique(['request_key']);
            $table->dropColumn(['amount', 'restock', 'provider_refund_id', 'request_key']);
        });
        // Partial refunds may exist, so do not restore the old one-approval constraint.
        Schema::dropIfExists('transaction_lines');
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['cashierId']);
            $table->dropIndex(['cashierEmail']);
            $table->dropUnique(['paymentReference']);
            $table->dropColumn(['cashierId', 'cashierRole', 'cashierEmail', 'subtotal', 'discountAmount', 'refundedAmount', 'discountType', 'paymentReference', 'checkoutUrl']);
        });
    }
};
