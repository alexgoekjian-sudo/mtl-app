# Research: Fast MVP — Hybrid with Retool

Decision: Use a hybrid approach — Retool for the admin UI and a small, well-documented REST API + MySQL backing service for core data and integrations (Mollie, Cal.com, Gmail). This minimizes front-end work while preserving an extensible API surface for future web apps.

Rationale:
- Speed: Retool provides an immediate admin UI and app-like pages for data editing, reporting and manual reconciliation. It significantly reduces time-to-MVP for coordinator/finance workflows.
- Safety: Keep a single canonical data source (MySQL) and an idempotent REST API for webhooks and external integrations. Retool can connect directly to MySQL or call the REST API depending on security constraints.
- Extensibility: The REST API surfaces business logic (dedupe rules, invoice calculations, webhook idempotency) that are hard to safely implement client-side.

Recommended minimal stack for MVP (fast, low-friction):
- Backend: Lightweight API in PHP (Lumen/Laravel) if you prefer staying on shared hosting; or FastAPI (Python) / Express (Node) on a small VPS for easier local development. Both work with MySQL.
- Database: MySQL (existing hosting) — use a separate schema for the app. Use migrations (Flyway, Liquibase, or framework migrations).
- Admin UI: Retool (connect to MySQL with a read/write DB user or to the REST API). Use Retool for staff-facing workflows (lead triage, invoicing, manual refund, import reconciliation).
- Integrations: Mollie (payments via webhook), Cal.com (bookings via webhook), Gmail (SMTP or Gmail API for transactional emails).
- Local import tooling: existing Python import adapter (dry-run) — keep this for migrations and repeatable imports.

Trade-offs / Notes:
- Security: Retool can be pointed at MySQL directly (fast) but that requires careful DB user scoping and network access. Alternative: Retool calls the app REST API (safer, adds small dev overhead).
- Hosting: If you must use shared hosting only, choose PHP (Laravel/Lumen) for the backend to avoid provisioning a VPS. If you can provision a small VPS, FastAPI/Express gives faster developer iteration.
- Future UX: Retool is excellent for admin workflows but not ideal for public-facing student/teacher booking flows — build a thin public web app later when needed.

Risks & Mitigations:
- Webhook reliability: implement idempotency keys and event persistence; provide admin retry UI in Retool.
- Sensitive data access in Retool: prefer REST API option for production; for early-stage, use DB-level RBAC and network restrictions.
- Refund logic & finance: keep refunds manual with admin approval to avoid accidental money movement.

Next decisions required (NEEDS CLARIFICATION resolved here per user's preference):
- Admin UI: Retool = confirmed (user preference)
- Payment gateway: Mollie = confirmed
- Booking provider: Cal.com = confirmed
- Certificates: export CSV for Google Sheets (external PDF generation) = confirmed

Artifacts produced from this research will feed the Phase 1 design: data-model.md, contracts/openapi.yaml and quickstart.md.
