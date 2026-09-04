# Nexus CRM

Production-oriented multi-tenant CRM based on the included Draw.io ER, class and use-case diagrams.

## Stack

- PHP 8.3+ and Laravel 13
- Vue 3, TypeScript, Inertia 3 and Tailwind CSS 4
- PostgreSQL 18 with Row-Level Security
- Redis queues/cache, S3-compatible object storage and Mailpit for local email
- Laravel Fortify (email verification, password reset, 2FA and passkeys) and Sanctum API authentication

## Start on Windows or Linux Mint

Requirements: Docker Desktop on Windows (WSL2 backend) or Docker Engine + Compose on Linux.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Open http://localhost:8000. Mailpit is at http://localhost:8025 and MinIO Console at http://localhost:9001.

Demo account after seeding: `owner@nexus.test` / `ChangeMe123!`. Change it immediately outside local development.

## Quality checks

```bash
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test
docker compose exec node pnpm run types:check
docker compose exec node pnpm run build
```

The REST contract is available at `/api/documentation` and `/openapi.yaml`. First-party Vue requests use secure session cookies; integrations may use scoped Sanctum bearer tokens and must send `X-Organization-Id`.

Customer CSV import/export is available in the CRM screen and through `/api/v1/customers/import` and `/api/v1/customers/export`. The header is `display_name,type,status,preferred_language,risk_level`; both comma and semicolon delimiters are accepted on import, and each import is atomic and limited to 5,000 rows.

The scheduler runs `crm:scan-pending` every 15 minutes. It creates a task and notification for an important inbound message that has no later reply, then raises the task priority and notifies a manager when the configured business-day escalation limit is reached.

See [architecture](docs/ARCHITECTURE.md) for tenant isolation and module boundaries. Provider credentials must be kept in a secret manager and referenced by `credential_ref`.
