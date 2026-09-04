<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('catalog_item_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('duration_min');
            $table->unsignedInteger('buffer_before_min')->default(0);
            $table->unsignedInteger('buffer_after_min')->default(0);
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('employee_services', function (Blueprint $table) {
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('custom_duration_min')->nullable();
            $table->decimal('custom_price', 14, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->primary(['employee_id', 'service_id']);
        });
        Schema::create('locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('address_json')->nullable();
            $table->string('timezone');
            $table->timestampsTz();
        });
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->string('status')->default('active');
            $table->timestampsTz();
        });
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('resource_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestampsTz();
        });
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('branch_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('status')->default('pending');
            $table->string('source')->default('staff');
            $table->text('cancel_reason')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'starts_at']);
            $table->index(['organization_id', 'employee_id', 'starts_at', 'ends_at']);
        });
        Schema::create('appointment_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('price_snapshot', 14, 2);
        });
        Schema::create('appointment_resources', function (Blueprint $table) {
            $table->foreignUuid('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('resource_id')->constrained()->restrictOnDelete();
            $table->timestampTz('reserved_from');
            $table->timestampTz('reserved_to');
            $table->primary(['appointment_id', 'resource_id']);
            $table->index(['resource_id', 'reserved_from', 'reserved_to']);
        });
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('preferred_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->json('time_preferences_json')->nullable();
            $table->string('status')->default('active');
            $table->timestampsTz();
        });
        Schema::create('service_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 14, 2);
            $table->unsignedInteger('validity_days')->nullable();
            $table->unsignedInteger('visits_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('package_services', function (Blueprint $table) {
            $table->foreignUuid('package_id')->constrained('service_packages')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('included_quantity')->default(1);
            $table->primary(['package_id', 'service_id']);
        });
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('package_id')->constrained('service_packages')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->integer('remaining_visits')->nullable();
            $table->string('status')->default('active');
            $table->timestampsTz();
        });
        Schema::create('gift_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('purchaser_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('owner_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('code_hash')->unique();
            $table->decimal('initial_amount', 14, 2);
            $table->decimal('balance', 14, 2);
            $table->date('expires_on')->nullable();
            $table->string('status')->default('active');
            $table->timestampsTz();
        });
        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('points_balance', 14, 2)->default(0);
            $table->string('tier')->nullable();
            $table->timestampsTz();
            $table->unique(['organization_id', 'customer_id']);
        });
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->decimal('delta', 14, 2);
            $table->string('reason');
            $table->string('source_type')->nullable();
            $table->uuid('source_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('external_account_id');
            $table->string('credential_ref');
            $table->string('sync_status')->default('pending');
            $table->timestampsTz();
            $table->unique(['organization_id', 'provider', 'external_account_id']);
        });
        Schema::create('calendar_event_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('calendar_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('external_event_id');
            $table->string('sync_version')->nullable();
            $table->timestampTz('last_synced_at')->nullable();
            $table->unique(['calendar_connection_id', 'external_event_id']);
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status')->default('pending');
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        foreach (['reviews', 'calendar_event_links', 'calendar_connections', 'loyalty_transactions', 'loyalty_accounts', 'gift_certificates', 'subscriptions', 'package_services', 'service_packages', 'waitlist_entries', 'appointment_resources', 'appointment_services', 'appointments', 'availability_rules', 'resources', 'locations', 'employee_services', 'services'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
