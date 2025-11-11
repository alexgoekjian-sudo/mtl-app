# Quickstart — MVP (Retool hybrid)

Purpose: give a minimal set of steps to get the MVP running locally and connect Retool for admin operations.

Prerequisites:
- MySQL server (or use the existing hosted MySQL)
- PHP 8.1+ (Laravel/Lumen) OR Python 3.11+ (FastAPI) depending on chosen backend
- Retool account (or self-hosted Retool)

1) Prepare the database
- Create database: `mtl_app` and a service user with limited privileges for app migrations and an admin DB user for Retool if you plan to connect Retool directly.

2) Start the backend (example choices):
- Option A (shared host / PHP):
  - Install Lumen/Laravel, configure `.env` with DB and Mollie/Cal.com/Gmail keys
  - Run migrations: `php artisan migrate`
  - Seed admin user: `php artisan db:seed --class=AdminUserSeeder`

- Option B (dev-friendly, VPS):
  - Use FastAPI (Python) with Alembic migrations. Configure `DATABASE_URL` pointing to MySQL.
  - Run: `uvicorn app.main:app --reload`

3) Connect Retool (admin UI)
- Option 1: Direct MySQL connector (fast)
  - Create a read/write DB user restricted by schema and IP.
  - In Retool, add a MySQL resource pointing to the DB; build pages for Leads, Students, Enrollments, Invoices.

- Option 2: REST API resource (recommended for production)
  - Expose the REST API endpoints with JWT auth.
  - In Retool, add a REST resource and reference endpoints for CRUD + custom actions (e.g., trigger refund endpoint).

4) Imports & data migration
- Put CSVs into `specs/001-title-english-language/imports/` and run the existing Python adapter in dry-run to preview normalized JSON output. After review, run apply mode to write to staging DB or call the REST API.

5) Webhooks
- Configure Mollie and Cal.com to send webhooks to `/webhooks/mollie` and `/webhooks/calcom`. Implement idempotent handlers that persist events and update invoices/bookings.

6) Admin tasks to run initially
- Confirm Admin user exists, configure Retool resources, import historical data in dry-run mode then apply to staging, verify payments and bookings are reconciled.

Try it (local):
```powershell
# Example: health-check
Invoke-RestMethod -Uri http://localhost:8000/health -Method GET
```

Notes:
- For early testing, Retool direct DB connection is the fastest path to a usable admin app. Move to API-based Retool connection when security and multi-environment deployments are required.
