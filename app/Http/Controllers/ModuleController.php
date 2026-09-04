<?php

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    private const MODULES = [
        'team' => ['employees', 'work_schedules', 'work_plans', 'audit_logs'],
        'crm' => ['customers', 'leads', 'deals', 'tasks'],
        'booking' => ['services', 'appointments', 'waitlist_entries', 'reviews'],
        'communications' => ['communication_threads', 'messages', 'pending_message_cases', 'notification_deliveries'],
        'documents' => ['document_templates', 'documents', 'approval_workflows', 'signature_requests'],
        'finance' => ['customer_orders', 'invoices', 'payments', 'suppliers', 'stock_movements'],
        'risk' => ['risk_assessments', 'customer_forecasts', 'consents', 'data_subject_requests'],
    ];

    public function __invoke(string $module, TenantContext $context): Response
    {
        abort_unless(isset(self::MODULES[$module]), 404);
        $organizationId = $context->organizationId();
        $tables = self::MODULES[$module];
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->where('organization_id', $organizationId)->count();
        }
        $primary = $tables[0];
        $recent = DB::table($primary)->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->limit(10)->get()->map(fn ($row) => (array) $row);

        return Inertia::render('Module', compact('module', 'counts', 'recent'));
    }
}
