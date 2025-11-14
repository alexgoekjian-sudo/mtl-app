# Retool-only Frontend Plan (MTL_App)

Goal

- Replace the default web frontend with a Retool-based UI that talks directly to the existing database (same DB connection as the backend). Keep the Lumen app as a minimal API/worker or maintenance backend (optional).

Success criteria

- Retool apps can render and mutate necessary data for teachers/admins.
- No new public-facing frontend code required beyond Retool apps.
- Existing database schema is reused, with read-only views or secured DB users created where needed.
- Production access controls enforced: DB user permissions, network restrictions, and secrets stored securely in Retool.

Scope

- Frontend: Retool only (no React/Vue work required).
- Backend: existing database used as data source. Minimal API endpoints or read-only views provided only if Retool requires them (e.g., for complex joins or security reasons).
- Deliverables: architecture note, quickstart for Retool resource creation, data-model summary, task list.

Constraints

- DB is MySQL (see `.env.example` in repo). Retool supports MySQL resources.
- Hosting is Plesk (no changes required for Retool: Retool is a hosted service or self-hosted provided separately).

Security notes

- Create a least-privilege DB user for Retool (select/insert/update/delete only on specific tables as required). Prefer read-only for analytics views.
- Use parameterized queries in Retool and avoid embedding raw SQL with untrusted inputs.
- Keep `.env` secrets out of the repo; use Retool's Secret Manager for DB credentials.

Next steps

- Create Retool resource for the MySQL database (host, port, username, password from `.env`).
- Define the core queries & saved queries (list, create, update, delete) for primary tables.
- Build the initial Retool dashboards (Courses, Students, Enrollments, Imports).
- Optionally: create small, secure API endpoints (Lumen) for operations that should not be performed directly from the DB (e.g., complex business logic, third-party calls, imports).

Accepted artifacts in this plan

- `research.md`, `data-model.md`, `quickstart.md`, and `tasks.md` in the same specs directory.
