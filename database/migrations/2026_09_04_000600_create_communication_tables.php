<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('provider');
            $table->string('external_account_id');
            $table->string('credential_ref');
            $table->string('status')->default('active');
            $table->timestampTz('last_synced_at')->nullable();
            $table->timestampsTz();
            $table->unique(['organization_id', 'provider', 'external_account_id']);
        });
        Schema::create('communication_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->string('external_thread_id')->nullable();
            $table->foreignUuid('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status')->default('open');
            $table->timestampTz('last_message_at')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'status', 'last_message_at']);
        });
        Schema::create('thread_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('thread_id')->constrained('communication_threads')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address');
            $table->string('display_name')->nullable();
            $table->string('role');
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('thread_id')->constrained('communication_threads')->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('communication_accounts')->restrictOnDelete();
            $table->string('direction');
            $table->string('external_message_id');
            $table->string('sender');
            $table->json('recipients_json');
            $table->string('subject')->nullable();
            $table->text('body_text')->nullable();
            $table->timestampTz('sent_received_at');
            $table->string('delivery_status')->default('received');
            $table->timestampsTz();
            $table->unique(['account_id', 'external_message_id']);
            $table->index(['organization_id', 'sent_received_at']);
        });
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('message_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('storage_uri');
            $table->string('checksum');
            $table->string('malware_status')->default('pending');
            $table->timestampsTz();
        });
        Schema::create('ai_message_classifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('message_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('model_version_id')->constrained()->restrictOnDelete();
            $table->decimal('importance_score', 6, 5);
            $table->boolean('is_important');
            $table->string('category');
            $table->json('rationale_json');
            $table->timestampTz('classified_at');
            $table->unique('message_id');
        });
        Schema::create('unanswered_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('business_days')->default(5);
            $table->foreignUuid('business_calendar_id')->constrained()->restrictOnDelete();
            $table->boolean('auto_create_task')->default(true);
            $table->boolean('notify_responsible')->default(true);
            $table->unsignedSmallInteger('escalation_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('pending_message_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('thread_id')->constrained('communication_threads')->cascadeOnDelete();
            $table->foreignUuid('triggering_message_id')->constrained('messages')->restrictOnDelete();
            $table->foreignUuid('responsible_employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignUuid('policy_id')->constrained('unanswered_policies')->restrictOnDelete();
            $table->timestampTz('due_at');
            $table->string('status')->default('open');
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
            $table->index(['organization_id', 'status', 'due_at']);
        });
        Schema::create('pending_case_tasks', function (Blueprint $table) {
            $table->foreignUuid('pending_case_id')->constrained('pending_message_cases')->cascadeOnDelete();
            $table->foreignUuid('task_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->primary(['pending_case_id', 'task_id']);
        });
        Schema::create('bot_flows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->json('definition_json');
            $table->boolean('is_published')->default(false);
            $table->timestampsTz();
            $table->unique(['organization_id', 'channel', 'name', 'version']);
        });
        Schema::create('bot_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('communication_accounts')->cascadeOnDelete();
            $table->foreignUuid('flow_id')->constrained('bot_flows')->restrictOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_chat_id');
            $table->string('state');
            $table->json('context_json')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();
        });
        Schema::create('bot_registration_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bot_session_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('customer_orders')->nullOnDelete();
            $table->foreignUuid('payment_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestampTz('created_at')->useCurrent();
        });
        Schema::create('sync_cursors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('communication_account_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type');
            $table->text('cursor');
            $table->timestampTz('updated_at')->useCurrent();
            $table->unique(['communication_account_id', 'resource_type']);
        });
        Schema::create('message_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('name');
            $table->text('subject_template')->nullable();
            $table->text('body_template');
            $table->json('variables_schema');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('channel');
            $table->json('payload_json');
            $table->string('status')->default('pending');
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        foreach (['notification_deliveries', 'message_templates', 'sync_cursors', 'bot_registration_results', 'bot_sessions', 'bot_flows', 'pending_case_tasks', 'pending_message_cases', 'unanswered_policies', 'ai_message_classifications', 'message_attachments', 'messages', 'thread_participants', 'communication_threads', 'communication_accounts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
