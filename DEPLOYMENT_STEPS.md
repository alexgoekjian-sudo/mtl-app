# 🚀 Deployment Steps - Course Schema Update

## Quick Summary
You requested changes to the course table structure. Here's what was done and how to deploy it.

## What Changed

### Database Schema
1. **attendance_id** (new) - Unique identifier from CSV's ATTENDANCE_COURSE_NAME (e.g., "B1_EVE _EDMON_1")
2. **round** (new) - Round number (1, 2, 3, etc.) from CSV's ROUND column
3. **course_book** (new) - Textbook info with ISBN from CSV's COURSE_BOOK column
4. **course_key** (changed) - Now non-unique, represents course type (e.g., "B1 EVE ONLINE")

### Why?
The original `course_key` was NOT unique in your CSV. Multiple rounds had the same course type name. Now:
- `attendance_id` = unique identifier for each specific offering
- `course_key` = course type (same for all rounds)
- `round` = which round it is

## Deployment Steps

### Step 1: Pull Latest Code
```bash
ssh into your server
cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app
git pull origin feat/deploy-scripts-improvements
```

### Step 2: Run Database Migration
```bash
composer dump-autoload
php artisan migrate
```

Expected output:
```
Migrated: 20251118_000002_add_course_offering_identifiers
```

### Step 3: Import Data

**Option A: PHP Script (Recommended)**
```bash
php import_data_standalone.php
```

Expected results:
- ✓ Courses imported: 248
- ✓ Students created: 87
- ✓ Enrollments created: ~80

**Option B: SQL Import (If PHP doesn't work)**
```bash
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb < import_courses.sql
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb < import_students_leads.sql
```

### Step 4: Verify Import
```bash
# Quick check via API
curl -H "Authorization: Bearer A317F31717358A2C316D9758857028526ABD0BC53D4399FA" \
  https://mixtreelangdb.nl/api/retool/course_offerings?limit=5 | jq
```

Should show courses with `attendance_id`, `round`, `course_key`, and `course_book` fields.

## What You Can Do Now

### 1. Query by Course Type (All Rounds)
```sql
SELECT attendance_id, round, course_full_name, start_date, course_book
FROM course_offerings 
WHERE course_key = 'B1 EVE ONLINE'
ORDER BY round, start_date;
```

### 2. Filter by Round Number
```sql
SELECT course_key, COUNT(*) as offerings
FROM course_offerings 
WHERE round = 1
GROUP BY course_key;
```

### 3. Get Textbook Information
```sql
SELECT DISTINCT course_key, course_book
FROM course_offerings 
WHERE course_book IS NOT NULL
ORDER BY course_key;
```

### 4. Link Enrollments to Specific Offerings
Now enrollments link to `attendance_id` (unique) instead of `course_key` (non-unique), so you know exactly which course offering a student attended.

## Files Created

| File | Purpose |
|------|---------|
| `COURSE_SCHEMA_CHANGES.md` | Complete technical documentation |
| `PROJECT_SUMMARY.md` | Overall project status and next steps |
| `generate_sql_import.py` | Python script to generate SQL from JSON |
| `import_courses.sql` | SQL INSERT statements (248 courses) |
| `import_students_leads.sql` | SQL INSERT statements (87 students) |
| `database/migrations/20251118_000002_add_course_offering_identifiers.php` | Migration file |

## Verification Queries

After deployment, run these to verify everything worked:

```sql
-- Should return 0 (no duplicates)
SELECT attendance_id, COUNT(*) 
FROM course_offerings 
GROUP BY attendance_id 
HAVING COUNT(*) > 1;

-- Should show round distribution
SELECT round, COUNT(*) as count 
FROM course_offerings 
GROUP BY round;

-- Should show sample data
SELECT attendance_id, round, course_key, course_book 
FROM course_offerings 
LIMIT 3;
```

## Rollback (If Needed)

If something goes wrong:
```bash
php artisan migrate:rollback --step=1
```

This removes the new columns and restores the previous schema.

## Support

- Full details: See `COURSE_SCHEMA_CHANGES.md`
- Import guide: See `IMPORT_GUIDE.md`
- Project overview: See `PROJECT_SUMMARY.md`

---

**Commit**: 349010e  
**Branch**: feat/deploy-scripts-improvements  
**Date**: November 18, 2025
