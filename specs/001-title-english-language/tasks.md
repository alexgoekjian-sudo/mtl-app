# Tasks — Retool-only Implementation Plan

Overview
- Prioritise DB readiness and Retool resource + pages. Only add small API endpoints where writes must enforce business logic or side-effects.

Phase 0 — Prepare DB & infra (P0)
1. Audit the current schema and create migrations if missing (Lead, Student, Teacher, CourseOffering, Session, Enrollment, Invoice, Payment, AttendanceRecord).
2. Create read-only views for heavy reporting queries.
3. Create a Retool least-privilege DB user and place credentials into Retool secrets.
4. Ensure database network access from Retool (open port or use SSH tunnel). Verify connectivity.

Phase 1 — Retool resource & pages (P0)
5. Create the MySQL resource in Retool using the new DB user.
6. Implement the following Retool pages (iterate):
   - Leads (list, create, convert-to-student action)
   - Students (list, edit, enroll action)
   - Courses & Offerings (manage offerings, capacity)
   - Enrollments (create/list/cancel)
   - Invoices & Payments (list, record payment via API endpoint recommended)
   - Imports (upload helper + show normalized preview rows — import adapter run locally to prepare CSV)
7. Add saved queries for common CRUD operations and test with parameterized queries.

Phase 2 — Small API endpoints (optional, P1)
8. If an operation requires strong business logic (idempotency, external webhooks, payments), implement a minimal authenticated Lumen endpoint to perform the action server-side and call that endpoint from Retool instead of writing directly to DB.
9. Add endpoints for: record-payment (idempotent), trigger-import (schedule), retry-webhook (admin), and any heavy transactional operations.

Phase 3 — QA & monitoring (P0)
10. Create integration smoke tests that perform key flows via Retool saved queries and API endpoints (where used).
11. Add monitoring/alerting for failed webhooks and slow DB queries.
12. Document operational runbooks for DB backups, Retool resource rotation, and how to rollback changes.

Deliverables & acceptance criteria
- Retool workspace with working pages for the main admin flows.
- DB user and views in place; documented grants and security notes.
- Minimal server endpoints for side-effectful operations with unit tests.
- Running smoke tests that validate create→enroll→invoice→pay flows.

Priority legend: P0 = immediate must-do; P1 = next-priority.
