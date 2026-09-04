<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_source_registry', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->char('country_code', 2)->nullable();
            $table->string('type');
            $table->text('lawful_use_notes');
            $table->string('adapter_key');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('retention_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedInteger('retention_days');
            $table->string('trigger_event');
            $table->string('action');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('legal_basis_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('processing_purpose');
            $table->string('legal_basis');
            $table->json('data_categories');
            $table->foreignUuid('retention_policy_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('approved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestampsTz();
        });
        Schema::create('privacy_policy_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->string('locale', 5);
            $table->string('content_uri');
            $table->timestampTz('effective_from');
            $table->string('checksum');
            $table->timestampsTz();
            $table->unique(['organization_id', 'version', 'locale']);
        });
        Schema::create('consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('policy_version_id')->constrained('privacy_policy_versions')->restrictOnDelete();
            $table->string('purpose');
            $table->json('scope_json');
            $table->string('status')->default('granted');
            $table->timestampTz('granted_at');
            $table->timestampTz('withdrawn_at')->nullable();
            $table->string('evidence_ref');
            $table->timestampsTz();
            $table->index(['organization_id', 'customer_id', 'purpose']);
        });
        Schema::create('external_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('data_source_id')->constrained('data_source_registry')->restrictOnDelete();
            $table->string('purpose');
            $table->foreignUuid('legal_basis_id')->constrained('legal_basis_records')->restrictOnDelete();
            $table->foreignUuid('consent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_hash');
            $table->string('result_ref');
            $table->string('status')->default('pending');
            $table->timestampTz('checked_at')->nullable();
            $table->timestampsTz();
        });
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('model_version_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->decimal('score', 8, 4);
            $table->string('risk_level');
            $table->json('explanation_json');
            $table->timestampTz('assessed_at');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'customer_id', 'type']);
        });
        Schema::create('risk_factors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('risk_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->json('value_json');
            $table->decimal('contribution', 8, 4);
            $table->foreignUuid('source_check_id')->nullable()->constrained('external_checks')->nullOnDelete();
            $table->text('description');
        });
        Schema::create('customer_forecasts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('model_version_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('horizon_days');
            $table->decimal('value', 14, 4);
            $table->decimal('confidence', 6, 5)->nullable();
            $table->json('explanation_json');
            $table->timestampTz('calculated_at');
            $table->timestampsTz();
        });
        Schema::create('human_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->foreignUuid('reviewer_user_id')->constrained('users')->restrictOnDelete();
            $table->string('decision');
            $table->text('reason');
            $table->timestampTz('reviewed_at');
            $table->timestampsTz();
        });
        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('received');
            $table->timestampTz('received_at');
            $table->timestampTz('due_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
        });
        Schema::create('data_erasure_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('request_id')->nullable()->constrained('data_subject_requests')->nullOnDelete();
            $table->foreignUuid('retention_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->string('mode');
            $table->string('status')->default('pending');
            $table->timestampTz('executed_at')->nullable();
            $table->json('result_json')->nullable();
            $table->timestampsTz();
        });
        Schema::create('cookie_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('anonymous_visitor_id')->nullable();
            $table->string('policy_version');
            $table->boolean('necessary')->default(true);
            $table->boolean('analytics')->default(false);
            $table->boolean('marketing')->default(false);
            $table->timestampTz('updated_at')->useCurrent();
        });
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('severity');
            $table->timestampTz('detected_at');
            $table->timestampTz('contained_at')->nullable();
            $table->timestampTz('authority_notified_at')->nullable();
            $table->timestampTz('subjects_notified_at')->nullable();
            $table->string('status')->default('open');
            $table->timestampsTz();
        });
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->uuid('aggregate_id');
            $table->json('payload_json');
            $table->timestampTz('occurred_at');
            $table->timestampTz('published_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->index(['published_at', 'occurred_at']);
        });
        Schema::create('webhook_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('external_event_id');
            $table->string('payload_hash');
            $table->string('status')->default('received');
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['provider', 'external_event_id']);
        });
    }

    public function down(): void
    {
        foreach (['webhook_receipts', 'outbox_messages', 'security_incidents', 'cookie_preferences', 'data_erasure_jobs', 'data_subject_requests', 'human_reviews', 'customer_forecasts', 'risk_factors', 'risk_assessments', 'external_checks', 'consents', 'privacy_policy_versions', 'legal_basis_records', 'retention_policies', 'data_source_registry'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
