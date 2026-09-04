<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\PendingMessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScanPendingMessages extends Command
{
    protected $signature = 'crm:scan-pending';

    protected $description = 'Create and escalate tasks for important inbound messages without a reply';

    public function handle(PendingMessageService $pending): int
    {
        $created = 0;
        foreach (Organization::query()->where('status', 'active')->pluck('id') as $organizationId) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("select set_config('app.current_organization_id', ?, false)", [$organizationId]);
            }
            try {
                $created += $pending->scanOrganization((string) $organizationId);
            } finally {
                if (DB::getDriverName() === 'pgsql') {
                    DB::statement("select set_config('app.current_organization_id', '', false)");
                }
            }
        }

        $this->info("Created {$created} pending-message cases.");

        return self::SUCCESS;
    }
}
