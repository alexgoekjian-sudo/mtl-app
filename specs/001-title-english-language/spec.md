```markdown
# Feature Specification: English language school management

**Feature Branch**: `001-title-english-language`  
**Created**: 2025-11-11  
**Status**: Draft  
**Input**: User description: "Create an application to manage an English language school: capture leads, run level checks, manage group and 1:1 courses (A1–C1 + Business), scheduling (morning/afternoon onsite, evening online), attendance, mid-course assessments, continuation flow, invoicing/payments via Mollie, basic reporting; users: teachers, coordinators, academic manager, administrative/financial, marketing/business." 

## Execution Flow (main)
1. Lead capture: lead arrives (website form / email / walk-in / referral / phone). Lead prompted to book a free level check.
2. Level check: coordinator/teacher conducts level check (online or onsite via Cal.com) and assigns a level.
3. Placement: coordinator assigns student to a suitable course offering (course run/instance) and creates an enrollment.
4. Payment: coordinator sends a registration email with a Mollie payment link (Mollie is the canonical gateway). Student pays; payment confirmation recorded and invoice marked. Option to send the student a pdf of the invoice (receipt) after payment, and an option to send an invoice before payment (with other details - e.g employer).
5. Attendance & mid-assessment: teacher uses the session attendance sheet each session and records a mid-course level halfway through the course.
6. Continuation: based on mid-level, coordinator sends continuation options via email; if student accepts, create new enrollment and new invoice.
7. Completion: at course end, export eligible students (>=80% attendance) as CSV for certificate generation via Google Sheets (certificate generation is external to the app in MVP).

---

## Confirmations & Assumptions (resolved)
The user confirmed several integration choices and requested best-guess handling for other items. The following are confirmed for the MVP based on your inputs and the sample CSVs provided:

- Payment: Mollie is the canonical payment gateway and will be the authoritative payment provider. The system will process Mollie webhooks, persist the webhook payloads, and record external_payment_refs. Partial payments are supported; refunds are recorded as negative Payment records and are performed/approved by Finance (no automatic prorated refund calculation in MVP).
- Booking: Cal.com is the booking provider for level checks. Level-check bookings arrive via Cal.com webhooks and should be stored as Booking records linked to the Lead/Student record.
- Imports: initial data migration will use the files in `specs/001-title-english-language/imports/` (Trello export -> leads/students, Google Sheets courses export -> course offerings). Sample files provided: `Trello_Export_MTL.csv` and `Courses_from_Google_Sheets.csv` (these were used to derive mapping notes below).
- Communications: Gmail (SMTP/API) is the preferred outbound channel for transactional emails in MVP; outgoing email logs will be recorded.
- Certificates: the app will export a CSV of eligible students (attendance >= 80%) to be processed in Google Sheets for certificate generation (the app will not produce signed PDF certificates in MVP).

Operational assumptions (apply unless you instruct otherwise):
- Deduplication: dedupe by email (primary), then phone (secondary). If neither present or ambiguous, surface records in a manual merge UI with suggested matches.
- Webhook reliability: implement idempotent webhook handlers, persist received events, and support exponential backoff retries (up to 5 attempts) plus an Admin retry queue for manual replay.
- Payments/refunds: store full payment history (including partial payments). Refunds require an Admin action and are recorded as negative Payment entries (no automated accounting adjustments in MVP).
- Withdrawals/transfers: an enrollment drop/transfer is a manual operation recorded on the enrollment; finance handles any refund/credit decisions outside the system in MVP.

---

## Import mapping notes (derived from sample files)
These notes capture the key columns observed in the sample CSVs and how they map to the app entities. They will be used to drive the import manifest and adapter.

- Courses CSV (`Courses_from_Google_Sheets.csv`) — representative columns observed:
  - COURSE_FULL_NAME -> CourseOffering.course_full_name (string; contains level, location, start date, round marker)
  - COURSE_SHORT_NAME -> CourseOffering.course_key / slug (use as canonical external id)
  - START_DATE, END_DATE -> CourseOffering.start_date, end_date (CSV uses M/D/YYYY; normalize to ISO 8601)
  - TIMES -> schedule.time (e.g., "19:00-21:00")
  - DAYS -> schedule.days (comma-separated short codes like "M,W")
  - HRS -> hours_total (string like "24 hours/6 weeks" — parse numeric total hours when possible)
  - PRICE, DISCOUNT-5pc, DISCOUNT-10pc -> price and discount fields (numeric)
  - LOCATION, ONLINE_ONSITE, SCHEDULE_TYPE -> program/location and delivery mode
  - REGISTRATION_LINK -> course external registration URL

- Trello CSV (`Trello_Export_MTL.csv`) — representative columns observed:
  - Course Name -> matches CourseOffering.course_full_name (often includes program and date)
  - First name, Surname, Email address, Phone number -> Lead/Student name and contact fields
  - Country of origin, City of Residence -> Student.address/profile fields
  - Language(s) -> Student.languages (list)
  - Level, PT/OPT result -> student.assessed_level / notes
  - Description of completed courses and other notes -> Lead.activity_notes / student.previous_courses
  - LC Teacher -> teacher name (used to match or create Teacher users if needed)

Mapping rules (import adapter will implement):
  - Deduplication: primary key = email; fallback = phone; fallback = (first+surname + course name) — if ambiguous, mark record for manual review.
  - Normalize phone numbers to E.164 when a country code is present; otherwise normalize to digits-only and flag probable-country from CourseOffering.location where possible.
  - Dates: parse M/D/YYYY (Course CSV) and store in ISO 8601. Where dates are ambiguous, report for manual review.
  - Course linking: map Trello "Course Name" to the course_key from Courses CSV (best-effort matching by COURSE_SHORT_NAME or slug of COURSE_FULL_NAME); unmatched course names will be reported for manual resolution.

These mapping notes will be encoded into `manifest.trello.json` and `manifest.courses.json` in the imports folder and used by the import adapter's transform pipeline.

---

## User Scenarios & Testing (mandatory)

### Primary User Stories
- As a Coordinator, I want to capture a lead, schedule a free level check (Cal.com), and register the student in an appropriate course so that the student receives course information and a Mollie payment link.  
- As a Teacher, I want to open my session's attendance sheet, mark attendance for students, and enter a mid-course level so coordinators can invite continuations.  
- As an Administrative/Financial user, I want to create invoices and record payments so that student billing is tracked and receipts are generated.  
- As a Marketing/Business manager, I want reports of enrollments, revenue and retention so I can measure performance.  

### Acceptance Scenarios
1. Given a new incoming lead (website form), when the lead is created, then the lead record exists with contact data and an email prompting level check booking is visible or queued for sending via Gmail.
2. Given a scheduled Cal.com level check, when the teacher completes the check and assigns a level, then the lead can be converted to a student record and be assigned to matching course offerings.
3. Given a student assigned to a course offering, when an invoice is generated and a Mollie payment link is sent, then upon payment the invoice status becomes Paid and a payment record exists (including Mollie external_reference).
4. Given an active course session, when the teacher records attendance for a session, then attendance records for each student for that session are stored and viewable on the student profile and course roster.
5. Given the mid-course point, when the teacher saves a mid-level and comments, then coordinators can send continuation recommendations to the student list for that course.
6. Given course completion, when student attendance >= 80% then the student appears in the CSV export for certificates (for external generation via Google Sheets).

---

## Requirements (mandatory)

### Functional Requirements
- FR-001: System MUST record leads with source (website/email/walk-in/referral), contact details (name, email, phone, country of origin, languages spoken) and an activity log.  
- FR-002: System MUST allow scheduling of free level checks and record outcomes (assigned level, date, teacher, notes). Integration: Cal.com webhooks will be processed to create/confirm bookings.  
- FR-003: System MUST support conversion of a lead to a student record and persist student profile data (name, email, phone, DOB optional, country, initial level, previous courses, activity notes).  
- FR-004: System MUST represent course offerings (course runs) with attributes: course_full_name, level, program/location, start_date, end_date, total_hours, schedule (days/time), price, type (morning/afternoon/online/intensive), capacity.  
- FR-005: System MUST allow enrollments linking a student to a specific course offering and track enrollment status (registered, active, cancelled, completed).  
- FR-006: System MUST provide a session-level attendance sheet for each scheduled class session and allow teachers to mark statuses: present, late, absent, excused; records must be timestamped and attributed to the recording user.  
- FR-007: System MUST allow teachers to record a mid-course level and freeform notes for each student.  
- FR-008: System MUST allow coordinators to generate and send course continuation recommendations to enrolled students (email content template + list export).  
- FR-009: System MUST generate invoices with line items, compute totals, record issued_date and due_date, and expose invoice status (draft, sent, paid, overdue).  
- FR-010: System MUST record payments against invoices with amount, method (Mollie), external_reference (webhook id), and date; support partial payments and refunds recorded as negative payments.  
- FR-011: System MUST export completion lists (CSV) for students meeting the completion rule (>=80% attendance) to be used for certificate generation in Google Sheets.  
- FR-012: System MUST provide CSV/JSON export for students, enrollments, attendance, invoices and payments.  
- FR-013: System MUST provide role-based access control for staff roles: Admin, Manager, Teacher, Receptionist/Finance, Marketing.  
- FR-014: System MUST audit changes to critical resources (invoices, payments, enrollments, attendance) with user and timestamp.  

### Non-functional Requirements (minimal)
- NFR-001: The system SHOULD allow backups and export of financial records for at least 7 years.  
- NFR-002: The system SHOULD support idempotent webhook processing and replay; implement exponential backoff with up to 5 retries and an admin retry queue for failed events.  
- NFR-003: UI performance: typical CRUD actions for single-record operations should be responsive for typical load (MVP scale). Specific SLA can be defined later if needed.  

## Key Entities (mandatory when data involved)
- Lead: {id, source, name, email, phone, country, languages, activity_notes[], created_at}  
- Student: {id, lead_id (optional), name, email, phone, country, initial_level, current_level, enrollment_history[], notes[]}  
- Teacher (User): {id, name, email, role=Teacher, availability[], assigned_sessions[]}  
- CourseOffering: {id, course_full_name, program/location, type, level, start_date, end_date, hours_total, schedule (days+times), price, capacity}. Should also included agreed teacher hourly rate and classroom cost.
- Session: {id, course_offering_id, date, start_time, end_time, teacher_id, location}  
- Enrollment: {id, student_id, course_offering_id, status, enrolled_at, dropped_at}  
- AttendanceRecord: {id, session_id, student_id, status, note, recorded_by, recorded_at}  
- Invoice: {id, invoice_number, billing_contact_id, items[], total, status, issued_date, due_date, external_payment_refs[]}  
- Payment: {id, invoice_id, amount, method, external_reference, date, status}  
- CertificateExportRow: {student_id, student_name, email, course_offering_id, course_full_name, attendance_percent, issued_date (empty until generated)}  
- Task/Activity: {id, title, body, assigned_to, related_entity_ref, due_date, status}

---

## Review & Acceptance Checklist

### Content Quality
- [ ] No low-level implementation details (APIs, DB schemas, frameworks) — spec focuses on WHAT and WHY.  
- [ ] Mandatory sections completed (User Scenarios, Requirements, Entities, Acceptance).  

### Requirement Completeness
- [ ] All ambiguous items resolved or flagged with [NEEDS CLARIFICATION]. (Most resolved per user confirmations; remaining items listed below.)  
- [ ] Requirements are testable and have acceptance scenarios.  
- [ ] Roles and permission needs are listed.  

### Deployment/Operations
- [ ] Integration points (Mollie, Cal.com, Gmail) confirmed and failover/retry expectations defined.

---

## Execution Status
- [x] User description parsed
- [x] Key concepts extracted
- [x] Integrations confirmed (Mollie, Cal.com, Gmail)
- [x] Sample imports received and schema inferred (`Trello_Export_MTL.csv`, `Courses_from_Google_Sheets.csv`)
- [x] Import mapping notes drafted (see above)
- [x] User scenarios defined
- [x] Requirements generated
- [x] Entities identified
- [ ] Review checklist passed

---

### Remaining clarifications (small)
- These items were handled with best-guess assumptions per the user's instruction; if any are incorrect please say which and I'll adjust the spec:

- Deduplication rule: dedupe by email, then phone.  
- Refunds: manual refunds recorded as negative payments; no automated prorations in MVP.  
- Webhook retry policy: exponential backoff, up to 5 retries + admin retry queue.  
- Email: Gmail SMTP/API used for outgoing emails in MVP; we will log outgoing emails.  

### Next steps (suggested)
1. Finalise the import mapping manifests (`manifest.trello.json`, `manifest.courses.json`) and the import adapter.  
2. Convert Key Entities into Speckit model/spec files and create initial acceptance tests for the primary user flows (create-student → enroll → invoice → payment → attendance).  
3. After mapping is approved, run a dry-run import to produce normalized JSON output and a validation report.

``` 
```markdown
(#) Feature Specification: English language school management

**Feature Branch**: `001-title-english-language`  
**Created**: 2025-11-11  
**Status**: Draft  
**Input**: User description: "Create an application to manage an English language school: capture leads, run level checks, manage group and 1:1 courses (A1–C1 + Business), scheduling (morning/afternoon onsite, evening online), attendance, mid-course assessments, continuation flow, invoicing/payments via Mollie, basic reporting; users: teachers, coordinators, academic manager, administrative/financial, marketing/business." 

## Execution Flow (main)
1. Lead capture: lead arrives (website form / email / walk-in / referral / phone). Lead prompted to book a free level check.
2. Level check: coordinator/teacher conducts level check (online or onsite via Cal.com) and assigns a level.
3. Placement: coordinator assigns student to a suitable course offering (course run/instance) and creates an enrollment.
4. Payment: coordinator sends a registration email with a Mollie payment link (Mollie is the canonical gateway). Student pays; payment confirmation recorded and invoice marked. Option to send the student a pdf of the invoice (receipt) after payment, and an option to send an invoice before payment (with other details - e.g employer)
5. Attendance & mid-assessment: teacher uses the session attendance sheet each session and records a mid-course level halfway through the course. All levels are pre-defined but can be altered by the course-co-ordinator (A1, A1+, A2 Elementary -, A2 Elementary, A2 Elementary+, A2 Pre-Intermediate-, A2 Pre-Intermediate, etc.)
6. Continuation: based on mid-level, coordinator sends continuation options via email; if student accepts, create new enrollment and new invoice.
7. Completion: at course end, export eligible students (>=80% attendance) as CSV for certificate generation via Google Sheets (certificate generation is external to the app in MVP).

---

## Confirmations & Assumptions (resolved)
The user confirmed several integration choices and requested best-guess handling for other items. The following are confirmed or assumed for the MVP:

- Payment: Mollie is the only payment gateway and will be the authoritative payment provider. The system must process Mollie webhooks and record external_payment_refs. Partial payments are supported; refunds are manual actions initiated by finance (no automated prorated refund calculation in MVP).
- Booking: Cal.com is the booking provider for level checks (bookings arrive via webhook). The app will accept Cal.com webhook events and store booking metadata.
- Imports: initial data migration from Trello and Google Sheets will be supported; the user will provide sample exports. We'll build import adapters that map Trello cards -> Lead/Student records and Sheet rows -> Course/Session records.
- Communications: Gmail remains the preferred channel for now. MVP will allow sending emails via a configured Gmail account (SMTP or API) and store outgoing email logs; future inbuilt email module may be added later.
- Certificates: the app will export student/course completion data (CSV) for students meeting the 80% attendance rule; certificate generation will be performed in Google Sheets per user's workflow (app will not generate PDF certificates in MVP).

Best-guess operational assumptions (apply unless user overrides):
- Duplicate lead deduplication uses primary key: email (if present), then phone. A merge UI will be available for manual resolution.
- Webhook reliability: implement idempotent webhook handlers and persist webhook events; retry policy: exponential backoff with up to 5 retries; provide an admin retry queue for failed/older webhooks.
- Partial payments and refunds: system stores payment status and supports marking partial payments; refunds are recorded as negative payment records and require an Admin role to execute and record (no automatic accounting entries beyond storing refund records in MVP).
- Withdrawals: student withdrawal is a manual action recorded on the enrollment; no automatic refund/proration is performed by default (finance will handle refunds manually). If needed, we can add prorated refund rules later.
- Initial admin user: on first setup, create an Admin user (provisioned via setup script or seed) so staff can log in and configure integrations.
- Returning students are offered discounts (5% is standard), Students who are referred from third parties also will benefit from refunds. And there are ad-hoc situations where a manual refund percentage should be calculated.


## User Scenarios & Testing (mandatory)

### Primary User Stories
- As a Coordinator, I want to capture a lead, schedule a free level check (Cal.com), and register the student in an appropriate course so that the student receives course information and a Mollie payment link.  
- As a Teacher, I want to open my session's attendance sheet, mark attendance for students, and enter a mid-course level so coordinators can invite continuations.  
- As an Administrative/Financial user, I want to create invoices and record payments (Mollie) so that student billing is tracked and receipts are generated.  
- As a Marketing/Business manager, I want reports of enrollments, revenue and retention so I can measure performance.  
- The Business manager should be able to assess whether a class can go ahead. This is based on different information elements being brought together in a dashboard. The number of students registered and paid in a course, the agreed teacher rate and hours of the course, and the situation for the month as a whole, e.g. if many course are oversubscribed and in prfit then it is possible to have some courses run at a loss, where loss means - amount paid for course by registered students minus teacher rate, minus classroom hire rate, minus administration costs. These latter elements need to be amendable.
- In most group courses the book is included. In other cases the student will pay for their books seperately. Details to be provided.

### Acceptance Scenarios
1. Given a new incoming lead (website form), when the lead is created, then the lead record exists with contact data and an email prompting level check booking is visible or queued for sending via Gmail.
2. Given a scheduled Cal.com level check, when the teacher completes the check and assigns a level, then the lead can be converted to a student record and be assigned to matching course offerings.
3. Given a student assigned to a course offering, when an invoice is generated and a Mollie payment link is sent, then upon payment the invoice status becomes Paid and a payment record exists (including Mollie external_reference).
4. Given an active course session, when the teacher records attendance for a session, then attendance records for each student for that session are stored and viewable on the student profile and course roster.
5. Given the mid-course point, when the teacher saves a mid-level and comments, then coordinators can send continuation recommendations to the student list for that course.
6. Given course completion, when student attendance >= 80% then the student appears in the CSV export for certificates (for external generation via Google Sheets).

### Edge Cases (resolved / assumed)
- Duplicate leads: dedupe by email; if no email, dedupe by phone; provide manual merge UI.  
- Partial payments/refunds: support partial payments; refunds recorded as negative payments and require Admin action.  
- Withdrawals mid-course: marked on enrollment; refunds handled manually by finance.  
- Missed webhook events (payment or booking): webhook events persisted; admin can replay events from a retry queue; handlers are idempotent.
- Some students will participate in the first lesson of a class as a trial period, because they are considered border cases in tersm of level. If it is deemed that they are in the wrong class, they will be transferred to another class.

---

## Requirements (mandatory)

### Functional Requirements
- FR-001: System MUST record leads with source (website/email/walk-in/referral), contact details (name, email, phone, country of origin, languages spoken) and an activity log.  
- FR-002: System MUST allow scheduling of free level checks and record outcomes (assigned level, date, teacher, notes). Integration: Cal.com webhooks will be processed to create/confirm bookings.  
- FR-003: System MUST support conversion of a lead to a student record and persist student profile data (name, email, phone, DOB optional, country, initial level, previous courses, activity notes).  
- FR-004: System MUST represent course offerings (course runs) with attributes: course_full_name, level, program/location, start_date, end_date, total_hours, schedule (days/time), price, type (morning/afternoon/online/intensive), capacity.  
- FR-005: System MUST allow enrollments linking a student to a specific course offering and track enrollment status (registered, active, cancelled, completed).  
- FR-006: System MUST provide a session-level attendance sheet for each scheduled class session and allow teachers to mark statuses: present, late, absent, excused; records must be timestamped and attributed to the recording user.  
- FR-007: System MUST allow teachers to record a mid-course level and freeform notes for each student.  
- FR-008: System MUST allow coordinators to generate and send course continuation recommendations to enrolled students (email content template + list export).  
- FR-009: System MUST generate invoices with line items, compute totals, record issued_date and due_date, and expose invoice status (draft, sent, paid, overdue).  
- FR-010: System MUST record payments against invoices with amount, method (Mollie), external_reference (webhook id), and date; support partial payments and refunds recorded as negative payments.  
- FR-011: System MUST export completion lists (CSV) for students meeting the completion rule (>=80% attendance) to be used for certificate generation in Google Sheets.  
- FR-012: System MUST provide CSV/JSON export for students, enrollments, attendance, invoices and payments.  
- FR-013: System MUST provide role-based access control for staff roles: Admin, Manager, Teacher, Receptionist/Finance, Marketing.  
- FR-014: System MUST audit changes to critical resources (invoices, payments, enrollments, attendance) with user and timestamp.  
- FR-015: System MUST allow for a student activity log to be edited. This allows for different co-ordinators to be aware of the special situations and particular requirements of the student.

### Non-functional Requirements (minimal)
- NFR-001: The system SHOULD allow backups and export of financial records for at least 7 years.  
- NFR-002: The system SHOULD support idempotent webhook processing and replay; implement exponential backoff with up to 5 retries and an admin retry queue for failed events.  
- NFR-003: UI performance: typical CRUD actions for single-record operations should be responsive for typical load (MVP scale). Specific SLA can be defined later if needed.  

## Key Entities (mandatory when data involved)
- Lead: {id, source, name, email, phone, country, languages, activity_notes[], created_at}  
- Student: {id, lead_id (optional), name, email, phone, country, initial_level, current_level, enrollment_history[], notes[]}  
- Teacher (User): {id, name, email, role=Teacher, availability[], assigned_sessions[]}  
- CourseOffering: {id, course_full_name, program/location, type, level, start_date, end_date, hours_total, schedule (days+times), price, capacity}. Should also included agreed teacher hourly rate and classroom cost.
- Session: {id, course_offering_id, date, start_time, end_time, teacher_id, location}  
- Enrollment: {id, student_id, course_offering_id, status, enrolled_at, dropped_at}  
- AttendanceRecord: {id, session_id, student_id, status, note, recorded_by, recorded_at}  
- Invoice: {id, invoice_number, billing_contact_id, items[], total, status, issued_date, due_date, external_payment_refs[]}  
- Payment: {id, invoice_id, amount, method, external_reference, date, status}  
- CertificateExportRow: {student_id, student_name, email, course_offering_id, course_full_name, attendance_percent, issued_date (empty until generated)}  
- Task/Activity: {id, title, body, assigned_to, related_entity_ref, due_date, status}

---

## Review & Acceptance Checklist

### Content Quality
- [ ] No low-level implementation details (APIs, DB schemas, frameworks) — spec focuses on WHAT and WHY.  
- [ ] Mandatory sections completed (User Scenarios, Requirements, Entities, Acceptance).  

### Requirement Completeness
- [ ] All ambiguous items resolved or flagged with [NEEDS CLARIFICATION]. (Most resolved per user confirmations; remaining items listed below.)  
- [ ] Requirements are testable and have acceptance scenarios.  
- [ ] Roles and permission needs are listed.  

### Deployment/Operations
- [ ] Integration points (Mollie, Cal.com, Gmail) confirmed and failover/retry expectations defined.

---

## Execution Status
- [x] User description parsed
- [x] Key concepts extracted
- [x] Ambiguities marked and resolved where user confirmed
- [x] User scenarios defined
- [x] Requirements generated
- [x] Entities identified
- [ ] Review checklist passed

---

### Remaining clarifications (small)
These items were handled with best-guess assumptions per the user's instruction; if any are incorrect please say which and I'll adjust the spec:

- Deduplication rule: dedupe by email, then phone.  
- Refunds: manual refunds recorded as negative payments; no automated prorations in MVP.  
- Webhook retry policy: exponential backoff, up to 5 retries + admin retry queue.  
- Email: Gmail SMTP/API used for outgoing emails in MVP; we will log outgoing emails.  

### Next steps (suggested)
1. Provide sample Trello export (JSON/CSV) and Google Sheets exports so I can draft the import mapping and adapters.  
2. I will design the Trello/Sheets import plan and mapping (I can scaffold the import scripts and a migration checklist).  
3. After import plan is approved, convert Key Entities into Speckit model/spec files and generate initial acceptance tests for the primary user flows (create-student → enroll → invoice → payment → attendance).  

*** End Patch
```
(#) Feature Specification: English language school management

**Feature Branch**: `001-title-english-language`  
**Created**: 2025-11-11  
**Status**: Draft  
**Input**: User description: "Create an application to manage an English language school: capture leads, run level checks, manage group and 1:1 courses (A1–C1 + Business), scheduling (morning/afternoon onsite, evening online), attendance, mid-course assessments, continuation flow, invoicing/payments via Mollie, basic reporting; users: teachers, coordinators, academic manager, administrative/financial, marketing/business." 

## Execution Flow (main)
1. Lead capture: lead arrives (website form / email / walk-in / referral). Lead prompted to book a free level check.
2. Level check: coordinator/teacher conducts level check (online or onsite) and assigns a level.
3. Placement: coordinator assigns student to a suitable course offering (course run/instance) and creates an enrollment.
4. Payment: coordinator sends a registration email with a payment link (Mollie webhook in current setup). Student pays; payment confirmation recorded and invoice marked.
5. Attendance & mid-assessment: teacher uses the session attendance sheet each session and records a mid-course level halfway through the course.
6. Continuation: based on mid-level, coordinator sends continuation options; if student accepts, create new enrollment and new invoice.
7. Completion: at course end, create certificate for students with >=80% attendance.

---

## ⚠️ [NEEDS CLARIFICATION]
- Payment provider: user mentions Mollie — confirm that Mollie is the authoritative payment gateway and whether other payment methods are required (card, bank transfer, manual).  
- Booking provider: user mentions Cal.com/Calendly — confirm canonical booking provider and whether booking data must be imported or integrated in real-time.  
- Import of historical data: does the initial rollout require import of existing Trello cards/Google Sheets? If yes, provide sample export format.  
- Notifications: confirm preferred channel for automatic emails (currently Gmail); confirm whether transactional email provider is required.  
- Certificate format and delivery: PDF generation? include attachments to email?  

## User Scenarios & Testing (mandatory)

### Primary User Stories
- As a Coordinator, I want to capture a lead, schedule a free level check, and register the student in an appropriate course so that the student receives course information and a payment link.  
- As a Teacher, I want to open my session's attendance sheet, mark attendance for students, and enter a mid-course level so coordinators can invite continuations.  
- As an Administrative/Financial user, I want to create invoices and record payments so that student billing is tracked and receipts are generated.  
- As a Marketing/Business manager, I want reports of enrollments, revenue and retention so I can measure performance.  

### Acceptance Scenarios
1. Given a new incoming lead (website form), when the lead is created, then the lead record exists with contact data and an email prompting level check booking is sent (or visible for the coordinator to send).  
2. Given a scheduled level check, when the teacher completes the check and assigns a level, then the lead can be converted to a student record and be assigned to matching course offerings.  
3. Given a student assigned to a course offering, when an invoice is generated and payment link is sent, then upon payment the invoice status becomes Paid and a payment record exists.  
4. Given an active course session, when the teacher records attendance for a session, then attendance records for each student for that session are stored and viewable on the student profile and course roster.  
5. Given the mid-course point, when the teacher saves a mid-level and comments, then coordinators can send continuation recommendations to the student list for that course.  
6. Given course completion, when student attendance >= 80% then a certificate is generated for that student and available to download/email.  

### Edge Cases
- Duplicate leads (same email/phone): define merge / dedupe rules. [NEEDS CLARIFICATION]
- Partial payments/refunds: how to handle partial payments and refunds against invoices.  
- Students withdrawing mid-course: should prorated refunds or credits be issued? [NEEDS CLARIFICATION]  
- Missed webhook events (payment or booking) — how are retries/notifications handled? [NEEDS CLARIFICATION]

---

## Requirements (mandatory)

### Functional Requirements
- FR-001: System MUST record leads with source (website/email/walk-in/referral), contact details (name, email, phone, country of origin, languages spoken) and an activity log.  
- FR-002: System MUST allow scheduling of free level checks and record outcomes (assigned level, date, teacher, notes). [NEEDS CLARIFICATION: confirm booking provider & webhook behavior]
- FR-003: System MUST support conversion of a lead to a student record and persist student profile data (name, email, phone, DOB optional, country, initial level, previous courses, activity notes).  
- FR-004: System MUST represent recurring course offerings (course runs) with attributes: title, level, program/location, start_date, end_date, total_hours, schedule (days/time), price, type (morning/afternoon/online/intensive).  
- FR-005: System MUST allow enrollments linking a student to a specific course offering and track enrollment status (registered, active, cancelled, completed).  
- FR-006: System MUST provide a session-level attendance sheet for each scheduled class session and allow teachers to mark statuses: present, late, absent, excused; records must be timestamped and attributed to the recording user.  
- FR-007: System MUST allow teachers to record a mid-course level and freeform notes for each student.  
- FR-008: System MUST allow coordinators to generate and send course continuation recommendations to enrolled students (email content template + list export).  
- FR-009: System MUST generate invoices with line items, compute totals, record issued_date and due_date, and expose invoice status (draft, sent, paid, overdue).  
- FR-010: System MUST record payments against invoices with amount, method, external_reference (webhook id), and date.  
- FR-011: System MUST generate a certificate for students meeting the completion rule (>=80% attendance) that is viewable/downloadable and can be emailed. [NEEDS CLARIFICATION: certificate format & signatory]  
- FR-012: System MUST provide CSV/JSON export for students, enrollments, attendance, invoices and payments.  
- FR-013: System MUST provide role-based access control for staff roles: Admin, Manager, Teacher, Receptionist/Finance, Marketing.  
- FR-014: System MUST audit changes to critical resources (invoices, payments, enrollments, attendance) with user and timestamp.  

### Non-functional Requirements (minimal)
- NFR-001: The system SHOULD allow backups and export of financial records for at least 7 years.  
- NFR-002: The system SHOULD allow recovery of missed external webhook events (idempotency and replay). [NEEDS CLARIFICATION: retry policy]  
- NFR-003: UI performance: typical CRUD actions for single-record operations should be responsive for typical load (no specific SLA required for the spec; to be defined). [NEEDS CLARIFICATION: performance SLA if required]  

## Key Entities (mandatory when data involved)
- Lead: {id, source, name, email, phone, country, languages, activity_notes[], created_at}  
- Student: {id, lead_id (optional), name, email, phone, country, initial_level, current_level, enrollment_history[], notes[]}  
- Teacher (User): {id, name, email, role=Teacher, availability[], assigned_sessions[]}  
- CourseOffering: {id, course_full_name, program/location, type, level, start_date, end_date, hours_total, schedule (days+times), price, capacity}  
- Session: {id, course_offering_id, date, start_time, end_time, teacher_id, location}  
- Enrollment: {id, student_id, course_offering_id, status, enrolled_at, dropped_at}  
- AttendanceRecord: {id, session_id, student_id, status, note, recorded_by, recorded_at}  
- Invoice: {id, invoice_number, billing_contact_id, items[], total, status, issued_date, due_date, external_payment_refs[]}  
- Payment: {id, invoice_id, amount, method, external_reference, date, status}  
- Certificate: {id, student_id, course_offering_id, issued_date, template_ref}  
- Task/Activity: {id, title, body, assigned_to, related_entity_ref, due_date, status}

---

## Review & Acceptance Checklist

### Content Quality
- [ ] No low-level implementation details (APIs, DB schemas, frameworks) — spec focuses on WHAT and WHY.  
- [ ] Mandatory sections completed (User Scenarios, Requirements, Entities, Acceptance).  

### Requirement Completeness
- [ ] All ambiguous items resolved or flagged with [NEEDS CLARIFICATION].  
- [ ] Requirements are testable and have acceptance scenarios.  
- [ ] Roles and permission needs are listed.  

### Deployment/Operations
- [ ] Integration points (payment, booking, email) confirmed and failover/retry expectations defined.  

---

## Execution Status
- [x] User description parsed
- [x] Key concepts extracted
- [x] Ambiguities marked (see above)
- [x] User scenarios defined
- [x] Requirements generated
- [x] Entities identified
- [ ] Review checklist passed

---

### Next steps (suggested)
1. Confirm the external integration specifics: Mollie webhooks, booking provider, and email sending method.  
2. Decide whether to import existing Trello/Sheets data for initial migration; if yes, provide a sample export.  
3. Once clarifications are answered, convert Key Entities into concrete Speckit spec files and create initial acceptance tests for the primary user flows.

