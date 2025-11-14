# Research: Retool-only Frontend

Decision: Use Retool as the primary and only frontend for staff/admin interfaces. The existing Lumen app remains available for background jobs/webhooks and optional API endpoints, but staff-facing UIs will be built in Retool connecting to the same MySQL database.

Rationale:
- Speed: Building UIs in Retool is substantially faster than developing a bespoke admin frontend.
- Maintenance: Reduces frontend code to maintain; product changes can be iterated in Retool.
- Coverage: Retool supports data tables, forms, workflows, and scripting for business logic.

Trade-offs:
- Vendor dependency: Retool is a hosted/costed service; consider budgeting and export strategies.
- Security surface: Granting Retool DB access requires careful privilege scoping and network control. For production, prefer Retool -> REST API or a restricted DB user with minimal privileges.
- Public UX: Retool is not a replacement for a public-facing student-facing site. Retool is for staff/admin flows only.

Recommendations (must-do for production):
1. Create a least-privilege DB user for Retool. Start read-only and add write permissions only on the tables Retool must mutate.
2. Use read-only views for reporting queries and large joins to protect production schema and optimize performance.
3. Prefer API-backed writes for actions that trigger side-effects (payment refunds, external webhooks, import jobs). Implement small authenticated Lumen endpoints for these if needed.
4. Store DB credentials in Retool's secret manager (do not check them into the repo).
5. Monitor slow queries and consider read-replicas or materialized aggregates for heavy Retool dashboards.

Acceptance criteria for Retool-only approach:
- Retool apps can CRUD required entities (Leads, Students, Courses, Enrollments, Invoices) against the existing DB.
- No new public frontend code is required for staff workflows.
- Security controls are in place (least-privilege DB user, secrets managed, optional API for side-effectful operations).

Artifacts that support this phase: `data-model.md`, `quickstart.md`, `tasks.md` (updated for Retool-only flow).
