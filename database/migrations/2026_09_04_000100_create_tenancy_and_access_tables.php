<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('timezone')->default('Europe/Riga');
            $table->string('data_region')->default('EU');
            $table->unsignedSmallInteger('unanswered_business_days')->default(5);
            $table->string('status')->default('active')->index();
            $table->timestampsTz();
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('address_json')->nullable();
            $table->string('timezone')->default('Europe/Riga');
            $table->string('status')->default('active');
            $table->timestampsTz();
            $table->index(['organization_id', 'status']);
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('manager_id')->nullable()->references('id')->on('employees')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->default('');
            $table->string('job_title')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('status')->default('active');
            $table->timestampsTz();
            $table->index(['organization_id', 'status']);
        });
        Schema::create('organization_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestampTz('joined_at')->useCurrent();
            $table->unique(['organization_id', 'user_id']);
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('code')->unique();
            $table->string('name_ru');
            $table->string('name_en');
            $table->unsignedSmallInteger('rank');
            $table->boolean('is_system')->default(true);
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('code')->unique();
            $table->string('module');
            $table->string('action');
            $table->string('description');
        });
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->boolean('allowed')->default(true);
            $table->primary(['role_id', 'permission_id']);
        });
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('assigned_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->timestampTz('revoked_at')->nullable();
            $table->index(['organization_user_id', 'revoked_at']);
        });
        Schema::create('business_calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->char('country_code', 2)->default('LV');
            $table->json('weekend_days')->default('[0,6]');
            $table->string('timezone')->default('Europe/Riga');
            $table->timestampsTz();
        });
        Schema::create('business_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_calendar_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('name');
            $table->boolean('is_working_day_override')->default(false);
            $table->unique(['business_calendar_id', 'date']);
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->uuid('entity_id')->nullable();
            $table->json('metadata_json')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['organization_id', 'entity_type', 'entity_id']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('business_holidays');
        Schema::dropIfExists('business_calendars');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('organization_users');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('organizations');
    }
};
