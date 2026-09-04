<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TENANT_TABLES = [
        'branches', 'employees', 'business_calendars', 'audit_logs', 'catalog_items', 'customers', 'contacts',
        'lead_sources', 'leads', 'pipelines', 'deals', 'tasks', 'activities', 'tags', 'campaigns', 'work_schedules',
        'work_plans', 'services', 'locations', 'resources', 'availability_rules', 'appointments', 'waitlist_entries',
        'service_packages', 'subscriptions', 'gift_certificates', 'loyalty_accounts', 'calendar_connections', 'reviews',
        'document_templates', 'documents', 'approval_workflows', 'signature_requests', 'file_objects', 'price_lists',
        'customer_orders', 'payment_sessions', 'payments', 'invoices', 'financial_categories', 'financial_transactions',
        'suppliers', 'warehouses', 'stock_movements', 'purchase_orders', 'goods_receipts', 'communication_accounts',
        'communication_threads', 'messages', 'unanswered_policies', 'pending_message_cases', 'bot_flows', 'bot_sessions',
        'message_templates', 'notification_deliveries', 'retention_policies', 'legal_basis_records',
        'privacy_policy_versions', 'consents', 'external_checks', 'risk_assessments', 'customer_forecasts',
        'human_reviews', 'data_subject_requests', 'data_erasure_jobs', 'cookie_preferences', 'security_incidents',
        'outbox_messages',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach (self::TENANT_TABLES as $table) {
            DB::connection()->getPdo()->exec(sprintf(
                "ALTER TABLE %s ENABLE ROW LEVEL SECURITY; CREATE POLICY tenant_isolation ON %s USING (organization_id::text = current_setting('app.current_organization_id', true)) WITH CHECK (organization_id::text = current_setting('app.current_organization_id', true));",
                $table,
                $table,
            ));
        }
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_audit_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'audit_logs is append-only';
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER audit_logs_immutable BEFORE UPDATE OR DELETE ON audit_logs
            FOR EACH ROW EXECUTE FUNCTION prevent_audit_mutation();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_immutable ON audit_logs; DROP FUNCTION IF EXISTS prevent_audit_mutation();');
        foreach (array_reverse(self::TENANT_TABLES) as $table) {
            DB::connection()->getPdo()->exec("DROP POLICY IF EXISTS tenant_isolation ON {$table}; ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY;");
        }
    }
};
