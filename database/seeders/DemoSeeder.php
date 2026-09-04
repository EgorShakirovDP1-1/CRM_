<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(['email' => 'owner@nexus.test'], ['name' => 'Elena Petrova', 'password' => Hash::make('ChangeMe123!'), 'email_verified_at' => now(), 'status' => 'active']);
        $organization = Organization::updateOrCreate(['slug' => 'nexus-demo'], ['name' => 'Nexus Demo Studio', 'timezone' => 'Europe/Riga', 'data_region' => 'EU', 'status' => 'active']);
        $branch = Branch::updateOrCreate(['organization_id' => $organization->id, 'name' => 'Rīga Centre'], ['timezone' => 'Europe/Riga', 'status' => 'active']);
        $employee = Employee::updateOrCreate(['organization_id' => $organization->id, 'user_id' => $user->id], ['branch_id' => $branch->id, 'first_name' => 'Elena', 'last_name' => 'Petrova', 'job_title' => 'Owner', 'status' => 'active']);
        $membership = OrganizationUser::updateOrCreate(['organization_id' => $organization->id, 'user_id' => $user->id], ['employee_id' => $employee->id, 'status' => 'active', 'joined_at' => now()]);
        $ownerRole = Role::where('code', 'owner')->firstOrFail();
        if (! $membership->roles()->where('roles.id', $ownerRole->id)->exists()) {
            $membership->roles()->attach($ownerRole->id, ['id' => (string) Str::uuid(), 'assigned_by_user_id' => $user->id, 'assigned_at' => now()]);
        }

        DB::table('business_calendars')->updateOrInsert(['organization_id' => $organization->id, 'country_code' => 'LV'], ['id' => (string) Str::uuid(), 'weekend_days' => json_encode([0, 6]), 'timezone' => 'Europe/Riga', 'created_at' => now(), 'updated_at' => now()]);
        $calendarId = DB::table('business_calendars')->where('organization_id', $organization->id)->value('id');
        DB::table('unanswered_policies')->updateOrInsert(['organization_id' => $organization->id], ['id' => (string) Str::uuid(), 'business_days' => 5, 'business_calendar_id' => $calendarId, 'auto_create_task' => true, 'notify_responsible' => true, 'escalation_days' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        $customerNames = ['Baltic Design SIA', 'Anna Ozola', 'Nordic Coffee OÜ', 'Mārtiņš Kalniņš'];
        $customers = collect($customerNames)->map(fn ($name, $i) => Customer::updateOrCreate(['organization_id' => $organization->id, 'display_name' => $name], ['type' => $i % 2 === 0 ? 'company' : 'person', 'owner_employee_id' => $employee->id, 'status' => 'active', 'preferred_language' => $i === 2 ? 'en' : 'ru']));
        DB::table('lead_sources')->updateOrInsert(['organization_id' => $organization->id, 'code' => 'website'], ['id' => (string) Str::uuid(), 'name' => 'Website', 'channel' => 'web', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $sourceId = DB::table('lead_sources')->where('organization_id', $organization->id)->where('code', 'website')->value('id');
        foreach (['Aurora Labs', 'Mila Jensen', 'Greenline SIA'] as $name) {
            Lead::updateOrCreate(['organization_id' => $organization->id, 'name' => $name], ['source_id' => $sourceId, 'owner_employee_id' => $employee->id, 'status' => 'new']);
        }
        $pipeline = Pipeline::updateOrCreate(['organization_id' => $organization->id, 'name' => 'Sales'], ['entity_type' => 'deal', 'is_default' => true]);
        $stages = collect([['New', 1, 15], ['Qualified', 2, 45], ['Proposal', 3, 70], ['Won', 4, 100]])->map(fn ($stage) => PipelineStage::updateOrCreate(['pipeline_id' => $pipeline->id, 'position' => $stage[1]], ['name' => $stage[0], 'probability_pct' => $stage[2], 'is_won' => $stage[0] === 'Won', 'is_lost' => false]));
        foreach ($customers->take(3) as $i => $customer) {
            Deal::updateOrCreate(['organization_id' => $organization->id, 'title' => 'Growth package — '.$customer->display_name], ['customer_id' => $customer->id, 'pipeline_id' => $pipeline->id, 'stage_id' => $stages[$i]->id, 'owner_employee_id' => $employee->id, 'expected_amount' => 1800 + $i * 1250, 'expected_close_date' => now()->addDays(10 + $i * 5), 'status' => 'open']);
        }
        foreach ([['Prepare proposal', 'high', 1], ['Reply to contract questions', 'urgent', 0], ['Review next week schedule', 'normal', 3]] as [$title, $priority, $days]) {
            CrmTask::updateOrCreate(['organization_id' => $organization->id, 'title' => $title], ['assignee_employee_id' => $employee->id, 'type' => 'todo', 'due_at' => now()->addDays($days), 'priority' => $priority, 'status' => 'open']);
        }
        $catalog = CatalogItem::updateOrCreate(['organization_id' => $organization->id, 'sku' => 'CONSULT-60'], ['type' => 'service', 'name' => 'Business consultation', 'unit' => 'hour', 'tax_rate_pct' => 21, 'is_active' => true]);
        Service::updateOrCreate(['organization_id' => $organization->id, 'catalog_item_id' => $catalog->id], ['name' => 'Business consultation', 'duration_min' => 60, 'buffer_before_min' => 10, 'buffer_after_min' => 10, 'capacity' => 1, 'is_active' => true]);
        foreach ($customers->take(3) as $i => $customer) {
            Appointment::updateOrCreate(['organization_id' => $organization->id, 'customer_id' => $customer->id, 'starts_at' => now()->addHours(2 + $i * 2)->startOfHour()], ['branch_id' => $branch->id, 'employee_id' => $employee->id, 'ends_at' => now()->addHours(3 + $i * 2)->startOfHour(), 'status' => 'confirmed', 'source' => 'staff']);
        }
        $this->seedSecondaryModules($organization->id, $user->id, $employee->id, $customers->first()->id);
    }

    private function seedSecondaryModules(string $organizationId, string $userId, string $employeeId, string $customerId): void
    {
        $now = now();
        $modelId = (string) Str::uuid();
        DB::table('forecast_models')->insert(['id' => $modelId, 'name' => 'Message priority', 'type' => 'classification', 'owner' => 'Nexus', 'status' => 'approved', 'created_at' => $now, 'updated_at' => $now]);
        $versionId = (string) Str::uuid();
        DB::table('model_versions')->insert(['id' => $versionId, 'model_id' => $modelId, 'version' => '1.0-demo', 'artifact_ref' => 'local://deterministic', 'features_schema' => '{}', 'metrics_json' => '{"f1":0.91}', 'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        $accountId = (string) Str::uuid();
        DB::table('communication_accounts')->insert(['id' => $accountId, 'organization_id' => $organizationId, 'owner_employee_id' => $employeeId, 'provider' => 'gmail', 'external_account_id' => 'demo@nexus.test', 'credential_ref' => 'vault://demo/mail', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $threadId = (string) Str::uuid();
        DB::table('communication_threads')->insert(['id' => $threadId, 'organization_id' => $organizationId, 'customer_id' => $customerId, 'channel' => 'email', 'subject' => 'Contract timeline', 'assigned_employee_id' => $employeeId, 'status' => 'open', 'last_message_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        $messageId = (string) Str::uuid();
        DB::table('messages')->insert(['id' => $messageId, 'organization_id' => $organizationId, 'thread_id' => $threadId, 'account_id' => $accountId, 'direction' => 'in', 'external_message_id' => 'demo-1', 'sender' => 'client@example.test', 'recipients_json' => '["demo@nexus.test"]', 'subject' => 'Contract timeline', 'body_text' => 'Could you confirm the delivery date?', 'sent_received_at' => $now, 'delivery_status' => 'received', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('ai_message_classifications')->insert(['id' => (string) Str::uuid(), 'message_id' => $messageId, 'model_version_id' => $versionId, 'importance_score' => .93, 'is_important' => true, 'category' => 'customer_question', 'rationale_json' => '{"signals":["deadline","question"]}', 'classified_at' => $now]);
        $policyId = DB::table('unanswered_policies')->where('organization_id', $organizationId)->value('id');
        DB::table('pending_message_cases')->insert(['id' => (string) Str::uuid(), 'organization_id' => $organizationId, 'thread_id' => $threadId, 'triggering_message_id' => $messageId, 'responsible_employee_id' => $employeeId, 'policy_id' => $policyId, 'due_at' => $now->addWeekdays(5), 'status' => 'open', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('documents')->insert(['id' => (string) Str::uuid(), 'organization_id' => $organizationId, 'customer_id' => $customerId, 'type' => 'contract', 'number' => 'CTR-2026-001', 'status' => 'approval', 'current_version' => 1, 'created_by_user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('suppliers')->insert(['id' => (string) Str::uuid(), 'organization_id' => $organizationId, 'name' => 'Baltic Supplies SIA', 'registration_no' => 'LV-DEMO-001', 'email' => 'supply@example.test', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
    }
}
