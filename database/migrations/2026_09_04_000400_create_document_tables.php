<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->unsignedInteger('current_version')->default(1);
            $table->json('variables_schema');
            $table->string('status')->default('draft');
            $table->foreignUuid('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
        });
        Schema::create('template_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('source_file_uri');
            $table->string('checksum');
            $table->foreignUuid('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['template_id', 'version']);
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('document_templates')->nullOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('deal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('number');
            $table->string('status')->default('draft');
            $table->unsignedInteger('current_version')->default(1);
            $table->foreignUuid('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['organization_id', 'number']);
        });
        Schema::create('document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('rendered_file_uri');
            $table->json('source_data_json');
            $table->string('checksum');
            $table->foreignUuid('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['document_id', 'version']);
        });
        Schema::create('document_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->unique(['document_id', 'entity_type', 'entity_id']);
        });
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('approver_role_code')->nullable();
            $table->foreignUuid('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('rule_json')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unique(['workflow_id', 'position']);
        });
        Schema::create('approval_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('workflow_id')->constrained('approval_workflows')->restrictOnDelete();
            $table->unsignedInteger('workflow_version');
            $table->string('status')->default('pending');
            $table->timestampTz('started_at')->useCurrent();
            $table->timestampTz('completed_at')->nullable();
        });
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('approval_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('step_id')->constrained('approval_steps')->restrictOnDelete();
            $table->foreignUuid('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->text('comment')->nullable();
            $table->timestampTz('acted_at')->useCurrent();
        });
        Schema::create('signature_providers', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->char('country_code', 2);
            $table->string('adapter_key');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('signature_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('provider_id')->constrained('signature_providers')->restrictOnDelete();
            $table->string('external_request_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestampTz('expires_at')->nullable();
            $table->string('signed_file_uri')->nullable();
            $table->timestampsTz();
        });
        Schema::create('signature_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_request_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('identifier_ref')->nullable();
            $table->unsignedInteger('sign_order')->default(1);
            $table->string('status')->default('pending');
        });
        Schema::create('signature_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_request_id')->constrained()->cascadeOnDelete();
            $table->string('provider_event_id')->unique();
            $table->string('type');
            $table->string('payload_hash');
            $table->timestampTz('occurred_at');
        });
        Schema::create('file_objects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('storage_uri');
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum');
            $table->string('encryption_key_ref')->nullable();
            $table->date('retention_until')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        foreach (['file_objects', 'signature_events', 'signature_participants', 'signature_requests', 'signature_providers', 'approval_actions', 'approval_instances', 'approval_steps', 'approval_workflows', 'document_links', 'document_versions', 'documents', 'template_versions', 'document_templates'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
