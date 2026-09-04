# Nexus CRM architecture

The application follows the supplied ER, class and use-case diagrams. Laravel is split into HTTP controllers, application services, Eloquent repositories/models and integration ports. Vue 3 pages are delivered through Inertia.

## Security boundaries

- Every business record carries `organization_id`; `BelongsToOrganization` applies an Eloquent scope and sets the tenant on inserts.
- PostgreSQL Row-Level Security is enabled as defence in depth. Production should use a non-owner database role so RLS cannot be bypassed.
- The seven roles are system-owned. Role assignment must only move down the fixed hierarchy.
- Audit records are append-only in both the model and PostgreSQL trigger.
- Credentials are represented only by `credential_ref`; secrets belong in the deployment secret store.
- Webhooks are idempotent through `webhook_receipts`; cross-module events use `outbox_messages`.

## Modules

1. Organization, access, employees, schedules and plans.
2. Leads, customers, contacts, pipelines, deals, tasks, activities and campaigns.
3. Services, staff/resources, appointments, waitlist, packages, certificates and loyalty.
4. Unified inbox, AI classification, unanswered-message cases, bots and notifications.
5. Versioned documents, approval workflows and e-signature adapters.
6. Catalog, full payments, invoices, management accounting, suppliers and inventory.
7. Model registry, forecasts, risk assessments, lawful source checks, consent, retention, DSR and incidents.

External providers implement ports and are selected by configuration. The repository intentionally ships with a safe `unconfigured` adapter; Gmail/Outlook, Telegram/WhatsApp, payment, e-signature and lawful-risk providers require project-specific credentials and adapters. OAuth tokens and identity numbers must never be stored directly in the database.
