<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\PendingMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PendingMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_important_unanswered_message_creates_one_task_notification_and_audit_event(): void
    {
        $user = User::factory()->withOrganization()->create();
        $membership = $user->memberships()->with('employee')->firstOrFail();
        $organizationId = $membership->organization_id;
        $employeeId = $membership->employee->id;
        $customer = Customer::create(['organization_id' => $organizationId, 'type' => 'company', 'display_name' => 'Important customer']);
        $now = now();
        $calendarId = (string) Str::uuid();
        $policyId = (string) Str::uuid();
        DB::table('business_calendars')->insert([
            'id' => $calendarId,
            'organization_id' => $organizationId,
            'country_code' => 'LV',
            'weekend_days' => json_encode([0, 6], JSON_THROW_ON_ERROR),
            'timezone' => 'Europe/Riga',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('unanswered_policies')->insert([
            'id' => $policyId,
            'organization_id' => $organizationId,
            'business_days' => 5,
            'business_calendar_id' => $calendarId,
            'auto_create_task' => true,
            'notify_responsible' => true,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $modelId = (string) Str::uuid();
        $versionId = (string) Str::uuid();
        DB::table('forecast_models')->insert([
            'id' => $modelId,
            'name' => 'Importance',
            'type' => 'classification',
            'owner' => 'Nexus',
            'status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('model_versions')->insert([
            'id' => $versionId,
            'model_id' => $modelId,
            'version' => '1.0',
            'artifact_ref' => 'test://model',
            'features_schema' => '{}',
            'approved_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $accountId = (string) Str::uuid();
        $threadId = (string) Str::uuid();
        $messageId = (string) Str::uuid();
        DB::table('communication_accounts')->insert([
            'id' => $accountId,
            'organization_id' => $organizationId,
            'owner_employee_id' => $employeeId,
            'provider' => 'gmail',
            'external_account_id' => 'important@example.test',
            'credential_ref' => 'vault://test/mail',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('communication_threads')->insert([
            'id' => $threadId,
            'organization_id' => $organizationId,
            'customer_id' => $customer->id,
            'channel' => 'email',
            'subject' => 'Please reply',
            'assigned_employee_id' => $employeeId,
            'status' => 'open',
            'last_message_at' => $now->copy()->subDays(14),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('messages')->insert([
            'id' => $messageId,
            'organization_id' => $organizationId,
            'thread_id' => $threadId,
            'account_id' => $accountId,
            'direction' => 'inbound',
            'external_message_id' => 'important-1',
            'sender' => 'customer@example.test',
            'recipients_json' => '["important@example.test"]',
            'subject' => 'Please reply',
            'sent_received_at' => $now->copy()->subDays(14),
            'delivery_status' => 'received',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('ai_message_classifications')->insert([
            'id' => (string) Str::uuid(),
            'message_id' => $messageId,
            'model_version_id' => $versionId,
            'importance_score' => 0.95,
            'is_important' => true,
            'category' => 'customer_question',
            'rationale_json' => '{"signals":["question"]}',
            'classified_at' => $now,
        ]);

        $service = app(PendingMessageService::class);
        $this->assertSame(1, $service->scanOrganization($organizationId));
        $this->assertSame(0, $service->scanOrganization($organizationId));

        $this->assertDatabaseHas('pending_message_cases', ['organization_id' => $organizationId, 'triggering_message_id' => $messageId, 'status' => 'open']);
        $this->assertDatabaseHas('tasks', ['organization_id' => $organizationId, 'source_type' => 'pending_message_case', 'priority' => 'high']);
        $this->assertDatabaseHas('notification_deliveries', ['organization_id' => $organizationId, 'recipient_user_id' => $user->id, 'type' => 'important_message_unanswered']);
        $this->assertDatabaseHas('outbox_messages', ['organization_id' => $organizationId, 'event_type' => 'pending_message.detected']);
        $this->assertDatabaseHas('audit_logs', ['organization_id' => $organizationId, 'action' => 'pending_message.detected']);
        $this->assertDatabaseCount('pending_message_cases', 1);
    }
}
