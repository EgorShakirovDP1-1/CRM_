<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->char('currency', 3)->default('EUR');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();
        });
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->foreignUuid('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 14, 2);
            $table->primary(['price_list_id', 'catalog_item_id']);
        });
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->char('currency', 3)->default('EUR');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestampsTz();
            $table->unique(['organization_id', 'number']);
        });
        Schema::create('customer_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->foreignUuid('catalog_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2);
        });
        Schema::create('payment_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->string('provider');
            $table->string('external_session_id');
            $table->string('checkout_url');
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3);
            $table->string('status')->default('pending');
            $table->timestampTz('expires_at');
            $table->timestampsTz();
            $table->unique(['provider', 'external_session_id']);
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained('customer_orders')->restrictOnDelete();
            $table->foreignUuid('payment_session_id')->constrained()->restrictOnDelete();
            $table->string('external_payment_id')->unique();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3);
            $table->string('method');
            $table->string('status')->default('pending');
            $table->timestampTz('paid_at')->nullable();
            $table->timestampsTz();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('customer_orders')->nullOnDelete();
            $table->foreignUuid('customer_id')->constrained()->restrictOnDelete();
            $table->string('number');
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->decimal('total_amount', 14, 2);
            $table->string('status')->default('draft');
            $table->foreignUuid('document_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['organization_id', 'number']);
        });
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->uuid('parent_id')->nullable();
            $table->timestampsTz();
        });
        Schema::table('financial_categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('financial_categories')
                ->nullOnDelete();
        });
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('financial_categories')->restrictOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('EUR');
            $table->date('occurred_on');
            $table->text('description')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'occurred_on']);
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('registration_no')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('address_json')->nullable();
            $table->string('status')->default('active');
            $table->timestampsTz();
        });
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestampsTz();
        });
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('reserved_quantity', 14, 3)->default(0);
            $table->decimal('reorder_level', 14, 3)->default(0);
            $table->timestampsTz();
            $table->primary(['warehouse_id', 'catalog_item_id']);
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('catalog_item_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->decimal('quantity_delta', 14, 3);
            $table->string('source_type')->nullable();
            $table->uuid('source_id')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();
            $table->index(['organization_id', 'warehouse_id', 'occurred_at']);
        });
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('number');
            $table->foreignUuid('ordered_by_employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('expected_on')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestampsTz();
            $table->unique(['organization_id', 'number']);
        });
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('catalog_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('received_quantity', 14, 3)->default(0);
            $table->decimal('unit_cost', 14, 2);
        });
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('received_by_employee_id')->constrained('employees')->restrictOnDelete();
            $table->timestampTz('received_at');
            $table->string('status')->default('draft');
            $table->timestampsTz();
        });
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('purchase_order_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('lot_no')->nullable();
            $table->date('expires_on')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['goods_receipt_items', 'goods_receipts', 'purchase_order_items', 'purchase_orders', 'stock_movements', 'stock_balances', 'warehouses', 'suppliers', 'financial_transactions', 'financial_categories', 'invoices', 'payments', 'payment_sessions', 'customer_order_items', 'customer_orders', 'price_list_items', 'price_lists'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
