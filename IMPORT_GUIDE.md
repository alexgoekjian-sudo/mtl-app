# Data Import Guide

## Overview
Import course offerings, students, leads, and enrollments from normalized JSON files located in `specs/001-title-english-language/imports/out/`.

## Source Files
- **courses_normalized.json** - Course offerings from Google Sheets export (5598 lines, ~100 courses)
- **trello_normalized.json** - Students and leads from Trello export (2157 lines, ~400 records)

## Import Methods

### Method 1: Standalone PHP Script (Recommended for Server)

Upload `import_data_standalone.php` to your server and run:

```bash
cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app
php import_data_standalone.php
```

This script:
- Connects directly to MySQL using .env credentials
- Imports all courses from `courses_normalized.json`
- Imports students/leads from `trello_normalized.json`
- Creates enrollments for students linked to courses
- Skips duplicates automatically

**Output Example:**
```
=====================================
MTL Database Import
=====================================

✓ Database connected

Importing courses from: /path/to/courses_normalized.json
  Imported 10 courses...
  Imported 20 courses...
  ...
✓ Courses imported: 98, skipped: 2

Importing students/leads from: /path/to/trello_normalized.json
  Created 10 students, 8 enrollments...
  Created 20 students, 15 enrollments...
  ...
✓ Students: 287, Leads: 113, Enrollments: 245, Skipped: 0

=====================================
✓ Import complete!
=====================================
```

### Method 2: SQL Import Script (Backup Method)

If the PHP script doesn't work, use the generated SQL files:

```bash
# First, run the migration to add new columns
php artisan migrate

# Then import data via SQL
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb < import_courses.sql
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb < import_students_leads.sql
```

To regenerate the SQL files:
```bash
python generate_sql_import.py
```

This creates:
- **import_courses.sql** - 248 course INSERT statements
- **import_students_leads.sql** - 87 student INSERT statements with enrollments

### Method 3: Laravel Seeder (For Local Development)

If you have `vendor/` installed locally:

```bash
php artisan db:seed --class=ImportDataSeeder
```

## What Gets Imported

### Courses (course_offerings table)
- **attendance_id**: Unique identifier for attendance tracking (e.g., "B1_EVE _EDMON_1") - **PRIMARY KEY FOR COURSE**
- **round**: Course round number (1, 2, 3, etc.) from ROUND column in CSV
- **course_key**: Course type identifier (e.g., "A1 BEGINNER", "B2 EVE ONLINE") - **NOT UNIQUE**, same for all rounds
- **course_full_name**: Full descriptive name with date and round
- **course_book**: Textbook information with ISBN (e.g., "English File Intermediate 4th Edition (ISBN-13978-0194035910)")
- **level**: Extracted from course_key (A1, A2, B1, B2, C1, C2)
- **program**: Inferred (general, intensive, conversation, business, private)
- **type**: Parsed from schedule_type (morning, evening, afternoon, online, intensive)
- **start_date / end_date**: ISO 8601 dates
- **hours_total**: Extracted from hours_raw (e.g., "24 hours/6 weeks" → 24)
- **schedule**: JSON with days array and time_range
- **price**: Numeric price in EUR
- **location**: Physical location or "ONLINE"
- **online**: Boolean flag

**Important Changes:**
- `attendance_id` is now the unique identifier (from CSV column ATTENDANCE_COURSE_NAME)
- `course_key` is now non-unique and represents the course type
- Multiple rounds of the same course type will have the same `course_key` but different `attendance_id` and `round` numbers
- `course_book` contains the textbook ISBN and title from CSV

### Students (students table)
Created when record has `assessed_level` or `placement_result`:
- **first_name / last_name**: Contact names
- **email / phone**: Contact info (at least one required)
- **country_of_origin**: From "Country of origin" column
- **city_of_residence**: From "City of Residence" column
- **initial_level / current_level**: From "Level" (assessed_level)
- **languages**: JSON array parsed from comma-separated list
- **profile_notes**: From "Description of completed courses and other notes"

### Leads (leads table)
Created when record lacks level check data:
- **first_name / last_name**: Contact names
- **email / phone**: Contact info
- **country**: Country of origin
- **source**: Set to "trello_import"
- **languages**: Comma-separated string
- **activity_notes**: Additional notes

### Enrollments (enrollments table)
Created when student has `linked_course_key` (which maps to `attendance_id`):
- **student_id**: Links to created student
- **course_offering_id**: Links to course via attendance_id matching (unique identifier)
- **status**: Set to "registered"
- **enrolled_at**: Current timestamp

## Duplicate Handling

The import script **skips duplicates** automatically:

- **Courses**: Checked by `attendance_id` (unique identifier, e.g., "B1_EVE _EDMON_1")
- **Students**: Checked by `email` (primary) or `phone` (fallback)
- **Enrollments**: Checked by `student_id` + `course_offering_id` pair

**Note**: Since `course_key` is now non-unique (same for all rounds), duplicate detection uses `attendance_id` to ensure each specific course offering is only imported once.

You can safely run the import multiple times.

## Expected Results

Based on the normalized JSON files:

- **~100 courses** across multiple levels (A1-C2) and types (morning, evening, online, intensive)
- **~280-300 students** with level checks and course enrollments
- **~100-120 leads** without level checks (prospective students)
- **~240-260 enrollments** linking students to their registered courses

## Verification Queries

After import, verify data with these SQL queries:

```sql
-- Count courses by level
SELECT level, COUNT(*) as count FROM course_offerings GROUP BY level;

-- Count students vs leads
SELECT COUNT(*) FROM students;  -- Should be ~280-300
SELECT COUNT(*) FROM leads;     -- Should be ~100-120

-- Count enrollments
SELECT COUNT(*) FROM enrollments;  -- Should be ~240-260

-- Top 5 popular courses
SELECT co.course_key, co.course_full_name, COUNT(e.id) as enrollments
FROM course_offerings co
LEFT JOIN enrollments e ON co.id = e.course_offering_id
GROUP BY co.id
ORDER BY enrollments DESC
LIMIT 5;

-- Students by country
SELECT country_of_origin, COUNT(*) as count
FROM students
WHERE country_of_origin IS NOT NULL
GROUP BY country_of_origin
ORDER BY count DESC
LIMIT 10;
```

## API Verification

Test with curl after import:

```bash
TOKEN="A317F31717358A2C316D9758857028526ABD0BC53D4399FA"

# Count courses
curl -H "Authorization: Bearer $TOKEN" https://mixtreelangdb.nl/api/retool/course_offerings | jq '. | length'

# Count students
curl -H "Authorization: Bearer $TOKEN" https://mixtreelangdb.nl/api/retool/students | jq '. | length'

# Count enrollments
curl -H "Authorization: Bearer $TOKEN" https://mixtreelangdb.nl/api/retool/enrollments | jq '. | length'

# View all data summary
curl -H "Authorization: Bearer $TOKEN" https://mixtreelangdb.nl/api/retool/all | jq '{students: (.students | length), courses: (.course_offerings | length), enrollments: (.enrollments | length), leads: (.leads | length)}'
```

## Troubleshooting

### "Database connection failed"
- Check `.env` file has correct `DB_*` credentials
- Verify database server is accessible

### "File not found"
- Ensure `specs/001-title-english-language/imports/out/` exists
- Check that normalized JSON files are present

### Duplicate key errors
- Script should auto-skip duplicates
- If errors persist, check unique constraints on tables

### No enrollments created
- Verify courses were imported first
- Check `linked_course_key` in trello_normalized.json matches `course_key` in courses

## Notes

- The import preserves all original data from Trello/Google Sheets exports
- Course-student linking is automatic based on `linked_course_key` matching
- Students without course links are imported but not enrolled
- All timestamps use current time at import
- No invoices or payments are created (import students/courses only)

---

**Import script created**: November 2025  
**Data source**: Trello export + Google Sheets course list  
**Records**: ~100 courses, ~400 students/leads, ~250 enrollments
