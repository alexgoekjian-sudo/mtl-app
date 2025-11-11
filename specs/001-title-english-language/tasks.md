# Tasks (implementation-plan) — ordered for MVP (Retool hybrid)

NOTE: Tasks are ordered to favor TDD-first and small iterative increments. Estimate: 2-4 week MVP depending on dev availability.

Phase A — Design & infra (short)
1. Create DB migrations for core entities (Lead, Student, Teacher, CourseOffering, Session, Enrollment, AttendanceRecord, Invoice, Payment) — P1
2. Create minimal REST API scaffold (Laravel Lumen or FastAPI) with JWT auth and role support — P1
3. Add OpenAPI contract tests (use `specs/001-title-english-language/contracts/openapi.yaml`) — P1 (tests fail initially)

Phase B — Core features (TDD order)
4. Implement Lead create/list endpoints + unit tests — P2
5. Implement Student conversion (lead->student) + tests — P2
6. Implement CourseOffering CRUD + seed importer for Courses CSV + integration test — P2
7. Implement Enrollment create/list + tests — P2
8. Implement Invoice creation (line items) and invoice total calc + tests — P2
9. Implement Payment record endpoint (idempotent, used by Mollie webhook) + tests — P2
10. Implement Attendance recording endpoint and teacher role restrictions + tests — P2

Phase C — Admin & integrations
11. Configure Retool pages for Leads, Students, Enrollments, Invoices, Payments, and Attendance reporting — P1
12. Implement webhook handlers: Mollie (payments) and Cal.com (bookings) with idempotency and event persistence; admin retry UI in Retool — P1
13. Add import adapter apply-mode to write to staging DB or call the REST API (after dry-run approval) — P1

Phase D — QA & Ops
14. Create basic integration smoke test that runs: create-student → enroll → invoice → record-payment → mark-attendance → export completion CSV — P1
15. Add monitoring/logging, backups and DB migration CI step — P1
16. Security review for Retool DB access vs API mode and finalize production wiring — P1

Extras (post-MVP)
- Fuzzy course matching for import reconciliation
- Teacher-facing web attendance sheet (React) if Retool is insufficient for UX
- Refund automation and accounting integrations

Priority legend: P1 = must-have for MVP launch; P2 = next-important; P3 = optional post-MVP
