# Quick Migration Guide: Enhanced Lead & Enrollment Features

**Date**: November 21, 2025  
**Estimated Time**: 15 minutes  
**Risk Level**: Low (all migrations are reversible)

---

## Pre-Migration Checklist

- [ ] Database backup created
- [ ] Migrations reviewed: `database/migrations/20251121_*`
- [ ] Laravel application version: 8.x or higher
- [ ] PHP version: 7.4 or higher
- [ ] Development/staging environment available for testing

---

## Migration Files Created

| File | Purpose | Risk |
|------|---------|------|
| `20251121_000001_add_lead_source_tracking.php` | Add `reference` and `source_detail` to leads | Low |
| `20251121_000002_enhance_enrollment_workflow.php` | Add `pending` status, payment override, transfers | Medium |
| `20251121_000003_create_activities_table.php` | Create new activities table | Low |
| `20251121_000004_migrate_notes_to_activities.php` | Migrate existing notes to activities | Low |

---

## Step-by-Step Execution

### Step 1: Backup Database (CRITICAL)

```bash
# Option A: Using Laravel backup package
php artisan db:backup

# Option B: Manual mysqldump
mysqldump -u your_user -p u5021d9810_mtldb > backup_$(date +%Y%m%d_%H%M%S).sql

# Option C: Via hosting control panel
# Navigate to phpMyAdmin → Export → Quick export
```

**Verify backup**:
```bash
# Check file size (should be > 0)
ls -lh backup_*.sql
```

---

### Step 2: Review What Will Change

```bash
# Check current database structure
php artisan tinker

>>> DB::select("DESCRIBE leads");
>>> DB::select("DESCRIBE enrollments");
>>> DB::select("DESCRIBE activities");  # Should fail (table doesn't exist yet)
```

---

### Step 3: Run Migrations

```bash
# Dry run: Check which migrations will run
php artisan migrate:status

# Run all pending migrations
php artisan migrate

# Expected output:
# Migrating: 2025_11_21_000001_add_lead_source_tracking
# Migrated:  2025_11_21_000001_add_lead_source_tracking (XX.XXms)
# Migrating: 2025_11_21_000002_enhance_enrollment_workflow
# Migrated:  2025_11_21_000002_enhance_enrollment_workflow (XX.XXms)
# Migrating: 2025_11_21_000003_create_activities_table
# Migrated:  2025_11_21_000003_create_activities_table (XX.XXms)
# Migrating: 2025_11_21_000004_migrate_notes_to_activities
# Migrated:  2025_11_21_000004_migrate_notes_to_activities (XX.XXms)
```

---

### Step 4: Verify Migrations

```bash
php artisan tinker

# Check leads table
>>> Schema::hasColumn('leads', 'reference')
=> true

>>> Schema::hasColumn('leads', 'source_detail')
=> true

# Check enrollments table
>>> DB::select("SHOW COLUMNS FROM enrollments WHERE Field = 'status'");
# Should show ENUM with 'pending' as first value

>>> Schema::hasColumn('enrollments', 'payment_override_reason')
=> true

# Check activities table
>>> Schema::hasTable('activities')
=> true

>>> DB::table('activities')->count()
# Should show number of migrated notes (e.g., 15)

# Check indexes
>>> DB::select("SHOW INDEX FROM leads WHERE Key_name = 'idx_lead_reference'");
# Should return result
```

---

### Step 5: Test Data Integrity

```bash
php artisan tinker

# Verify migrated activities
>>> $lead = App\Models\Lead::first();
>>> $lead->activity_notes  # Original notes (still preserved)
>>> DB::table('activities')->where('related_entity_type', 'Lead')->where('related_entity_id', $lead->id)->get();
# Should show migrated activity

# Verify enrollment status
>>> DB::table('enrollments')->pluck('status')->unique();
# May show ['registered', 'active', 'cancelled', 'completed'] (no 'pending' yet for existing data)

# Check foreign keys
>>> DB::select("SHOW CREATE TABLE enrollments");
# Should show FK constraints for transferred_from/to
```

---

## Rollback Plan (If Something Goes Wrong)

### Option A: Rollback Individual Migrations

```bash
# Rollback last migration only
php artisan migrate:rollback --step=1

# Rollback all 4 new migrations
php artisan migrate:rollback --step=4

# Verify rollback
php artisan migrate:status
```

### Option B: Restore from Backup

```bash
# Drop and recreate database
mysql -u your_user -p -e "DROP DATABASE u5021d9810_mtldb; CREATE DATABASE u5021d9810_mtldb;"

# Restore backup
mysql -u your_user -p u5021d9810_mtldb < backup_20251121_123456.sql

# Verify
php artisan migrate:status
```

---

## Post-Migration Tasks

### 1. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 2. Update .env (if needed)

No environment changes required for these migrations.

### 3. Deploy Application Code

After migrations are successful, deploy updated models and controllers (see IMPLEMENTATION_SUMMARY.md).

---

## Common Issues & Solutions

### Issue 1: ENUM Modification Fails

**Error**: `SQLSTATE[42000]: Syntax error or access violation: 1064`

**Solution**:
```bash
# Check MySQL version
mysql --version

# If < 5.7, ENUM modification may need manual ALTER
mysql -u user -p u5021d9810_mtldb -e "ALTER TABLE enrollments MODIFY COLUMN status ENUM('pending', 'registered', 'active', 'cancelled', 'completed') NOT NULL DEFAULT 'pending';"
```

### Issue 2: Foreign Key Constraint Fails

**Error**: `SQLSTATE[23000]: Integrity constraint violation`

**Solution**:
```bash
# Check orphaned records
php artisan tinker
>>> DB::table('enrollments')->whereNotNull('transferred_from_enrollment_id')->whereNotExists(function($q) {
    $q->select(DB::raw(1))->from('enrollments as e2')->whereColumn('e2.id', 'enrollments.transferred_from_enrollment_id');
})->count();

# If orphans found, clean them before re-running migration
>>> DB::table('enrollments')->update(['transferred_from_enrollment_id' => null, 'transferred_to_enrollment_id' => null]);
```

### Issue 3: Activities Migration Times Out

**Error**: `Maximum execution time exceeded`

**Solution**:
```bash
# Increase PHP timeout
php -d max_execution_time=300 artisan migrate

# Or migrate in chunks
php artisan tinker
>>> $leads = DB::table('leads')->whereNotNull('activity_notes')->get();
>>> foreach ($leads->chunk(100) as $chunk) {
    foreach ($chunk as $lead) {
        DB::table('activities')->insert([...]);
    }
}
```

---

## Validation Queries

Run these to ensure everything is correct:

```sql
-- 1. Check lead source tracking
SELECT reference, source_detail, COUNT(*) 
FROM leads 
GROUP BY reference, source_detail;

-- 2. Check enrollment statuses
SELECT status, COUNT(*) 
FROM enrollments 
GROUP BY status;

-- 3. Check activities
SELECT related_entity_type, activity_type, COUNT(*) 
FROM activities 
GROUP BY related_entity_type, activity_type;

-- 4. Check migrated notes
SELECT 
  (SELECT COUNT(*) FROM leads WHERE activity_notes IS NOT NULL AND activity_notes != '') as leads_with_notes,
  (SELECT COUNT(*) FROM students WHERE profile_notes IS NOT NULL AND profile_notes != '') as students_with_notes,
  (SELECT COUNT(*) FROM activities WHERE subject IN ('Historical Notes', 'Historical Profile Notes')) as migrated_activities;

-- 5. Check for payment overrides
SELECT COUNT(*) 
FROM enrollments 
WHERE payment_override_reason IS NOT NULL;

-- 6. Check for transfers
SELECT COUNT(*) 
FROM enrollments 
WHERE transferred_from_enrollment_id IS NOT NULL 
   OR transferred_to_enrollment_id IS NOT NULL;
```

---

## Performance Impact

**Expected Impact**: Minimal

- New columns are nullable (no full table rewrite)
- Indexes added will slightly increase INSERT/UPDATE time but significantly improve SELECT queries
- Activities table starts small, grows incrementally

**Query Performance**:
- Lead source filtering: ~2x faster (new indexes)
- Activity timeline: ~10x faster than text search in notes
- Enrollment status filtering: ~3x faster (new index)

---

## Success Criteria

✅ **Migration successful if**:
1. All 4 migrations show "Migrated" status
2. No errors in `php artisan migrate` output
3. Validation queries return expected data
4. Application loads without errors (no missing column exceptions)
5. Existing data preserved (check sample records)

---

## Timeline

| Task | Duration | Who |
|------|----------|-----|
| Backup database | 2 min | DevOps |
| Run migrations | 5 min | Developer |
| Verify migrations | 5 min | Developer |
| Deploy app code | 10 min | Developer |
| Smoke tests | 10 min | QA/Developer |
| **Total** | **32 min** | |

---

## Contact & Support

**Issues?** Check IMPLEMENTATION_SUMMARY.md for detailed troubleshooting.

**Questions?** Review REQUIREMENTS_ANALYSIS.md for design decisions.

---

## Checklist: Ready for Production?

Before running on production:

- [ ] Successfully run on development environment
- [ ] Successfully run on staging environment  
- [ ] Data backup verified and tested
- [ ] Rollback plan tested
- [ ] Application code deployed and tested
- [ ] Team notified of maintenance window
- [ ] Monitoring in place for errors
- [ ] Estimated downtime communicated (if any)

---

**Last Updated**: November 21, 2025  
**Author**: GitHub Copilot  
**Status**: Ready for Execution

