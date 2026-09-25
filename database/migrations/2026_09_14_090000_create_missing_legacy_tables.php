<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original installation imported these tables outside Laravel migrations.
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->integer('id');
                $table->string('name', 160)->nullable();
                $table->string('category', 100)->nullable();
                $table->string('vatClass', 30)->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->decimal('cost', 10, 2)->nullable();
                $table->integer('stock')->nullable();
                $table->integer('minStock')->nullable();
                $table->string('unit', 40)->nullable();
                $table->string('status', 40)->nullable();
                $table->string('batch', 40)->nullable();
                $table->string('lot', 40)->nullable();
                $table->date('expiry')->nullable();
                $table->string('barcode', 80)->nullable();
                $table->integer('supplierId')->nullable();
                $table->integer('parentId')->nullable();
                $table->string('variantLabel', 40)->nullable();
                $table->string('purchaseUnit', 40)->nullable();
                $table->string('stockUnit', 40)->nullable();
                $table->decimal('conversionFactor', 10, 2)->nullable();
                $table->string('barcodeStatus', 120)->nullable();
                $table->date('archivedAt')->nullable();
                $table->string('archivedBy', 120)->nullable();
                $table->primary(['id']);
            });
        }
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->string('name', 100);
                $table->string('status', 30)->nullable();
                $table->date('archivedAt')->nullable();
                $table->string('archivedBy', 120)->nullable();
                $table->primary(['name']);
            });
        }
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->integer('id');
                $table->string('name', 160)->nullable();
                $table->string('status', 40)->nullable();
                $table->string('contact', 120)->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('email', 160)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('category', 120)->nullable();
                $table->date('archivedAt')->nullable();
                $table->string('archivedBy', 120)->nullable();
                $table->longText('callbackLog')->nullable();
                $table->primary(['id']);
            });
        }
        if (! Schema::hasTable('supplier_products')) {
            Schema::create('supplier_products', function (Blueprint $table) {
                $table->integer('supplierId');
                $table->integer('productId');
                $table->decimal('costPrice', 10, 2)->nullable();
                $table->boolean('preferred')->nullable();
                $table->primary(['supplierId', 'productId']);
            });
        }
        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->string('uuid', 30);
                $table->string('status', 40)->nullable();
                $table->string('cashier', 80)->nullable();
                $table->date('date')->nullable();
                $table->date('refundDate')->nullable();
                $table->decimal('total', 10, 2)->nullable();
                $table->string('paymentMode', 40)->nullable();
                $table->decimal('tendered', 10, 2)->nullable();
                $table->decimal('paid', 10, 2)->nullable();
                $table->decimal('changeAmount', 10, 2)->nullable();
                $table->string('referenceNo', 80)->nullable();
                $table->primary(['uuid']);
            });
        }
        if (! Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->string('id', 30);
                $table->string('itemRequestId', 30)->nullable();
                $table->integer('supplierId')->nullable();
                $table->string('status', 60)->nullable();
                $table->longText('supplierCallback')->nullable();
                $table->decimal('orderedValue', 12, 2)->nullable();
                $table->decimal('deliveredValue', 12, 2)->nullable();
                $table->decimal('invoicedValue', 12, 2)->nullable();
                $table->decimal('paidValue', 12, 2)->nullable();
                $table->decimal('outstandingValue', 12, 2)->nullable();
                $table->decimal('cancelledValue', 12, 2)->nullable();
                $table->longText('receivingRecordIds')->nullable();
                $table->date('created')->nullable();
                $table->integer('confirmedQty')->nullable();
                $table->primary(['id']);
            });
        }
        if (! Schema::hasTable('purchase_order_lines')) {
            Schema::create('purchase_order_lines', function (Blueprint $table) {
                $table->increments('id');
                $table->string('poId', 30)->nullable();
                $table->integer('productId')->nullable();
                $table->string('name', 160)->nullable();
                $table->integer('orderedQty')->nullable();
                $table->decimal('unitCost', 10, 2)->nullable();
                $table->integer('deliveredQty')->nullable();
                $table->string('outstandingAction', 120)->nullable();
            });
        }
        if (! Schema::hasTable('item_requests')) {
            Schema::create('item_requests', function (Blueprint $table) {
                $table->string('id', 30);
                $table->string('category', 100)->nullable();
                $table->string('status', 60)->nullable();
                $table->date('dateRequested')->nullable();
                $table->string('requestedBy', 120)->nullable();
                $table->text('adminNote')->nullable();
                $table->text('disapprovalReason')->nullable();
                $table->string('poId', 30)->nullable();
                $table->primary(['id']);
            });
        }
        if (! Schema::hasTable('item_request_lines')) {
            Schema::create('item_request_lines', function (Blueprint $table) {
                $table->increments('id');
                $table->string('requestId', 30)->nullable();
                $table->integer('productId')->nullable();
                $table->string('name', 160)->nullable();
                $table->integer('qty')->nullable();
                $table->integer('supplierId')->nullable();
                $table->integer('confirmedQty')->nullable();
                $table->integer('editedQty')->nullable();
            });
        }
        if (! Schema::hasTable('adjustments')) {
            Schema::create('adjustments', function (Blueprint $table) {
                $table->string('id', 30);
                $table->integer('productId')->nullable();
                $table->integer('qtyChange')->nullable();
                $table->string('reason', 80)->nullable();
                $table->string('status', 80)->nullable();
                $table->boolean('photo')->nullable();
                $table->text('comment')->nullable();
                $table->text('remarks')->nullable();
                $table->date('date')->nullable();
                $table->primary(['id']);
            });
        }
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->dateTime('ts')->nullable();
                $table->string('user', 80)->nullable();
                $table->string('action', 80)->nullable();
                $table->string('record', 160)->nullable();
                $table->text('beforeValue')->nullable();
                $table->text('afterValue')->nullable();
            });
        }
        if (! Schema::hasTable('field_version_history')) {
            Schema::create('field_version_history', function (Blueprint $table) {
                $table->increments('id');
                $table->string('field', 80)->nullable();
                $table->string('record', 160)->nullable();
                $table->text('oldValue')->nullable();
                $table->text('newValue')->nullable();
                $table->string('user', 80)->nullable();
                $table->dateTime('ts')->nullable();
                $table->text('reason')->nullable();
            });
        }
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->integer('id');
                $table->string('type', 80)->nullable();
                $table->string('priority', 40)->nullable();
                $table->text('message')->nullable();
                $table->primary(['id']);
            });
        }
        if (! Schema::hasTable('sales_log')) {
            Schema::create('sales_log', function (Blueprint $table) {
                $table->increments('id');
                $table->date('date')->nullable();
                $table->integer('hour')->nullable();
                $table->integer('productId')->nullable();
                $table->integer('qty')->nullable();
                $table->decimal('amount', 10, 2)->nullable();
            });
        }
        if (! Schema::hasTable('receiving_records')) {
            Schema::create('receiving_records', function (Blueprint $table) {
                $table->string('id', 30);
                $table->string('poId', 30)->nullable();
                $table->date('date')->nullable();
                $table->string('deliveryStatus', 80)->nullable();
                $table->boolean('discrepancy')->nullable();
                $table->string('discrepancyType', 80)->nullable();
                $table->text('supplierContact')->nullable();
                $table->text('outcome')->nullable();
                $table->string('adminApproval', 120)->nullable();
                $table->text('barcodeAssignment')->nullable();
                $table->primary(['id']);
            });
        }
        if (! Schema::hasTable('receiving_record_lines')) {
            Schema::create('receiving_record_lines', function (Blueprint $table) {
                $table->increments('id');
                $table->string('receivingId', 30)->nullable();
                $table->integer('productId')->nullable();
                $table->integer('poQty')->nullable();
                $table->integer('deliveredQty')->nullable();
                $table->integer('invoiceQty')->nullable();
                $table->string('conditionText', 120)->nullable();
            });
        }
        if (! Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->integer('id');
                $table->string('name', 160)->nullable();
                $table->string('type', 60)->nullable();
                $table->string('occasionName', 120)->nullable();
                $table->decimal('discountPct', 5, 2)->nullable();
                $table->date('startDate')->nullable();
                $table->date('endDate')->nullable();
                $table->string('category', 100)->nullable();
                $table->primary(['id']);
            });
        }
        if (! Schema::hasTable('promotion_products')) {
            Schema::create('promotion_products', function (Blueprint $table) {
                $table->integer('promotionId');
                $table->integer('productId');
                $table->primary(['promotionId', 'productId']);
            });
        }
        if (! Schema::hasTable('batch_recall_affected')) {
            Schema::create('batch_recall_affected', function (Blueprint $table) {
                $table->increments('id');
                $table->string('batch', 40)->nullable();
                $table->string('lot', 40)->nullable();
                $table->string('product', 160)->nullable();
                $table->string('location', 160)->nullable();
                $table->integer('qty')->nullable();
                $table->text('note')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Keep imported business records on rollback; this baseline is additive only.
    }
};
