<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PendingMessageService
{
    public function __construct(private readonly BusinessDayPolicy $businessDays) {}

    public function scanOrganization(string $organizationId): int
    {
        $policy = DB::table('unanswered_policies')
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->latest('created_at')
            ->first();
        if ($policy === null) {
            return 0;
        }

        $candidates = DB::table('messages as inbound')
            ->join('communication_threads as threads', 'threads.id', '=', 'inbound.thread_id')
            ->join('ai_message_classifications as classification', 'classification.message_id', '=', 'inbound.id')
            ->where('inbound.organization_id', $organizationId)
            ->whereIn('inbound.direction', ['in', 'inbound'])
            ->where('classification.is_important', true)
            ->whereNotNull('threads.assigned_employee_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('messages as reply')
                    ->whereColumn('reply.thread_id', 'inbound.thread_id')
                    ->whereIn('reply.direction', ['out', 'outbound'])
                    ->whereColumn('reply.sent_received_at', '>', 'inbound.sent_received_at');
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('pending_message_cases as existing_case')
                    ->whereColumn('existing_case.triggering_message_id', 'inbound.id');
            })
            ->select([
                'inbound.id as message_id',
                'inbound.thread_id',
                'inbound.subject',
                'inbound.sent_received_at',
                'threads.customer_id',
                'threads.assigned_employee_id',
            ])
            ->oldest('inbound.sent_received_at')
            ->limit(500)
            ->get();

        $created = 0;
        foreach ($candidates as $candidate) {
            $receivedAt = CarbonImmutable::parse((string) $candidate->sent_received_at);
            $dueAt = $this->businessDays->dueAt($receivedAt, (int) $policy->business_days, (string) $policy->business_calendar_id);
            if ($dueAt->isFuture()) {
                continue;
            }

            $wasCreated = DB::transaction(function () use ($candidate, $dueAt, $organizationId, $policy): bool {
                $duplicate = DB::table('pending_message_cases')
                    ->where('triggering_message_id', $candidate->message_id)
                    ->lockForUpdate()
                    ->exists();
                if ($duplicate) {
                    return false;
                }

                $caseId = (string) Str::uuid();
                $now = now();
                DB::table('pending_message_cases')->insert([
                    'id' => $caseId,
                    'organization_id' => $organizationId,
                    'thread_id' => $candidate->thread_id,
                    'triggering_message_id' => $candidate->message_id,
                    'responsible_employee_id' => $candidate->assigned_employee_id,
                    'policy_id' => $policy->id,
                    'due_at' => $dueAt,
                    'status' => 'open',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ((bool) $policy->auto_create_task) {
                    $taskId = (string) Str::uuid();
                    DB::table('tasks')->insert([
                        'id' => $taskId,
                        'organization_id' => $organizationId,
                        'assignee_employee_id' => $candidate->assigned_employee_id,
                        'customer_id' => $candidate->customer_id,
                        'type' => 'pending_reply',
                        'title' => 'Reply to important message: '.($candidate->subject ?: $candidate->message_id),
                        'due_at' => $dueAt,
                        'priority' => 'high',
                        'status' => 'open',
                        'source_type' => 'pending_message_case',
                        'source_id' => $caseId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('pending_case_tasks')->insert([
                        'pending_case_id' => $caseId,
                        'task_id' => $taskId,
                        'created_at' => $now,
                    ]);
                }

                if ((bool) $policy->notify_responsible) {
                    $recipientUserId = DB::table('employees')->where('id', $candidate->assigned_employee_id)->value('user_id');
                    if (is_string($recipientUserId)) {
                        DB::table('notification_deliveries')->insert([
                            'id' => (string) Str::uuid(),
                            'organization_id' => $organizationId,
                            'recipient_user_id' => $recipientUserId,
                            'type' => 'important_message_unanswered',
                            'channel' => 'database',
                            'payload_json' => json_encode(['case_id' => $caseId, 'thread_id' => $candidate->thread_id], JSON_THROW_ON_ERROR),
                            'status' => 'pending',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }

                $this->recordEvent($organizationId, 'pending_message.detected', $caseId, [
                    'message_id' => $candidate->message_id,
                    'due_at' => $dueAt->toIso8601String(),
                ]);

                return true;
            }, 3);

            if ($wasCreated) {
                $created++;
            }
        }

        $this->escalateOverdue(
            $organizationId,
            (string) $policy->id,
            $policy->escalation_days === null ? null : (int) $policy->escalation_days,
            (string) $policy->business_calendar_id,
        );

        return $created;
    }

    private function escalateOverdue(string $organizationId, string $policyId, ?int $escalationDays, string $calendarId): void
    {
        if ($escalationDays === null) {
            return;
        }

        $cases = DB::table('pending_message_cases')
            ->where('organization_id', $organizationId)
            ->where('policy_id', $policyId)
            ->where('status', 'open')
            ->where('due_at', '<=', now())
            ->limit(500)
            ->get();
        foreach ($cases as $case) {
            $escalateAt = $this->businessDays->dueAt(
                CarbonImmutable::parse((string) $case->due_at),
                $escalationDays,
                $calendarId,
            );
            if ($escalateAt->isFuture()) {
                continue;
            }

            DB::transaction(function () use ($case, $organizationId): void {
                $updated = DB::table('pending_message_cases')
                    ->where('id', $case->id)
                    ->where('status', 'open')
                    ->update(['status' => 'escalated', 'updated_at' => now()]);
                if ($updated === 0) {
                    return;
                }
                DB::table('tasks')
                    ->where('organization_id', $organizationId)
                    ->where('source_type', 'pending_message_case')
                    ->where('source_id', $case->id)
                    ->whereIn('status', ['open', 'in_progress'])
                    ->update(['priority' => 'urgent', 'updated_at' => now()]);

                $managerUserId = DB::table('employees as responsible')
                    ->leftJoin('employees as manager', 'manager.id', '=', 'responsible.manager_id')
                    ->where('responsible.id', $case->responsible_employee_id)
                    ->value('manager.user_id');
                if (! is_string($managerUserId)) {
                    $managerUserId = DB::table('organization_users')
                        ->where('organization_id', $organizationId)
                        ->where('status', 'active')
                        ->oldest('joined_at')
                        ->value('user_id');
                }
                if (is_string($managerUserId)) {
                    DB::table('notification_deliveries')->insert([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $organizationId,
                        'recipient_user_id' => $managerUserId,
                        'type' => 'important_message_escalated',
                        'channel' => 'database',
                        'payload_json' => json_encode(['case_id' => $case->id], JSON_THROW_ON_ERROR),
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $this->recordEvent($organizationId, 'pending_message.escalated', (string) $case->id);
            }, 3);
        }
    }

    /** @param array<string, mixed> $payload */
    private function recordEvent(string $organizationId, string $eventType, string $aggregateId, array $payload = []): void
    {
        $now = now();
        DB::table('outbox_messages')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'event_type' => $eventType,
            'aggregate_id' => $aggregateId,
            'payload_json' => json_encode($payload, JSON_THROW_ON_ERROR),
            'occurred_at' => $now,
        ]);
        DB::table('audit_logs')->insert([
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'actor_user_id' => null,
            'action' => $eventType,
            'entity_type' => 'pending_message_case',
            'entity_id' => $aggregateId,
            'metadata_json' => json_encode($payload, JSON_THROW_ON_ERROR),
            'created_at' => $now,
        ]);
    }
}
