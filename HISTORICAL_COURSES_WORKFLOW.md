# Historical Course Data Import - Workflow Guide

## Overview

Import historical course participation data from Trello (2022-2024) to link past courses with existing student records. This enables complete student course history tracking without requiring historical attendance data.

## Goals

1. ✅ **Associate historical courses with students** - Link 2022-2024 course participation to student records
2. ✅ **Populate previous_courses field** - Auto-fill "Previous Courses" on future attendance sheets
3. ✅ **Maintain historical records** - Preserve data for returning students
4. ✅ **Enable admin visibility** - Show complete student course history in admin interface

## Non-Goals

- ❌ Historical attendance data (individual session records not needed)
- ❌ Historical invoices/payments (financial data separate workflow)
- ❌ Historical certificates (can be generated later if needed)

---

## Database Schema Changes

### 1. Add `is_historical` Flag to course_offerings

```sql
ALTER TABLE course_offerings 
ADD COLUMN is_historical BOOLEAN DEFAULT FALSE AFTER online;
```

**Purpose:** Distinguish historical courses from current/future courses
**Impact:** Historical courses won't appear in teacher dropdown, only in admin views

### 2. Add `historical_metadata` to enrollments

```sql
ALTER TABLE enrollments 
ADD COLUMN historical_metadata JSON NULL AFTER status;
```

**Purpose:** Store original Trello card data for reference/debugging
**Format:** `{"trello_card_id": "...", "original_course_name": "...", "notes": "..."}`

### 3. Index Optimization

```sql
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_enrollments_student_course ON enrollments(student_id, course_offering_id);
CREATE INDEX idx_course_offerings_historical ON course_offerings(is_historical);
```

**Purpose:** Speed up student matching and historical data queries

---

## Data Preparation

### Step 1: Export Historical Data from Trello

**Option A: Trello CSV Export**
1. Open Trello board containing historical courses
2. Board Menu → Print and Export → Export as CSV
3. Download file (e.g., `Trello_Historical_2022-2024.csv`)

**Option B: Trello JSON Export**
1. Board Menu → Print and Export → Export as JSON
2. Download file (e.g., `Trello_Historical_2022-2024.json`)
3. Convert to CSV if needed using `trello_json_to_csv.php` converter script

### Step 2: Understand Trello Data Structure

**Expected Columns** (may vary - script will adapt):
- **Student Identification:**
  - First name / Surname / Full Name
  - Email address (PRIMARY matching field)
  - Phone number (optional)
  
- **Course Information:**
  - Course Name / Card Title (e.g., "A2 Morning Enschede 2023")
  - Course Level (A1, A2, B1, etc.)
  - Start Date / End Date (or Year)
  - Location (Enschede, Oldenzaal, Online)
  
- **Additional Data:**
  - Completion status
  - Final level (if assessed)
  - Notes (student performance, special situations)
  - Trello Card ID / URL

**Example Row:**
```csv
"First name","Surname","Email address","Course Name","Level","Start Date","End Date","Location","Notes"
"John","Doe","john.doe@example.com","A2 Morning Enschede Round 3","A2","01/09/2023","01/12/2023","Enschede","Good progress"
```

### Step 3: Clean and Validate Data

**Manual Review Checklist:**
- [ ] Email addresses are valid and properly formatted
- [ ] Course names follow consistent pattern (or document variations)
- [ ] Dates are in consistent format (MM/DD/YYYY or DD/MM/YYYY)
- [ ] Duplicate rows removed (same student + course)
- [ ] Special characters handled (accents, apostrophes)

**Common Data Issues:**
- Multiple email addresses for same student (choose primary)
- Typos in course names ("A2 Moring" → "A2 Morning")
- Inconsistent date formats (parse both US and EU formats)
- Missing email (will require name-based matching + manual review)

---

## Import Process

### Script: `import_historical_courses.php`

**Location:** `~/domains/mixtreelangdb.nl/mtl_app/import_historical_courses.php`

**Features:**
- Idempotent (safe to re-run)
- Manual .env loading (no Composer required)
- Student matching: email (primary), name (fallback), manual queue
- Course name normalization with mapping rules
- Dry-run mode for validation before commit
- Detailed import report with statistics

### Usage

**Dry Run (Validation Only):**
```bash
php import_historical_courses.php path/to/trello_export.csv --dry-run
```

**Output:**
```
=== Dry Run Mode - No Changes Will Be Made ===

Parsing CSV: trello_export.csv
Found 150 rows

Student Matching:
  ✓ 120 matched by email
  ⚠ 15 matched by name (review recommended)
  ❌ 15 unmatched (manual resolution required)

Courses to Create:
  - A2 Morning Enschede 2023 (15 enrollments)
  - B1 Afternoon Online 2023 (20 enrollments)
  - A1 Intensive Oldenzaal 2022 (10 enrollments)
  ... (total: 25 courses)

Enrollments to Create: 150
Previous Courses to Update: 120 students

Unmatched Records:
1. Row 15: jane.smith@oldmail.com - No student found
2. Row 23: bob.johnson@company.com - Multiple students found (manual merge needed)
...

Export unmatched records to: historical_import_unmatched_2025-11-19.csv
```

**Live Import:**
```bash
php import_historical_courses.php path/to/trello_export.csv
```

**With Custom Date Format:**
```bash
php import_historical_courses.php path/to/trello_export.csv --date-format="d/m/Y"
```

### Import Logic Flow

```
FOR EACH row in CSV:
  1. Parse student data (name, email, phone)
  2. Match student:
     - Try email exact match (case-insensitive)
     - Try name match (first_name + last_name)
     - If ambiguous → add to manual review queue
     - If no match → create placeholder or skip (based on flag)
  
  3. Parse course data (name, level, dates, location)
  4. Normalize course name using mapping rules
  5. Check if course exists:
     - Match by normalized name + year
     - If not found → create new CourseOffering with is_historical=true
  
  6. Check if enrollment exists:
     - Match by student_id + course_offering_id
     - If exists → skip (already imported)
     - If not → create Enrollment with status='completed'
  
  7. Update student.previous_courses field:
     - Append course name if not already present
     - Format: "Course Name (Year)"
  
  8. Store metadata:
     - Save original Trello data in enrollment.historical_metadata JSON
```

### Course Name Normalization

**Mapping Rules** (editable in script):
```php
$courseNameMappings = [
    // Pattern → Normalized Name
    '/A2.*Morning.*Enschede/i' => 'A2 PR_MORN_ENSCH',
    '/A2.*Afternoon.*Enschede/i' => 'A2 PR_AFT_ENSCH',
    '/B1.*Evening.*Online/i' => 'B1 PR_EVE_ONLINE',
    '/A1.*Intensive/i' => 'A1 INT',
    // Add more patterns as needed
];
```

**Fallback:** If no pattern matches, use original course name with cleaned formatting

---

## Post-Import Validation

### Step 1: Review Import Report

**Check Statistics:**
```
=== Import Complete ===

Summary:
  Total rows processed: 150
  Students matched: 135
  Students unmatched: 15
  Courses created: 25
  Enrollments created: 150
  Previous courses updated: 120

Time: 12.5 seconds
```

### Step 2: Verify Database

**SQL Queries:**

```sql
-- Check historical courses created
SELECT id, course_full_name, start_date, end_date, is_historical
FROM course_offerings 
WHERE is_historical = TRUE
ORDER BY start_date DESC;

-- Check historical enrollments
SELECT e.id, s.email, c.course_full_name, e.status, e.historical_metadata
FROM enrollments e
JOIN students s ON e.student_id = s.id
JOIN course_offerings c ON e.course_offering_id = c.id
WHERE c.is_historical = TRUE
LIMIT 20;

-- Check previous_courses field populated
SELECT id, CONCAT(first_name, ' ', last_name) as name, email, previous_courses
FROM students 
WHERE previous_courses IS NOT NULL AND previous_courses != ''
ORDER BY id DESC
LIMIT 20;

-- Count historical enrollments per student
SELECT s.id, CONCAT(s.first_name, ' ', s.last_name) as name, 
       COUNT(e.id) as historical_courses
FROM students s
LEFT JOIN enrollments e ON s.id = e.student_id
LEFT JOIN course_offerings c ON e.course_offering_id = c.id
WHERE c.is_historical = TRUE
GROUP BY s.id
ORDER BY historical_courses DESC;
```

### Step 3: Spot Check Student Profiles

**Retool Admin Interface:**
1. Open student list
2. Select student with known historical courses
3. Verify "Enrollment History" shows historical courses with dates
4. Verify "Previous Courses" field shows formatted list
5. Check enrollment metadata contains original Trello data

**Example Expected Display:**
```
Student: John Doe (john.doe@example.com)

Enrollment History:
  ✓ A2 Morning Enschede 2023 (Completed) - 01/09/2023 to 01/12/2023
  ✓ A1 Intensive 2022 (Completed) - 15/06/2022 to 15/07/2022
  → B1 Afternoon Online 2024 (Active) - 15/01/2024 to 15/04/2024

Previous Courses: A1 Intensive (2022), A2 Morning Enschede (2023)
```

### Step 4: Resolve Unmatched Records

**Review:** `historical_import_unmatched_YYYY-MM-DD.csv`

**Resolution Options:**

1. **Student email changed:**
   - Update student email in database to match Trello
   - Re-run import (idempotent - will match this time)

2. **Student doesn't exist in system:**
   - Decide: Create new student from historical data?
   - If yes: Use import script with `--create-missing-students` flag
   - If no: Document as former student (not in current system)

3. **Ambiguous match (multiple students with same name):**
   - Manually merge duplicate students in Retool
   - OR: Manual SQL to link correct student_id to enrollment

4. **Data quality issue (invalid email, missing name):**
   - Fix in Trello export CSV
   - Re-run import

---

## Testing Workflow

### Phase 1: Test Import (10-20 Records)

1. Export small sample from Trello (e.g., one course from 2024)
2. Run dry-run: `php import_historical_courses.php sample.csv --dry-run`
3. Review dry-run report
4. Run live import: `php import_historical_courses.php sample.csv`
5. Verify in database (SQL queries above)
6. Verify in Retool admin interface
7. Document any issues or edge cases

### Phase 2: Import 2024 Courses

1. Export all 2024 courses from Trello
2. Clean and validate data
3. Run dry-run and review
4. Run live import
5. Spot check 10% of students randomly
6. Address unmatched records
7. Re-run import if needed (idempotent)

### Phase 3: Import 2023 Courses

1. Repeat Phase 2 process for 2023 data
2. More unmatched records expected (older data, students may have left)

### Phase 4: Import 2022 Courses

1. Repeat Phase 2 process for 2022 data
2. Highest unmatch rate expected
3. Many students may no longer be in system

---

## Integration with Attendance Sheets

### Automatic Population

**When exporting new Google Sheets attendance:**
- `previous_courses` field automatically populated from student record
- Format: "Course 1 (Year), Course 2 (Year), ..."
- Student name displays in italic if `previous_courses` is not empty

**When importing Google Sheets attendance:**
- `import_google_attendance.php` reads "Previous Courses" column
- Updates student.previous_courses field
- Merges with existing historical data (no duplicates)

### Manual Override

**Teachers can:**
- Add additional courses in Google Sheets "Previous Courses" column
- These get merged with historical data on import
- Useful for courses taken elsewhere or recent courses not yet in system

---

## Maintenance & Updates

### Adding More Historical Data

**New historical courses found:**
1. Add to Trello export CSV
2. Run import script (idempotent)
3. Only new enrollments created, existing unchanged

### Correcting Historical Data

**If student was linked to wrong course:**
1. Delete enrollment: `DELETE FROM enrollments WHERE id = X`
2. Update student previous_courses: Remove incorrect course from field
3. Re-run import (will recreate correct enrollment)

**If course details need updating:**
1. Update course_offerings table directly
2. OR: Update Trello export and re-run import with `--update-existing` flag

---

## Troubleshooting

### Issue: "No students matched"

**Cause:** Email addresses in Trello don't match database
**Fix:**
1. Export current student emails: `SELECT id, email FROM students`
2. Compare with Trello export
3. Update Trello export to use current emails
4. OR: Update student emails in database to match Trello

### Issue: "Duplicate enrollment error"

**Cause:** Student already has enrollment for this course
**Fix:**
- This is expected behavior (idempotent)
- Script skips duplicate, no action needed
- Check if this is truly a duplicate or different course round

### Issue: "Course name not normalized"

**Cause:** Course name pattern not in mapping rules
**Fix:**
1. Add pattern to `$courseNameMappings` in script
2. Re-run import (will update existing courses)

### Issue: "previous_courses field not updating"

**Cause:** Field exceeded TEXT limit OR update logic issue
**Fix:**
1. Check field length: `SELECT LENGTH(previous_courses) FROM students ORDER BY LENGTH(previous_courses) DESC LIMIT 10`
2. If >65535 chars, consider moving to separate table
3. Check import script update logic

---

## Future Enhancements

**Potential Improvements:**
- [ ] Student-facing portal to view course history
- [ ] Automatic certificate regeneration for historical courses (>=80% attendance retroactive entry)
- [ ] Link historical courses to historical invoices (separate import workflow)
- [ ] Migration to separate `course_history` table (if previous_courses field too limited)
- [ ] Integration with certificate generation (auto-populate eligible historical courses)

---

## Files Required

**Import Script:**
- `import_historical_courses.php` - Main import script

**Migration:**
- `database/migrations/YYYYMMDD_add_historical_course_fields.php` - Schema changes

**Documentation:**
- `HISTORICAL_COURSES_WORKFLOW.md` (this file)
- `specs/001-title-english-language/spec.md` (updated with FR-016)

**Data Files:**
- `Trello_Historical_2022-2024.csv` (from Trello export)
- `historical_course_name_mappings.json` (optional: externalized mapping rules)

---

## Checklist

**Pre-Import:**
- [ ] Database schema updated (is_historical flag, historical_metadata field, indexes)
- [ ] Trello export downloaded and saved
- [ ] Data cleaned and validated
- [ ] Import script uploaded to server
- [ ] Dry-run executed and reviewed
- [ ] Backup database (just in case)

**Import:**
- [ ] Test import with small sample (10-20 records)
- [ ] Verified sample data in database and Retool
- [ ] Import 2024 courses
- [ ] Import 2023 courses
- [ ] Import 2022 courses
- [ ] Resolved unmatched records
- [ ] Spot-checked student profiles

**Post-Import:**
- [ ] Verified previous_courses field populated
- [ ] Tested Google Sheets attendance export (previous courses auto-fill)
- [ ] Tested Google Sheets attendance import (merges with historical data)
- [ ] Documented any issues or customizations
- [ ] Updated spec with completion status

---

## Contact / Support

**Questions or Issues:**
- Review this workflow guide first
- Check import script output logs
- Review database query results
- Check specs/001-title-english-language/spec.md for requirements

**Import Script Options:**
```bash
php import_historical_courses.php --help

Options:
  --dry-run              Validate without making changes
  --date-format=FORMAT   Date format (default: m/d/Y)
  --create-missing       Create students not found in system
  --update-existing      Update existing course details
  --verbose              Detailed output
  --help                 Show this help
```
