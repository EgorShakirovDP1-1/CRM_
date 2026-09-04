<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('unit')->default('pcs');
            $table->decimal('tax_rate_pct', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['organization_id', 'sku']);
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('person');
            $table->string('display_name');
            $table->foreignUuid('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status')->default('active');
            $table->string('preferred_language', 5)->default('ru');
            $table->string('risk_level')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'display_name']);
        });
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name')->default('');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('status')->default('active');
            $table->timestampsTz();
            $table->index(['organization_id', 'email']);
            $table->index(['organization_id', 'phone']);
        });
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->primary(['customer_id', 'contact_id']);
        });
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('channel');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['organization_id', 'code']);
        });
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignUuid('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignUuid('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('new');
            $table->timestampsTz();
            $table->index(['organization_id', 'status']);
        });
        Schema::create('pipelines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('entity_type')->default('deal');
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();
        });
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('position');
            $table->decimal('probability_pct', 5, 2)->default(0);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->unique(['pipeline_id', 'position']);
        });
        Schema::create('deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('pipeline_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('stage_id')->constrained('pipeline_stages')->restrictOnDelete();
            $table->foreignUuid('owner_employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('title');
            $table->decimal('expected_amount', 14, 2)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->string('status')->default('open');
            $table->timestampsTz();
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'stage_id']);
        });
        Schema::create('deal_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('catalog_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
        });
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('assignee_employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('deal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('todo');
            $table->string('title');
            $table->timestampTz('due_at')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->string('source_type')->nullable();
            $table->uuid('source_id')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'assignee_employee_id', 'status']);
            $table->index(['organization_id', 'due_at']);
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('deal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('subject');
            $table->timestampTz('occurred_at');
            $table->json('details_json')->nullable();
            $table->timestampsTz();
        });
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->unique(['organization_id', 'name']);
        });
        Schema::create('tag_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tag_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->unique(['tag_id', 'entity_type', 'entity_id']);
        });
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('channel');
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->decimal('budget', 14, 2)->nullable();
            $table->string('status')->default('draft');
            $table->timestampsTz();
        });
        Schema::create('campaign_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestampTz('responded_at')->nullable();
        });
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('timezone');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('status')->default('draft');
            $table->timestampsTz();
        });
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_id')->constrained('work_schedules')->cascadeOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->json('breaks_json')->nullable();
            $table->string('status')->default('planned');
        });
        Schema::create('work_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('period_type');
            $table->date('period_start');
            $table->string('status')->default('draft');
            $table->timestampsTz();
        });
        Schema::create('work_plan_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('work_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->timestampTz('due_at')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('planned');
        });
    }

    public function down(): void
    {
        foreach (['work_plan_items', 'work_plans', 'work_shifts', 'work_schedules', 'campaign_members', 'campaigns', 'tag_links', 'tags', 'activities', 'tasks', 'deal_items', 'deals', 'pipeline_stages', 'pipelines', 'leads', 'lead_sources', 'customer_contacts', 'contacts', 'customers', 'catalog_items'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
