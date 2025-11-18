# ⚠️ IMPORTANT: Run Migration First!

## Error You're Seeing

```
PHP Fatal error: Column not found: 1054 Unknown column 'attendance_id'
```

## Solution

You need to run the database migration BEFORE running the import script.

## Steps to Fix

### 1. Run the Migration
```bash
cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app
php artisan migrate
```

Expected output:
```
Migrating: 20251118_000002_add_course_offering_identifiers
Migrated:  20251118_000002_add_course_offering_identifiers (0.XX seconds)
```

### 2. Verify Migration Worked
```bash
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb -e "DESCRIBE course_offerings;" | grep attendance_id
```

Should show:
```
attendance_id | varchar(255) | NO | UNI | NULL |
```

### 3. Now Run Import
```bash
php import_data_standalone.php
```

## If Migration Fails

If `php artisan migrate` gives an error, try running the migration SQL directly:

```bash
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb
```

Then paste this SQL:
```sql
ALTER TABLE course_offerings 
ADD COLUMN attendance_id VARCHAR(255) NOT NULL AFTER id,
ADD COLUMN round INT DEFAULT 1 NOT NULL AFTER attendance_id,
ADD COLUMN course_book TEXT NULL AFTER book_included,
ADD UNIQUE INDEX course_offerings_attendance_id_unique (attendance_id);
```

Then try the import again.

## Alternative: Use SQL Import Instead

If you prefer to skip the PHP import entirely:

```bash
# Just run the migration first
php artisan migrate

# Then use SQL import
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb < import_courses.sql
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb < import_students_leads.sql
```

---

**Bottom line**: Always run migrations before importing data!
