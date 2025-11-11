# Data model: core entities and minimal DB guidance

This file extracts the canonical entities from the feature spec and gives simple DB column suggestions and constraints for the MVP (MySQL).

Entities (core):

- Lead
  - id: bigint PK
  - source: varchar(64) NULL
  - first_name: varchar(128)
  - last_name: varchar(128)
  - email: varchar(255) NULL UNIQUE
  - phone: varchar(48) NULL
  - country: varchar(64) NULL
  - languages: varchar(255) NULL -- comma separated
  - activity_notes: text NULL
  - created_at, updated_at

- Student
  - id: bigint PK
  - lead_id: bigint NULL FK -> lead.id
  - first_name, last_name
  - email varchar(255) NULL UNIQUE
  - phone varchar(48) NULL
  - initial_level varchar(32) NULL
  - current_level varchar(32) NULL
  - profile_notes text NULL
  - created_at, updated_at

- Teacher (User)
  - id, name, email UNIQUE, role ENUM('teacher','manager','admin','reception')
  - phone, availability JSON NULL

- CourseOffering
  - id: bigint PK
  - course_key: varchar(64) UNIQUE -- COURSE_SHORT_NAME
  - course_full_name: varchar(255)
  - level: varchar(32)
  - program varchar(128) NULL
  - start_date date NULL
  - end_date date NULL
  - hours_total int NULL
  - schedule JSON NULL -- {days:["M","W"], time:"19:00-21:00"}
  - price decimal(10,2) NULL
  - capacity int NULL
  - location varchar(128) NULL
  - online boolean DEFAULT false

- Session
  - id, course_offering_id FK
  - date date
  - start_time time, end_time time
  - teacher_id FK

- Enrollment
  - id, student_id FK, course_offering_id FK
  - status ENUM('registered','active','cancelled','completed')
  - enrolled_at datetime

- AttendanceRecord
  - id, session_id FK, student_id FK
  - status ENUM('present','late','absent','excused')
  - note text NULL
  - recorded_by FK user
  - recorded_at datetime

- Invoice
  - id, invoice_number varchar(64) UNIQUE
  - billing_contact_id FK
  - items JSON
  - total decimal(10,2)
  - status ENUM('draft','sent','paid','overdue')
  - issued_date date, due_date date

- Payment
  - id, invoice_id FK
  - amount decimal(10,2)
  - method varchar(32) -- 'mollie','cash','bank'
  - external_reference varchar(255) NULL
  - recorded_at datetime

Indices & constraints (recommendations):
- UNIQUE(email) for students/leads when present; when email missing rely on phone and manual merge workflows.
- Index course_offering.start_date, course_offering.course_key
- Index attendance by session_id for fast roster queries

Dedupe rules (engineered into import / API):
- Primary dedupe by email (case-insensitive normalized), fallback by phone digits-only.
- If duplicates detected, mark for manual merge in Retool with suggested canonical record.

Notes:
- Use JSON columns for schedule/availability for flexibility in MVP.
- Keep migrations idempotent and reversible; seed an Admin user on first-run.
