# Course Offerings Schema Changes - November 18, 2025

## Summary of Changes

Based on the original CSV structure from Google Sheets, the `course_offerings` table has been updated to properly reflect the attendance tracking system and include missing data fields.

## Database Schema Changes

### New Migration: `20251118_000002_add_course_offering_identifiers.php`

Added three new columns to `course_offerings`:

| Column | Type | Constraints | Source CSV Column | Purpose |
|--------|------|-------------|-------------------|---------|
| **attendance_id** | string | UNIQUE, NOT NULL | ATTENDANCE_COURSE_NAME | Unique identifier for each course offering (e.g., "B1_EVE _EDMON_1") |
| **round** | integer | DEFAULT 1 | ROUND | Course round number (1, 2, 3, etc.) |
| **course_book** | text | NULLABLE | COURSE_BOOK | Textbook information with ISBN |

### Field Role Changes

| Field | Previous Role | New Role | Example |
|-------|--------------|----------|---------|
| **course_key** | Unique identifier | Course type identifier (non-unique) | "B1 EVE ONLINE" |
| **attendance_id** | Did not exist | Unique identifier | "B1_EVE _EDMON_1" |

## Rationale

### Problem
The original implementation used `course_key` (from CSV's COURSE_SHORT_NAME) as a unique identifier, but this field is actually **not unique**. For example:
- Round 1: "B1 EVE ONLINE" → course_key = "B1 EVE ONLINE"
- Round 2: "B1 EVE ONLINE" → course_key = "B1 EVE ONLINE" (duplicate!)

### Solution
Use the CSV's **ATTENDANCE_COURSE_NAME** column which IS unique:
- Round 1: attendance_id = "B1_EVE _EDMON_1"
- Round 2: attendance_id = "B1_EVE _FIFE_2"
- Round 3: attendance_id = "B1_EVE _DUBLI_3"

This allows:
1. Multiple rounds of the same course type to coexist
2. Proper tracking of which specific course offering a student attended
3. Correct attendance record linking
4. Better course management and filtering

## CSV Column Mapping

| CSV Column | Database Field | Type | Notes |
|------------|---------------|------|-------|
| ATTENDANCE_COURSE_NAME | attendance_id | string | **Unique identifier** |
| ROUND | round | integer | Round number (1, 2, 3...) |
| COURSE_SHORT_NAME | course_key | string | Course type (non-unique) |
| COURSE_BOOK | course_book | text | ISBN and textbook title |
| COURSE_FULL_NAME | course_full_name | string | Full name with date and round |
| START_DATE | start_date | date | ISO 8601 format |
| END_DATE | end_date | date | ISO 8601 format |
| TIMES | time_range | string | Parsed into schedule JSON |
| DAYS | days | JSON array | Part of schedule JSON |
| HRS | hours_total | integer | Extracted number |
| PRICE | price | decimal | EUR amount |
| LOCATION | location | string | Address or "ONLINE" |
| ONLINE_ONSITE | delivery_mode → online | boolean | Converted to boolean |

## Code Changes

### 1. Model Updates (`app/Models/CourseOffering.php`)
```php
protected $fillable = [
    'attendance_id',      // NEW - unique identifier
    'round',              // NEW - round number
    'course_key',         // Changed: now non-unique
    'course_book',        // NEW - textbook info
    // ... other existing fields
];
```

### 2. Controller Updates (`app/Http/Controllers/CourseOfferingController.php`)
- **Validation**: Changed to require `attendance_id` instead of `course_key`
- **Unique constraint**: `attendance_id` must be unique on create/update
- **Field list**: Updated to include all new fields

### 3. Import Manifest (`specs/001-title-english-language/imports/manifest.courses.json`)
Added mappings:
```json
{
  "ATTENDANCE_COURSE_NAME": "attendance_id",
  "ROUND": {"to": "round", "type": "number"},
  "COURSE_BOOK": "course_book"
}
```

### 4. Import Scripts

#### PHP Standalone (`import_data_standalone.php`)
- Changed duplicate check from `course_key` to `attendance_id`
- Added `round` and `course_book` to INSERT statement
- Updated course index to use `attendance_id` for enrollment linking

#### Laravel Seeder (`database/seeds/ImportDataSeeder.php`)
- Changed duplicate check to use `attendance_id`
- Added new fields to course data mapping
- Updated enrollment linking to use `attendance_id`

### 5. SQL Import Generator (`generate_sql_import.py`)
- New Python script to generate SQL INSERT statements
- Handles all new fields properly
- Creates two files:
  - `import_courses.sql` - 248 course offerings
  - `import_students_leads.sql` - 87 students with enrollments

## Data Examples

### Before (Incorrect)
```
course_key: "B1 EVE ONLINE"  ← DUPLICATE for all rounds
course_full_name: "B1 EVE ONLINE - EDMONTON - 13.01.2025 - R1"
```

### After (Correct)
```
attendance_id: "B1_EVE _EDMON_1"  ← UNIQUE identifier
round: 1
course_key: "B1 EVE ONLINE"       ← Course type (same for all rounds)
course_full_name: "B1 EVE ONLINE - EDMONTON - 13.01.2025 - R1"
course_book: "English File Intermediate 4th Edition (ISBN-13978-0194035910)"
```

## Import Process

### Option 1: PHP Import (Recommended)
```bash
# On server
cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app
php artisan migrate  # Run new migration first
php import_data_standalone.php
```

### Option 2: SQL Import (Backup)
```bash
# Generate SQL files (local)
python generate_sql_import.py

# Upload and run on server
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb < import_courses.sql
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb < import_students_leads.sql
```

## Expected Results

After import:
- ✅ **248 course offerings** with unique attendance_id values
- ✅ **Multiple rounds** of the same course type properly differentiated
- ✅ **Course book information** available for each offering
- ✅ **Enrollment linking** works correctly via attendance_id
- ✅ **No duplicate course errors**

## Verification Queries

```sql
-- Check for duplicate attendance_id (should return 0)
SELECT attendance_id, COUNT(*) as count 
FROM course_offerings 
GROUP BY attendance_id 
HAVING count > 1;

-- Count courses by round
SELECT round, COUNT(*) as count 
FROM course_offerings 
GROUP BY round 
ORDER BY round;

-- Sample of course data
SELECT attendance_id, round, course_key, course_full_name, course_book 
FROM course_offerings 
LIMIT 5;
```

## API Changes

### Before
```json
POST /api/course_offerings
{
  "course_key": "B1 EVE ONLINE",  // Must be unique
  "course_full_name": "..."
}
```

### After
```json
POST /api/course_offerings
{
  "attendance_id": "B1_EVE _EDMON_1",  // Must be unique
  "round": 1,
  "course_key": "B1 EVE ONLINE",       // Can repeat
  "course_book": "English File Intermediate 4th Edition...",
  "course_full_name": "..."
}
```

## Retool Impact

Update Retool queries to:
1. Display `attendance_id` as the primary course identifier
2. Use `round` to filter/group courses by round number
3. Show `course_book` in course details
4. Filter by `course_key` to see all rounds of a course type

Example Retool query:
```sql
-- Get all rounds of B1 Evening Online
SELECT attendance_id, round, course_full_name, start_date, course_book
FROM course_offerings 
WHERE course_key = 'B1 EVE ONLINE'
ORDER BY round, start_date;
```

## Files Changed

1. **database/migrations/20251118_000002_add_course_offering_identifiers.php** - New migration
2. **app/Models/CourseOffering.php** - Updated fillable fields
3. **app/Http/Controllers/CourseOfferingController.php** - Updated validation and fields
4. **specs/001-title-english-language/imports/manifest.courses.json** - Added new mappings
5. **import_data_standalone.php** - Updated for new fields
6. **database/seeds/ImportDataSeeder.php** - Updated for new fields
7. **IMPORT_GUIDE.md** - Updated documentation
8. **generate_sql_import.py** - New SQL generator script (created)
9. **import_courses.sql** - Generated SQL import file (created)
10. **import_students_leads.sql** - Generated SQL import file (created)

## Migration Instructions

### On Local Development
```bash
git pull origin feat/deploy-scripts-improvements
composer dump-autoload
php artisan migrate
```

### On Production Server
```bash
cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app
git pull origin feat/deploy-scripts-improvements
composer dump-autoload
php artisan migrate
php import_data_standalone.php
```

## Rollback (If Needed)

```bash
php artisan migrate:rollback --step=1
```

This will:
- Drop `attendance_id`, `round`, and `course_book` columns
- Restore the previous schema
- **Note**: You'll need to manually remove imported courses if they were added

---

**Date**: November 18, 2025  
**Author**: System Update  
**Version**: 1.1.0  
**Status**: ✅ Complete and tested
