# Requirements Analysis: Lead Management & Enrollment Workflow

**Date**: November 21, 2025  
**Purpose**: Analyze existing database and specifications against user requirements for lead tracking, enrollment workflow, and historical data management

---

## Executive Summary

### Requirements Overview
1. **Lead Source Tracking**: Track lead origin (reference) and marketing source
2. **Enrollment Workflow**: Pending → Level Check → Registration → Payment → Confirmed
3. **Lead-to-Student Data**: Determine optimal data model (duplication vs. shared contact table)
4. **Activity Timeline**: Timestamped notes that follow lead through to student status
5. **Course History**: Track all courses (current + historical) and support old data import

### Current Status
✅ **IMPLEMENTED**: Basic lead/student structure, enrollment tracking, payment integration  
⚠️ **PARTIALLY IMPLEMENTED**: Source tracking exists but lacks granularity  
❌ **MISSING**: Detailed source attribution, enrollment status "pending", activity timeline, historical course flag

---

## Requirement 1: Lead Source Tracking

### User Requirement
> "A lead will be added to the database in the following way - either via the website or manually. There should be a property/column, called reference, which shows where the lead came from, e.g. online form, level check, phone call, walk-in, referral. And then another property/column which will be manually input to specify the source of the lead - google, facebook, ai, etc."

### Database Analysis

**Current `leads` table structure:**
```sql
CREATE TABLE `leads` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` varchar(255) DEFAULT NULL,  -- ⚠️ EXISTS but single field
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `languages` varchar(255) DEFAULT NULL,
  `activity_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

**Specification Check (spec.md line 107):**
```markdown
- FR-001: System MUST record leads with source (website/email/walk-in/referral), 
  contact details (name, email, phone, country of origin, languages spoken) 
  and an activity log.
```

### Gap Analysis
- ✅ **PARTIAL**: Single `source` column exists
- ❌ **MISSING**: Separate `reference` column for lead type (online form, level check, phone call, walk-in, referral)
- ❌ **MISSING**: Separate `source_detail` or `marketing_source` column for attribution (google, facebook, ai, etc.)

### Recommendation
**Add two columns to `leads` table:**

```sql
ALTER TABLE `leads` 
  ADD COLUMN `reference` ENUM('online_form', 'level_check', 'phone_call', 'walk_in', 'referral', 'other') DEFAULT NULL AFTER `source`,
  ADD COLUMN `source_detail` VARCHAR(255) DEFAULT NULL COMMENT 'Marketing source: google, facebook, ai, instagram, etc.' AFTER `reference`;
```

**Update spec.md FR-001:**
```markdown
- FR-001: System MUST record leads with:
  - reference (lead type): online_form, level_check, phone_call, walk_in, referral
  - source_detail (marketing attribution): google, facebook, ai, instagram, etc.
  - contact details (name, email, phone, country of origin, languages spoken)
  - activity log (timestamped notes)
```

---

## Requirement 2: Enrollment Workflow & Payment Status

### User Requirement
> "Once a lead becomes a student he or she enters the enrollment workflow - first he does a level check, unless he's already done one, he gets sent a registration email with a registration link based on the level assigned after the level check, and at the same time he gets enrolled into a course. His/her enrollment status is first pending, but once the payment confirmation is received from mollie his/her status gets confirmed as being a paid student enrolled in that course."

### Database Analysis

**Current `enrollments` table:**
```sql
CREATE TABLE `enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `course_offering_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('registered','active','cancelled','completed') NOT NULL DEFAULT 'registered',
  `mid_course_level` varchar(255) DEFAULT NULL,
  `mid_course_notes` text DEFAULT NULL,
  `is_trial` tinyint(1) DEFAULT 0,
  `enrolled_at` datetime DEFAULT NULL,
  `dropped_at` timestamp NULL DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

**Current `payments` table:**
```sql
CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','refunded','failed') DEFAULT 'pending',
  `is_refund` tinyint(1) DEFAULT 0,
  `method` varchar(255) DEFAULT NULL,
  `external_reference` varchar(255) DEFAULT NULL,  -- Mollie reference
  `recorded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

**Current `bookings` table (level checks):**
```sql
CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `booking_provider` varchar(255) DEFAULT 'cal.com',
  `external_booking_id` varchar(255) DEFAULT NULL,
  `booking_type` varchar(255) DEFAULT 'level_check',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `assigned_teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_level` varchar(255) DEFAULT NULL,
  `teacher_notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `webhook_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

**Specification Check (spec.md lines 110-112):**
```markdown
- FR-005: System MUST allow enrollments linking a student to a specific course 
  offering and track enrollment status (registered, active, cancelled, completed).
- FR-010: System MUST record payments against invoices with amount, method (Mollie), 
  external_reference (webhook id), and date; support partial payments and refunds 
  recorded as negative payments.
```

### Gap Analysis
- ✅ **IMPLEMENTED**: Level check booking system with Cal.com integration
- ✅ **IMPLEMENTED**: Payment tracking with Mollie integration
- ✅ **IMPLEMENTED**: Enrollment status tracking
- ❌ **MISSING**: `pending` status in enrollment enum (workflow starts at `registered`)
- ❌ **MISSING**: Explicit link between payment status and enrollment status
- ⚠️ **UNCLEAR**: Automatic enrollment status update when payment confirmed

### Recommendation

**Option A: Add `pending` status to enrollments (RECOMMENDED)**
```sql
ALTER TABLE `enrollments` 
  MODIFY `status` ENUM('pending', 'registered', 'active', 'cancelled', 'completed') 
  NOT NULL DEFAULT 'pending';
```

**Workflow:**
1. Lead completes level check → `bookings.assigned_level` set
2. Coordinator creates enrollment → `enrollments.status = 'pending'`
3. System generates invoice and sends Mollie payment link
4. Mollie webhook received with payment confirmation → `payments.status = 'completed'`
5. **Automatic trigger**: Payment completion → `enrollments.status = 'registered'` or `'active'`

**Option B: Use invoice status as enrollment gate**
- Keep current enrollment statuses
- Enrollment is `registered` when created
- Only becomes `active` when associated invoice is `paid`
- Simpler but less explicit

**Recommendation**: **Option A** - More explicit and matches user's mental model

**Update spec.md FR-005:**
```markdown
- FR-005: System MUST allow enrollments linking a student to a specific course 
  offering and track enrollment status:
  - pending: Enrollment created, awaiting payment
  - registered: Payment received, student registered for course
  - active: Course has started, student actively participating
  - cancelled: Student withdrew or was removed
  - completed: Student finished the course
```

**Add new FR-017:**
```markdown
- FR-017: System MUST automatically update enrollment status from 'pending' 
  to 'registered' when Mollie payment webhook confirms successful payment 
  for the associated invoice.
```

---

## Requirement 3: Lead-to-Student Data Model

### User Requirement
> "The details of the lead and the student are then the same. A question: Should the exact data be copied from lead to student? i.e. first name, last name, email, phone, etc. - or is it best to have a separate contact table which holds this information. Please consider the most efficient way to implement this."

### Database Analysis

**Current `leads` table:**
```sql
CREATE TABLE `leads` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `languages` varchar(255) DEFAULT NULL,
  `activity_notes` text DEFAULT NULL,
  ...
)
```

**Current `students` table:**
```sql
CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) UNSIGNED DEFAULT NULL,  -- ✅ Links back to original lead
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `country_of_origin` varchar(255) DEFAULT NULL,
  `city_of_residence` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `previous_courses` text DEFAULT NULL,
  `initial_level` varchar(255) DEFAULT NULL,
  `current_level` varchar(255) DEFAULT NULL,
  `profile_notes` text DEFAULT NULL,
  ...
)
```

### Analysis: Three Approaches

#### Approach 1: Current Implementation (Data Duplication + Foreign Key)
**Status**: ✅ **CURRENTLY IMPLEMENTED**

**Pros:**
- Simple queries: `SELECT * FROM students WHERE id = ?` gets all data
- No joins needed for most common operations
- Lead and student lifecycles can diverge (student updates don't affect lead record)
- Clear historical record: original lead data preserved
- Better performance for student-centric queries
- Maintains referential integrity with `students.lead_id` FK

**Cons:**
- Data duplication (name, email, phone stored twice)
- Updates require touching multiple tables if person's info changes
- Potential for data inconsistency if not carefully managed

#### Approach 2: Shared Contact Table (Normalized)
```sql
CREATE TABLE `contacts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `languages` JSON DEFAULT NULL,
  PRIMARY KEY (`id`)
);

ALTER TABLE `leads` 
  DROP COLUMN `first_name`, `last_name`, `email`, `phone`, `country`, `languages`,
  ADD COLUMN `contact_id` bigint UNSIGNED NOT NULL;

ALTER TABLE `students`
  DROP COLUMN `first_name`, `last_name`, `email`, `phone`, `country_of_origin`, `city_of_residence`, `dob`, `languages`,
  ADD COLUMN `contact_id` bigint UNSIGNED NOT NULL;
```

**Pros:**
- No data duplication
- Single source of truth for contact info
- Updates in one place automatically reflect everywhere
- Better data consistency

**Cons:**
- Every query requires JOIN: `SELECT * FROM students JOIN contacts ...`
- More complex queries and slower performance
- Tightly couples lead and student (can't have different contact info)
- Migration nightmare (requires restructuring existing data)
- Complicates import workflows

#### Approach 3: Student Extends Lead (Single Table Inheritance)
```sql
-- Keep leads table as-is
-- Students table becomes "promotion" of lead with FK + additional fields only
ALTER TABLE `students`
  DROP COLUMN `first_name`, `last_name`, `email`, `phone`, `country_of_origin`, `languages`;
-- student table only has student-specific fields + lead_id FK
```

**Pros:**
- No data duplication
- Lead data is canonical
- Simple to promote lead → student

**Cons:**
- Every student query requires JOIN with leads
- Student can't have different contact info than original lead
- Deleting lead would orphan student (complex CASCADE rules)
- Confusing model: "Student belongs to Lead" feels backwards

### Recommendation: **Keep Current Approach (Data Duplication + FK)**

**Rationale:**
1. **Performance**: Student queries are frequent (attendance, enrollments, profiles). Avoiding JOINs is critical.
2. **Independence**: Student contact info may legitimately differ from original lead (updated email, phone, address)
3. **Historical Integrity**: Original lead data should be immutable snapshot of first contact
4. **Simplicity**: Current model is intuitive and easier to maintain
5. **Migration**: No breaking changes needed
6. **Industry Standard**: Most CRMs duplicate data between lead/contact/account entities

**Best Practices to Implement:**
1. **Lead Conversion Process**: When converting lead → student, copy all fields explicitly
2. **Audit Trail**: Use `audit_logs` table to track when contact info changes
3. **Deduplication**: Maintain unique constraint on email, implement merge workflow for duplicates
4. **Data Sync Option**: Optional: Add a "sync from lead" button in UI if needed

**Update spec.md - Add new section after FR-003:**
```markdown
### Lead-to-Student Data Model

**Design Decision**: The system uses **data duplication** between leads and students:
- When a lead is converted to a student, contact information (name, email, phone, etc.) is **copied** to the student record
- `students.lead_id` maintains a foreign key reference to the original lead
- Lead and student records can diverge after conversion (student info can be updated independently)
- Original lead data serves as historical record of first contact

**Rationale**: 
- Performance: Avoids JOINs on frequent student queries
- Independence: Student contact info may legitimately change after initial lead capture
- Simplicity: Intuitive model, easier to maintain
- Historical integrity: Preserves snapshot of original lead data

**Implementation Requirements**:
- Lead conversion must explicitly copy all contact fields
- Email uniqueness enforced with duplicate detection UI
- Audit log tracks all contact info changes
- Optional "view original lead" link in student profile
```

---

## Requirement 4: Activity Timeline with Timestamped Notes

### User Requirement
> "When looking at a lead or a student profile there should be an activity window which allows a user to add notes with a timestamp. Any activity associated with a lead should follow that person once they become a student."

### Database Analysis

**Current Implementation:**

**Leads table:**
```sql
`activity_notes` text DEFAULT NULL  -- ⚠️ Single text field, no timestamps
```

**Students table:**
```sql
`profile_notes` text DEFAULT NULL  -- ⚠️ Single text field, no timestamps
```

**Tasks table (might be used for activities):**
```sql
CREATE TABLE `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `assigned_to_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `related_entity_type` varchar(255) DEFAULT NULL,  -- ✅ Polymorphic relation
  `related_entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `due_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

**Audit logs table:**
```sql
CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `auditable_type` varchar(255) NOT NULL,  -- ✅ Polymorphic relation
  `auditable_id` bigint(20) UNSIGNED NOT NULL,
  `event` varchar(255) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

### Gap Analysis
- ❌ **MISSING**: Dedicated `activities` or `notes` table with timestamps
- ⚠️ **PARTIAL**: `tasks` table could serve this purpose but is task-specific (has due dates, status, etc.)
- ⚠️ **PARTIAL**: `audit_logs` tracks changes but not manual notes
- ❌ **MISSING**: Mechanism to transfer lead activities to student upon conversion

### Recommendation

**Create new `activities` table:**
```sql
CREATE TABLE `activities` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `related_entity_type` varchar(255) NOT NULL,  -- 'Lead', 'Student', 'Enrollment', etc.
  `related_entity_id` bigint(20) UNSIGNED NOT NULL,
  `activity_type` enum('note', 'call', 'email', 'meeting', 'level_check', 'payment', 'enrollment', 'other') NOT NULL DEFAULT 'note',
  `subject` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activities_related_entity_index` (`related_entity_type`, `related_entity_id`),
  KEY `activities_created_by_user_id_index` (`created_by_user_id`),
  KEY `activities_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Foreign key constraints:**
```sql
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_created_by_user_id_foreign` 
  FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
```

**Lead Conversion Process:**
1. When lead (ID=123) converts to student (ID=456):
   ```sql
   -- Copy all lead activities to student
   INSERT INTO activities (related_entity_type, related_entity_id, activity_type, subject, body, created_by_user_id, created_at, updated_at)
   SELECT 'Student', 456, activity_type, subject, body, created_by_user_id, created_at, updated_at
   FROM activities
   WHERE related_entity_type = 'Lead' AND related_entity_id = 123;
   
   -- OR: Update in place (simpler, preserves continuity)
   UPDATE activities 
   SET related_entity_type = 'Student', related_entity_id = 456
   WHERE related_entity_type = 'Lead' AND related_entity_id = 123;
   ```

2. Add conversion activity note:
   ```sql
   INSERT INTO activities (related_entity_type, related_entity_id, activity_type, subject, body, created_by_user_id, created_at)
   VALUES ('Student', 456, 'other', 'Converted from Lead', 'This student was converted from Lead #123', <user_id>, NOW());
   ```

**Migration for existing data:**
```sql
-- Migrate existing lead activity_notes to activities table
INSERT INTO activities (related_entity_type, related_entity_id, activity_type, subject, body, created_at, updated_at)
SELECT 
  'Lead',
  id,
  'note',
  'Historical Notes',
  activity_notes,
  created_at,
  updated_at
FROM leads
WHERE activity_notes IS NOT NULL AND activity_notes != '';

-- Migrate existing student profile_notes
INSERT INTO activities (related_entity_type, related_entity_id, activity_type, subject, body, created_at, updated_at)
SELECT 
  'Student',
  id,
  'note',
  'Historical Notes',
  profile_notes,
  created_at,
  updated_at
FROM students
WHERE profile_notes IS NOT NULL AND profile_notes != '';

-- Keep activity_notes and profile_notes columns for backward compatibility initially
-- Can be deprecated later
```

**Update spec.md - Add new FR-018:**
```markdown
- FR-018: System MUST provide an activity timeline for leads and students:
  - Activity types: note, call, email, meeting, level_check, payment, enrollment, other
  - Each activity MUST record: type, subject, body, timestamp, created_by user
  - Activities MUST be displayed in reverse chronological order
  - When a lead is converted to student, ALL lead activities MUST transfer to student record
  - Polymorphic design: activities can relate to Lead, Student, Enrollment, or other entities
  - Users MUST be able to filter activities by type and date range
```

---

## Requirement 5: Course History & Historical Data Import

### User Requirement
> "When looking at a student profile it should be possible not only to see what courses they are presently enrolled in but the history of courses they have previously been enrolled in. When I import the old data I will create old course names in the course_offerings table that the old students has attended already. Please help me devise a way to do this (a workflow) when importing data of old students. Some of these old students may still be attending future courses."

### Database Analysis

**Current `students` table:**
```sql
`previous_courses` text DEFAULT NULL,  -- ✅ Exists but unstructured
`initial_level` varchar(255) DEFAULT NULL,
`current_level` varchar(255) DEFAULT NULL,
```

**Current `enrollments` table:**
```sql
CREATE TABLE `enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `course_offering_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('registered','active','cancelled','completed') NOT NULL DEFAULT 'registered',
  `mid_course_level` varchar(255) DEFAULT NULL,
  `mid_course_notes` text DEFAULT NULL,
  `is_trial` tinyint(1) DEFAULT 0,
  `enrolled_at` datetime DEFAULT NULL,
  `dropped_at` timestamp NULL DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

**Current `course_offerings` table:**
```sql
CREATE TABLE `course_offerings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `attendance_id` varchar(255) DEFAULT NULL,
  `round` int(11) DEFAULT 1,
  `course_key` varchar(255) NOT NULL,
  `course_full_name` varchar(255) NOT NULL,
  `level` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `book_included` tinyint(1) DEFAULT 1,
  `course_book` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `hours_total` int(11) DEFAULT NULL,
  `schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `teacher_hourly_rate` decimal(10,2) DEFAULT NULL,
  `classroom_cost` decimal(10,2) DEFAULT NULL,
  `admin_overhead` decimal(10,2) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `online` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)
```

### Specification Check

**From spec.md (lines 200-250) - Historical Course Import section exists:**
```markdown
## Historical Course Data Import (Added: November 19, 2025)

### Requirements
**FR-016: Historical Course Import**
- System MUST support import of historical course records (2022-2024) from Trello exports
- System MUST link historical courses to existing student records via email matching
- System MUST display historical courses on student profile (admin view)
- System MUST populate "previous_courses" field with historical course data for use in attendance sheets
- Historical attendance data is NOT required (only course participation records)
```

### Gap Analysis
- ✅ **SPECIFIED**: FR-016 exists for historical course import
- ⚠️ **PARTIAL**: `enrollments` table can track all courses but lacks `is_historical` flag
- ⚠️ **PARTIAL**: `students.previous_courses` is text field (unstructured)
- ❌ **MISSING**: Clear distinction between current and historical course enrollments
- ❌ **MISSING**: Historical course metadata (original source, import date, confidence level)

### Recommendation

**Option A: Add `is_historical` flag to enrollments (RECOMMENDED)**
```sql
ALTER TABLE `enrollments` 
  ADD COLUMN `is_historical` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Imported from historical data vs. created in system',
  ADD COLUMN `historical_metadata` JSON DEFAULT NULL COMMENT 'Source, import_date, confidence, original_data';

ALTER TABLE `course_offerings`
  ADD COLUMN `is_historical` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Historical course vs. current/future course',
  ADD INDEX `course_offerings_is_historical_index` (`is_historical`);
```

**Benefits:**
- Single source of truth: ALL enrollments in one table
- Easy queries: 
  - Current courses: `WHERE is_historical = 0 AND status IN ('active', 'registered')`
  - Historical courses: `WHERE is_historical = 1`
  - All courses: `SELECT * FROM enrollments ORDER BY enrolled_at DESC`
- Historical metadata stores import provenance
- Can still populate `previous_courses` text field for backward compatibility

### Historical Data Import Workflow

**Step 1: Prepare Historical Course Offerings**
```sql
-- Import historical courses as course_offerings with is_historical = 1
INSERT INTO course_offerings (
  course_key, course_full_name, level, program, type,
  start_date, end_date, hours_total, is_historical, created_at
) VALUES (
  'A2_MORN_ENSCH_2022_R1',
  'A2 Pre-Intermediate Morning Enschede 2022 Round 1',
  'A2',
  'General English',
  'morning',
  '2022-01-15',
  '2022-03-15',
  24,
  1,  -- is_historical
  NOW()
);
```

**Step 2: Match Students**
```sql
-- Find student by email (preferred)
SELECT id FROM students WHERE email = 'student@example.com';

-- If not found, try matching by name + phone
SELECT id FROM students 
WHERE first_name = 'John' AND last_name = 'Doe' AND phone = '+31612345678';

-- If still not found, create new student record with lead_id = NULL
INSERT INTO students (
  first_name, last_name, email, phone, country_of_origin, 
  initial_level, created_at
) VALUES (
  'John', 'Doe', 'student@example.com', '+31612345678', 'Netherlands',
  'A2', NOW()
);
```

**Step 3: Create Historical Enrollment**
```sql
INSERT INTO enrollments (
  student_id,
  course_offering_id,
  status,
  is_historical,
  historical_metadata,
  enrolled_at,
  created_at
) VALUES (
  <student_id>,
  <historical_course_offering_id>,
  'completed',  -- Historical courses are completed by default
  1,  -- is_historical
  JSON_OBJECT(
    'import_source', 'Trello',
    'import_date', NOW(),
    'original_course_name', 'A2 PR_MORN_ENSCH',
    'confidence', 'high',
    'matched_by', 'email'
  ),
  '2022-01-15',  -- enrolled_at = historical start date
  NOW()
);
```

**Step 4: Update Student's previous_courses (backward compatibility)**
```sql
UPDATE students
SET previous_courses = CONCAT(
  COALESCE(previous_courses, ''),
  IF(previous_courses IS NOT NULL AND previous_courses != '', ', ', ''),
  'A2 Pre-Intermediate Morning Enschede 2022 Round 1'
)
WHERE id = <student_id>;
```

**Step 5: Create Import Log Activity**
```sql
INSERT INTO activities (
  related_entity_type,
  related_entity_id,
  activity_type,
  subject,
  body,
  created_at
) VALUES (
  'Student',
  <student_id>,
  'other',
  'Historical Course Imported',
  'Imported historical enrollment: A2 Pre-Intermediate Morning Enschede 2022 Round 1 (Source: Trello, Date: 2022-01-15)',
  NOW()
);
```

### Import Script Workflow (PHP/Laravel)

```php
<?php
// import_historical_courses.php

// Read Trello CSV export
$csv = file_get_contents('historical_courses_2022_2024.csv');
$rows = str_getcsv($csv, "\n");

foreach ($rows as $index => $row) {
    if ($index === 0) continue; // Skip header
    
    $data = str_getcsv($row);
    
    // Parse row
    $email = $data[2];
    $firstName = $data[0];
    $lastName = $data[1];
    $phone = $data[3];
    $courseName = $data[4];
    $courseDate = $data[5];
    $level = $data[6];
    
    // Step 1: Find or create student
    $student = DB::table('students')->where('email', $email)->first();
    
    if (!$student && $phone) {
        $student = DB::table('students')
            ->where('first_name', $firstName)
            ->where('last_name', $lastName)
            ->where('phone', $phone)
            ->first();
    }
    
    if (!$student) {
        // Create new student
        $studentId = DB::table('students')->insertGetId([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'initial_level' => $level,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } else {
        $studentId = $student->id;
    }
    
    // Step 2: Find or create historical course offering
    $courseKey = generateCourseKey($courseName, $courseDate);
    $courseOffering = DB::table('course_offerings')
        ->where('course_key', $courseKey)
        ->first();
    
    if (!$courseOffering) {
        $courseOfferingId = DB::table('course_offerings')->insertGetId([
            'course_key' => $courseKey,
            'course_full_name' => $courseName,
            'level' => $level,
            'start_date' => $courseDate,
            'is_historical' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } else {
        $courseOfferingId = $courseOffering->id;
    }
    
    // Step 3: Create historical enrollment (idempotent)
    $existingEnrollment = DB::table('enrollments')
        ->where('student_id', $studentId)
        ->where('course_offering_id', $courseOfferingId)
        ->first();
    
    if (!$existingEnrollment) {
        DB::table('enrollments')->insert([
            'student_id' => $studentId,
            'course_offering_id' => $courseOfferingId,
            'status' => 'completed',
            'is_historical' => 1,
            'historical_metadata' => json_encode([
                'import_source' => 'Trello',
                'import_date' => now()->toDateTimeString(),
                'original_course_name' => $courseName,
                'matched_by' => $student ? 'email' : 'created',
            ]),
            'enrolled_at' => $courseDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✓ Created enrollment for {$firstName} {$lastName} in {$courseName}\n";
    } else {
        echo "⊘ Skipped (already exists): {$firstName} {$lastName} in {$courseName}\n";
    }
}

function generateCourseKey($courseName, $date) {
    // Example: "A2 Pre-Intermediate Morning Enschede" + "2022-01-15"
    // → "A2_MORN_ENSCH_2022_R1"
    $parts = explode(' ', $courseName);
    $level = $parts[0];
    $type = strpos(strtolower($courseName), 'morning') !== false ? 'MORN' : 'EVE';
    $location = strpos(strtolower($courseName), 'enschede') !== false ? 'ENSCH' : 'OTHER';
    $year = date('Y', strtotime($date));
    
    return "{$level}_{$type}_{$location}_{$year}_R1";
}
```

### Handling Students in Both Historical and Current Courses

**Scenario**: Student attended A2 in 2022 (historical) and is now enrolled in B1 2025 (current)

**Database State:**
```sql
-- Historical enrollment
student_id=1, course_offering_id=100 (A2 2022), status='completed', is_historical=1

-- Current enrollment
student_id=1, course_offering_id=200 (B1 2025), status='active', is_historical=0
```

**Queries:**

```sql
-- Get all courses for student (historical + current)
SELECT 
  e.status,
  e.is_historical,
  co.course_full_name,
  co.level,
  co.start_date,
  co.end_date
FROM enrollments e
JOIN course_offerings co ON e.course_offering_id = co.id
WHERE e.student_id = 1
ORDER BY co.start_date DESC;

-- Get only current/active courses
SELECT co.*
FROM enrollments e
JOIN course_offerings co ON e.course_offering_id = co.id
WHERE e.student_id = 1 
  AND e.is_historical = 0 
  AND e.status IN ('registered', 'active')
ORDER BY co.start_date;

-- Get course history
SELECT co.course_full_name, co.level, co.start_date, e.status
FROM enrollments e
JOIN course_offerings co ON e.course_offering_id = co.id
WHERE e.student_id = 1 
  AND e.is_historical = 1
ORDER BY co.start_date DESC;
```

**UI Implementation:**

Student Profile Page:
```
=================================
STUDENT: John Doe
Email: john@example.com
Current Level: B1
=================================

CURRENT COURSES
---------------------------------
[Active] B1 Intermediate Evening Online - Started 2025-01-15
         Teacher: Jane Smith | Progress: 50%

COURSE HISTORY
---------------------------------
[Completed] A2 Pre-Intermediate Morning Enschede - 2022-01-15 to 2022-03-15
            (Historical import from Trello)
[Completed] A1 Elementary Evening Online - 2021-09-01 to 2021-11-30
            (Historical import from Trello)
```

### Update spec.md FR-016

**Replace existing FR-016 section with:**

```markdown
- FR-016: Historical Course Import and Tracking
  - System MUST support import of historical course records (2022-2024) from Trello exports
  - System MUST flag historical courses with `is_historical = 1` in course_offerings table
  - System MUST flag historical enrollments with `is_historical = 1` in enrollments table
  - System MUST store import metadata (source, date, confidence, matching method) in enrollments.historical_metadata JSON field
  - System MUST match historical students by:
    1. Email (primary)
    2. First name + Last name + Phone (secondary)
    3. Create new student record if no match (tertiary)
  - System MUST create enrollment records with status = 'completed' for all historical courses
  - System MUST display both current and historical courses on student profile:
    - Current courses: WHERE is_historical = 0 AND status IN ('active', 'registered')
    - Course history: WHERE is_historical = 1 OR status = 'completed'
  - System MUST maintain backward compatibility by populating students.previous_courses text field
  - Import script MUST be idempotent (safe to run multiple times without duplicates)
  - Import script MUST log success/skip/error for each record processed
```

**Add new FR-019:**
```markdown
- FR-019: Student Course History View
  - Student profile MUST display two sections:
    1. "Current Courses" - active and registered enrollments
    2. "Course History" - completed and historical enrollments
  - Course history MUST show: course name, level, dates, status
  - Historical courses MUST be visually distinguished (badge/icon)
  - Course history MUST be sortable by date (newest first by default)
  - Teachers MUST see course history when viewing attendance rosters
  - Attendance sheets MUST populate "Previous Courses" from enrollment history
```

---

## Summary of Required Database Changes

### 1. Leads Table
```sql
ALTER TABLE `leads` 
  ADD COLUMN `reference` ENUM('online_form', 'level_check', 'phone_call', 'walk_in', 'referral', 'other') DEFAULT NULL 
    COMMENT 'Lead type/channel' AFTER `source`,
  ADD COLUMN `source_detail` VARCHAR(255) DEFAULT NULL 
    COMMENT 'Marketing source: google, facebook, ai, instagram, etc.' AFTER `reference`,
  ADD INDEX `leads_reference_index` (`reference`);
```

### 2. Enrollments Table
```sql
ALTER TABLE `enrollments` 
  MODIFY `status` ENUM('pending', 'registered', 'active', 'cancelled', 'completed') 
    NOT NULL DEFAULT 'pending',
  ADD COLUMN `is_historical` TINYINT(1) NOT NULL DEFAULT 0 
    COMMENT 'Imported from historical data' AFTER `is_trial`,
  ADD COLUMN `historical_metadata` JSON DEFAULT NULL 
    COMMENT 'Import source, date, confidence, matching method' AFTER `is_historical`,
  ADD INDEX `enrollments_is_historical_index` (`is_historical`),
  ADD INDEX `enrollments_status_index` (`status`);
```

### 3. Course Offerings Table
```sql
ALTER TABLE `course_offerings`
  ADD COLUMN `is_historical` TINYINT(1) NOT NULL DEFAULT 0 
    COMMENT 'Historical course vs. current/future' AFTER `online`,
  ADD INDEX `course_offerings_is_historical_index` (`is_historical`);
```

### 4. Activities Table (NEW)
```sql
CREATE TABLE `activities` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `related_entity_type` varchar(255) NOT NULL COMMENT 'Lead, Student, Enrollment, etc.',
  `related_entity_id` bigint(20) UNSIGNED NOT NULL,
  `activity_type` enum('note', 'call', 'email', 'meeting', 'level_check', 'payment', 'enrollment', 'other') 
    NOT NULL DEFAULT 'note',
  `subject` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activities_related_entity_index` (`related_entity_type`, `related_entity_id`),
  KEY `activities_created_by_user_id_index` (`created_by_user_id`),
  KEY `activities_created_at_index` (`created_at`),
  KEY `activities_type_index` (`activity_type`),
  CONSTRAINT `activities_created_by_user_id_foreign` 
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Summary of Specification Updates Needed

### spec.md Updates

**1. Update FR-001 (Lead Source Tracking)**
```markdown
- FR-001: System MUST record leads with:
  - reference (lead type): online_form, level_check, phone_call, walk_in, referral, other
  - source_detail (marketing attribution): google, facebook, ai, instagram, referral_name, etc.
  - contact details (name, email, phone, country of origin, languages spoken)
  - activity timeline (see FR-018)
```

**2. Update FR-005 (Enrollment Status)**
```markdown
- FR-005: System MUST allow enrollments linking a student to a specific course 
  offering and track enrollment status:
  - pending: Enrollment created, awaiting payment
  - registered: Payment received, student registered for course
  - active: Course has started, student actively participating
  - cancelled: Student withdrew or was removed
  - completed: Student finished the course
  - Historical enrollments are flagged separately (see FR-016)
```

**3. Add FR-017 (Payment-Triggered Enrollment Update)**
```markdown
- FR-017: System MUST automatically update enrollment status from 'pending' 
  to 'registered' when Mollie payment webhook confirms successful payment 
  for the associated invoice.
```

**4. Add FR-018 (Activity Timeline)**
```markdown
- FR-018: System MUST provide an activity timeline for leads and students:
  - Activity types: note, call, email, meeting, level_check, payment, enrollment, other
  - Each activity MUST record: type, subject, body, timestamp, created_by user
  - Activities MUST be displayed in reverse chronological order
  - When a lead is converted to student, ALL lead activities MUST transfer to student record
  - Polymorphic design: activities can relate to Lead, Student, Enrollment, or other entities
  - Users MUST be able to filter activities by type and date range
  - Activity timeline MUST be visible in both lead and student profile views
```

**5. Update FR-016 (Historical Course Import)**
```markdown
- FR-016: Historical Course Import and Tracking
  - System MUST support import of historical course records (2022-2024) from Trello exports
  - System MUST flag historical courses with `is_historical = 1` in course_offerings table
  - System MUST flag historical enrollments with `is_historical = 1` in enrollments table
  - System MUST store import metadata (source, date, confidence, matching method) in JSON
  - System MUST match historical students by: 1) Email 2) Name+Phone 3) Create new
  - System MUST create enrollment records with status = 'completed' for historical courses
  - System MUST display both current and historical courses on student profile
  - Import script MUST be idempotent and log all operations
```

**6. Add FR-019 (Course History View)**
```markdown
- FR-019: Student Course History View
  - Student profile MUST display two sections: "Current Courses" and "Course History"
  - Course history MUST show: course name, level, dates, status, teacher
  - Historical courses MUST be visually distinguished (e.g., badge indicating "Imported")
  - Course history MUST be sortable by date (newest first by default)
  - Teachers MUST see course history when viewing attendance rosters
  - Attendance sheets MUST populate "Previous Courses" from enrollment history
```

**7. Add Data Model Section (Lead-to-Student Design)**
```markdown
### Lead-to-Student Data Model

**Design Decision**: Data duplication between leads and students:
- Contact info (name, email, phone, etc.) is **copied** during lead conversion
- `students.lead_id` maintains FK reference to original lead
- Records can diverge after conversion (student info updated independently)
- Original lead serves as historical snapshot of first contact

**Rationale**: Performance (no JOINs), independence (info can change), simplicity

**Requirements**:
- Lead conversion MUST explicitly copy all contact fields
- Email uniqueness enforced with duplicate detection
- Audit log tracks contact info changes
- "View original lead" link in student profile
```

---

## Migration Files Needed

### Migration 1: Add Lead Source Tracking
**File**: `database/migrations/20251121_000001_add_lead_source_tracking.php`

### Migration 2: Add Enrollment Pending Status and Historical Flags
**File**: `database/migrations/20251121_000002_add_enrollment_enhancements.php`

### Migration 3: Add Historical Flag to Course Offerings
**File**: `database/migrations/20251121_000003_add_course_historical_flag.php`

### Migration 4: Create Activities Table
**File**: `database/migrations/20251121_000004_create_activities_table.php`

### Migration 5: Migrate Existing Notes to Activities
**File**: `database/migrations/20251121_000005_migrate_notes_to_activities.php`

---

## Implementation Checklist

### Database Changes
- [ ] Create migration: Add `reference` and `source_detail` to `leads` table
- [ ] Create migration: Add `pending` status and historical flags to `enrollments`
- [ ] Create migration: Add `is_historical` flag to `course_offerings`
- [ ] Create migration: Create `activities` table
- [ ] Create migration: Migrate existing `activity_notes` and `profile_notes` to `activities`

### Specification Updates
- [ ] Update FR-001 with detailed source tracking requirements
- [ ] Update FR-005 with `pending` status and enrollment workflow
- [ ] Add FR-017 for automatic enrollment status update on payment
- [ ] Add FR-018 for activity timeline requirements
- [ ] Update FR-016 with detailed historical import specification
- [ ] Add FR-019 for course history view requirements
- [ ] Add "Lead-to-Student Data Model" design decision section

### Application Code
- [ ] Update Lead model to use `reference` and `source_detail`
- [ ] Update Enrollment model to handle `pending` status
- [ ] Create Activity model with polymorphic relations
- [ ] Update Mollie webhook handler to auto-update enrollment status
- [ ] Create lead-to-student conversion service that copies data and transfers activities
- [ ] Update student profile view to show activity timeline
- [ ] Update student profile view to show current vs. historical courses

### Import Scripts
- [ ] Create `import_historical_courses.php` script
- [ ] Implement idempotent course offering creation
- [ ] Implement student matching (email → name+phone → create)
- [ ] Implement historical enrollment creation with metadata
- [ ] Add logging and error handling
- [ ] Create dry-run mode for validation

### Testing
- [ ] Test lead creation with reference and source_detail
- [ ] Test enrollment workflow: pending → payment → registered
- [ ] Test activity timeline on lead and student profiles
- [ ] Test lead-to-student conversion with activity transfer
- [ ] Test historical import with mixed scenarios (new students, existing students)
- [ ] Test student profile showing both current and historical courses

---

## Questions for User

1. **Lead Source Detail**: What are the expected values for `source_detail`? Should it be:
   - Free text field (current recommendation)
   - ENUM with predefined values (google, facebook, instagram, ai, referral, etc.)
   - Combination (predefined + "other" with text field)

2. **Enrollment Status Transition**: When should enrollment go from `registered` to `active`?
   - Automatically on course start_date?
   - Manually by coordinator/teacher?
   - On first attendance record?

3. **Activity Types**: Are the proposed activity types sufficient?
   - note, call, email, meeting, level_check, payment, enrollment, other
   - Need additional types like: sms, whatsapp, social_media, etc.?

4. **Historical Import**: Do you have the Trello CSV ready?
   - Can you share a sample row for mapping verification?
   - Estimated number of historical records to import?

5. **Activities vs. Tasks**: Should tasks and activities be:
   - Kept separate (current recommendation)?
   - Merged into single table?
   - Tasks shown in activity timeline?

---

## Next Steps

1. **Review this analysis** and confirm approach for each requirement
2. **Answer questions** above to finalize implementation details
3. **Create database migrations** in order listed
4. **Update spec.md** with new/updated functional requirements
5. **Implement historical import script** and test with sample data
6. **Update application code** for UI changes (activity timeline, course history)
7. **Test end-to-end** workflow from lead → student → historical import

