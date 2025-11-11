# MTL_App Constitution (Speckit) — Minimal requirements

Purpose
-------
This document defines the minimal, essential structure and requirements for the MTL_App (an English language school management application built with Speckit). It captures scope, core modules, data model primitives, security and non-functional requirements, and clear acceptance criteria for an MVP.

Scope
-----
- Manage students, teachers, courses and enrollments
- Track attendance and lesson-level presence
- Basic CRM for contacts and communications
- Task management for staff workflows
- Invoice generation and payment recording
- Basic reporting (attendance, revenue, outstanding invoices)

Core Modules (high level)
--------------------------
1. CRM: contacts (students, parents, companies), notes, communication log.
2. Courses & Scheduling: course, class/session, teacher assignment, room/online link.
3. Enrollment: enroll/unenroll students into courses and classes.
4. Attendance: mark attendance per session; support statuses (present, late, absent, excused).
5. Tasks: create/assign tasks to staff, with due-date, status, and related entity link.
6. Invoicing & Payments: create invoices, line items, apply payments, track status (draft, sent, paid, overdue).
7. Reporting & Exports: CSV/JSON export of key records and basic dashboards.

Minimal Data Model (entities + minimal fields)
---------------------------------------------
- Student: id, first_name, last_name, email, phone, dob (optional), status (active/inactive), contacts (parent/guardian links)
- Teacher: id, name, email, phone, availability (basic)
- Contact: id, name, role (parent/company/other), email, phone
- Course: id, title, level, price_per_session (optional)
- Session/Class: id, course_id, teacher_id, start_datetime, end_datetime, location
- Enrollment: id, student_id, course_id, status, start_date, end_date
- AttendanceRecord: id, session_id, student_id, status, note, recorded_by, recorded_at
- Task: id, title, description, assigned_to (user), related_entity (polymorphic ref), due_date, status
- Invoice: id, invoice_number, billing_contact_id, items [{description, qty, unit_price}], total, status, issued_date, due_date
- Payment: id, invoice_id, amount, method, date

APIs / Integration contracts (minimal)
------------------------------------
- Provide a RESTful JSON API (or Speckit-standard endpoints) exposing core CRUD for the entities above.
- For each resource: list, get, create, update, delete (soft-delete where appropriate).
- Attendance: endpoint to mark multiple students for a session in one request.
- Invoice: endpoint to generate PDF (or link to pre-generated PDF) and to record payments.
- Export endpoints: CSV/JSON for students, attendances, invoices.

Authentication, Authorization & Roles
------------------------------------
- Authentication: token-based (JWT) for API; session/cookie for web UI per deploy standard.
- Roles (minimum): Admin, Manager, Teacher, Receptionist. Role-based access control (RBAC) rules:
  - Admin: full access
  - Manager: manage courses, enrollments, invoices, reports
  - Teacher: view assigned courses/sessions, record attendance, see related student contact info
  - Receptionist: manage enrollments, create invoices, view basic student info
- Principle of least privilege: APIs must enforce role checks.

Security & Privacy (minimum)
---------------------------
- Store PII securely. Hash any credentials. Use TLS in transit.
- Data retention policy: keep invoices and attendance records for a configurable retention period (default 7 years for financial records); offer export & deletion for GDPR-like requests.
- Audit log: record who changed critical resources (invoices, enrollments, payments, attendance) with timestamp.

Non-functional requirements
---------------------------
- Performance: API should respond within 500ms for typical CRUD requests under normal load (MVP scale: <100 concurrent users).
- Scalability: design data model so attendance records and invoices are append-only records; allow horizontal scaling of stateless web/API layers.
- Availability & Backups: nightly backups of the database; restore-tested quarterly.
- Observability: structured logs, basic metrics (requests, errors, queue lengths) and error tracking.

Workflows (minimal)
-------------------
- Enroll flow: receptionist creates enrollment -> student status active -> invoices generated (manual or automated) -> payments recorded.
- Attendance flow: teacher opens session -> marks attendance -> attendance records stored and visible on student profile.
- Invoice flow: create invoice -> send (mark sent) -> record payments -> invoice status updates (paid/partial/overdue).

Acceptance criteria (MVP)
------------------------
1. CRM: create/read/update student and contact records; link parent contacts.
2. Courses: create course and schedule sessions; assign a teacher.
3. Enrollment: enroll student into a course and show in student's enrollments.
4. Attendance: mark and persist attendance for a session; teacher role can record attendance.
5. Tasks: create, assign, and change task status.
6. Invoicing: create invoice with line items, compute total, record a payment and reflect invoice status.
7. Reports/Export: export student list and invoice list to CSV.
8. Auth/Z: login, role-based access enforced; Admin user exists on first setup.

Quality & Testing
-----------------
- Unit tests for core domain logic (enrollment rules, invoice totals, attendance status transitions).
- Integration tests for key flows (enroll + invoice + payment, session attendance write/read).
- Minimal end-to-end smoke test that runs through create-student -> enroll -> create-invoice -> record-payment.

Operational & Deployment notes (minimal)
--------------------------------------
- Configuration: environment-based configuration for DB, SMTP, payments (if integrated), and storage.
- Migrations: database migrations must be versioned and reversible when possible.
- Backups: schedule automated DB backups and exportability of financial records.

Extensibility (kept minimal here)
--------------------------------
- Integrations like SMS/email for reminders, payment gateways, or calendar sync are out-of-scope for the MVP but should be designed as pluggable modules.

Governance & Amendments
-----------------------
This constitution defines the minimal requirements for an MVP. Amendments require updating this file with a version line and a short rationale.

Version: 1.0 | Ratified: 2025-11-10
# MTL_App Constitution (Speckit) — Minimal requirements

Purpose
-------
This document defines the minimal, essential structure and requirements for the MTL_App (an English language school management application built with Speckit). It captures scope, core modules, data model primitives, security and non-functional requirements, and clear acceptance criteria for an MVP.

Scope
-----
- Manage students, teachers, courses and enrollments
- Track attendance and lesson-level presence
- Basic CRM for contacts and communications
- Task management for staff workflows
- Invoice generation and payment recording
- Basic reporting (attendance, revenue, outstanding invoices)

Core Modules (high level)
--------------------------
1. CRM: contacts (students, parents, companies), notes, communication log.
2. Courses & Scheduling: course, class/session, teacher assignment, room/online link.
3. Enrollment: enroll/unenroll students into courses and classes.
4. Attendance: mark attendance per session; support statuses (present, late, absent, excused).
5. Tasks: create/assign tasks to staff, with due-date, status, and related entity link.
6. Invoicing & Payments: create invoices, line items, apply payments, track status (draft, sent, paid, overdue).
7. Reporting & Exports: CSV/JSON export of key records and basic dashboards.

Minimal Data Model (entities + minimal fields)
---------------------------------------------
- Student: id, first_name, last_name, email, phone, dob (optional), status (active/inactive), contacts (parent/guardian links)
- Teacher: id, name, email, phone, availability (basic)
- Contact: id, name, role (parent/company/other), email, phone
- Course: id, title, level, price_per_session (optional)
- Session/Class: id, course_id, teacher_id, start_datetime, end_datetime, location
- Enrollment: id, student_id, course_id, status, start_date, end_date
- AttendanceRecord: id, session_id, student_id, status, note, recorded_by, recorded_at
- Task: id, title, description, assigned_to (user), related_entity (polymorphic ref), due_date, status
- Invoice: id, invoice_number, billing_contact_id, items [{description, qty, unit_price}], total, status, issued_date, due_date
- Payment: id, invoice_id, amount, method, date

APIs / Integration contracts (minimal)
------------------------------------
- Provide a RESTful JSON API (or Speckit-standard endpoints) exposing core CRUD for the entities above.
- For each resource: list, get, create, update, delete (soft-delete where appropriate).
- Attendance: endpoint to mark multiple students for a session in one request.
- Invoice: endpoint to generate PDF (or link to pre-generated PDF) and to record payments.
- Export endpoints: CSV/JSON for students, attendances, invoices.

Authentication, Authorization & Roles
------------------------------------
- Authentication: token-based (JWT) for API; session/cookie for web UI per deploy standard.
- Roles (minimum): Admin, Manager, Teacher, Receptionist. Role-based access control (RBAC) rules:
  - Admin: full access
  - Manager: manage courses, enrollments, invoices, reports
  - Teacher: view assigned courses/sessions, record attendance, see related student contact info
  - Receptionist: manage enrollments, create invoices, view basic student info
- Principle of least privilege: APIs must enforce role checks.

Security & Privacy (minimum)
---------------------------
- Store PII securely. Hash any credentials. Use TLS in transit.
- Data retention policy: keep invoices and attendance records for a configurable retention period (default 7 years for financial records); offer export & deletion for GDPR-like requests.
- Audit log: record who changed critical resources (invoices, enrollments, payments, attendance) with timestamp.

Non-functional requirements
---------------------------
- Performance: API should respond within 500ms for typical CRUD requests under normal load (MVP scale: <100 concurrent users).
- Scalability: design data model so attendance records and invoices are append-only records; allow horizontal scaling of stateless web/API layers.
- Availability & Backups: nightly backups of the database; restore-tested quarterly.
- Observability: structured logs, basic metrics (requests, errors, queue lengths) and error tracking.

Workflows (minimal)
-------------------
- Enroll flow: receptionist creates enrollment -> student status active -> invoices generated (manual or automated) -> payments recorded.
- Attendance flow: teacher opens session -> marks attendance -> attendance records stored and visible on student profile.
- Invoice flow: create invoice -> send (mark sent) -> record payments -> invoice status updates (paid/partial/overdue).

Acceptance criteria (MVP)
------------------------
1. CRM: create/read/update student and contact records; link parent contacts.
2. Courses: create course and schedule sessions; assign a teacher.
3. Enrollment: enroll student into a course and show in student's enrollments.
4. Attendance: mark and persist attendance for a session; teacher role can record attendance.
5. Tasks: create, assign, and change task status.
6. Invoicing: create invoice with line items, compute total, record a payment and reflect invoice status.
7. Reports/Export: export student list and invoice list to CSV.
8. Auth/Z: login, role-based access enforced; Admin user exists on first setup.

Quality & Testing
-----------------
- Unit tests for core domain logic (enrollment rules, invoice totals, attendance status transitions).
- Integration tests for key flows (enroll + invoice + payment, session attendance write/read).
- Minimal end-to-end smoke test that runs through create-student -> enroll -> create-invoice -> record-payment.

Operational & Deployment notes (minimal)
--------------------------------------
- Configuration: environment-based configuration for DB, SMTP, payments (if integrated), and storage.
- Migrations: database migrations must be versioned and reversible when possible.
- Backups: schedule automated DB backups and exportability of financial records.

Extensibility (kept minimal here)
--------------------------------
- Integrations like SMS/email for reminders, payment gateways, or calendar sync are out-of-scope for the MVP but should be designed as pluggable modules.

Governance & Amendments
-----------------------
This constitution defines the minimal requirements for an MVP. Amendments require updating this file with a version line and a short rationale.

Version: 1.0 | Ratified: 2025-11-10
# [PROJECT_NAME] Constitution
<!-- Example: Spec Constitution, TaskFlow Constitution, etc. -->

## Core Principles

### [PRINCIPLE_1_NAME]
<!-- Example: I. Library-First -->
[PRINCIPLE_1_DESCRIPTION]
<!-- Example: Every feature starts as a standalone library; Libraries must be self-contained, independently testable, documented; Clear purpose required - no organizational-only libraries -->

### [PRINCIPLE_2_NAME]
<!-- Example: II. CLI Interface -->
[PRINCIPLE_2_DESCRIPTION]
<!-- Example: Every library exposes functionality via CLI; Text in/out protocol: stdin/args → stdout, errors → stderr; Support JSON + human-readable formats -->

### [PRINCIPLE_3_NAME]
<!-- Example: III. Test-First (NON-NEGOTIABLE) -->
[PRINCIPLE_3_DESCRIPTION]
<!-- Example: TDD mandatory: Tests written → User approved → Tests fail → Then implement; Red-Green-Refactor cycle strictly enforced -->

### [PRINCIPLE_4_NAME]
<!-- Example: IV. Integration Testing -->
[PRINCIPLE_4_DESCRIPTION]
<!-- Example: Focus areas requiring integration tests: New library contract tests, Contract changes, Inter-service communication, Shared schemas -->

### [PRINCIPLE_5_NAME]
<!-- Example: V. Observability, VI. Versioning & Breaking Changes, VII. Simplicity -->
[PRINCIPLE_5_DESCRIPTION]
<!-- Example: Text I/O ensures debuggability; Structured logging required; Or: MAJOR.MINOR.BUILD format; Or: Start simple, YAGNI principles -->

## [SECTION_2_NAME]
<!-- Example: Additional Constraints, Security Requirements, Performance Standards, etc. -->

[SECTION_2_CONTENT]
<!-- Example: Technology stack requirements, compliance standards, deployment policies, etc. -->

## [SECTION_3_NAME]
<!-- Example: Development Workflow, Review Process, Quality Gates, etc. -->

[SECTION_3_CONTENT]
<!-- Example: Code review requirements, testing gates, deployment approval process, etc. -->

## Governance
<!-- Example: Constitution supersedes all other practices; Amendments require documentation, approval, migration plan -->

[GOVERNANCE_RULES]
<!-- Example: All PRs/reviews must verify compliance; Complexity must be justified; Use [GUIDANCE_FILE] for runtime development guidance -->

**Version**: [CONSTITUTION_VERSION] | **Ratified**: [RATIFICATION_DATE] | **Last Amended**: [LAST_AMENDED_DATE]
<!-- Example: Version: 2.1.1 | Ratified: 2025-06-13 | Last Amended: 2025-07-16 -->