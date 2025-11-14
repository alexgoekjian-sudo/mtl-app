# Quickstart — Retool-only (Admin UI)

Purpose: minimal steps to connect Retool to the existing MySQL database and build admin pages quickly.

Prerequisites
- Retool account (or self-hosted Retool installation)
- MySQL server reachable from Retool (publicly accessible IP or via SSH tunnel / private network)
- DB credentials (create a dedicated Retool DB user; see `.env.example` for naming conventions)

1) Provision a Retool MySQL resource
- In Retool, go to Resources → Create new → MySQL.
- Use these values (example, replace with real secrets stored in Retool):
  - Host: the DB host (ex: 127.0.0.1 or host provided by your provider)
  - Port: 3306
  - Database: mtl_app_staging (or the production DB name)
  - Username: mtl_app_user_retool (create this user with restricted permissions)
  - Password: (secret)
  - SSL: enable if your DB supports it
- Save the resource and test connection.

2) Recommended DB user privileges for Retool
- For read-only dashboards: GRANT SELECT on specific tables and views.
- For admin workflows that must write: GRANT INSERT, UPDATE, DELETE on specific tables only.
- Example SQL to create a restricted user (run as a DBA):

```sql
CREATE USER 'mtl_retool'@'%' IDENTIFIED BY 'strong_password';
GRANT SELECT ON mtl_app.* TO 'mtl_retool'@'%';
-- Narrow grants later as needed:
GRANT INSERT, UPDATE ON mtl_app.enrollments TO 'mtl_retool'@'%';
FLUSH PRIVILEGES;
```

3) Quick Retool queries to create
- List students:
  SELECT id, first_name, last_name, email FROM students ORDER BY last_name LIMIT 100;
- Create student (use parameterized insert):
  INSERT INTO students (first_name, last_name, email, phone, created_at, updated_at) VALUES ({{first_name}}, {{last_name}}, {{email}}, {{phone}}, NOW(), NOW());
- Enrollment create (use a prepared query with bind params):
  INSERT INTO enrollments (student_id, course_offering_id, status, enrolled_at) VALUES ({{student_id}}, {{course_offering_id}}, 'registered', NOW());

4) Recommended Retool UI pages
- Leads dashboard: table + quick-create modal + convert-to-student action.
- Students: table, edit modal, and enrollment quick-action.
- Courses & Offerings: manage course offerings and capacity.
- Enrollments & Attendance: list and mark attendance.
- Finance: invoices list, record payment action (prefer via small Lumen endpoint to keep idempotency rules in backend).

5) Imports & data validation
- Use the existing Python import adapter to normalize CSVs locally first (`specs/001-title-english-language/imports/`), then import into DB with safe INSERTs or call a backend endpoint that schedules import jobs.

6) Webhooks & side-effects
- For payments (Mollie) and bookings (Cal.com), keep webhook handlers in the Lumen app and persist events in the DB. Retool can show webhook event lists and trigger retries via an admin endpoint.

7) Testing
- In Retool, test each saved query with parameter values. Use audit columns (created_by, updated_by) where possible to trace admin actions.

8) Rollout
- Start in staging DB. After verifying workflows, create a Retool workspace for production and set production DB resource with a carefully scoped user.

Example curl health-check (local dev):
```powershell
Invoke-RestMethod -Uri http://localhost:8000/health -Method GET
```
