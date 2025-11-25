# Historical Course Data Import - Implementation Summary

## 📋 Overview

Complete solution for importing historical course participation data (2022-2024) from Trello exports and linking to existing student records.

---

## ✅ Deliverables

### 1. **Specification Update**
**File:** `specs/001-title-english-language/spec.md`

**Added:**
- **FR-016:** Historical Course Import requirement
- Detailed data source description (Trello exports)
- Entity changes (is_historical flag, historical_metadata field)
- Import workflow specification
- Acceptance criteria (AC-016.1, AC-016.2, AC-016.3)
- Student matching strategy
- Course name normalization approach
- Implementation priority phases

### 2. **Workflow Documentation**
**File:** `HISTORICAL_COURSES_WORKFLOW.md` (40 sections, comprehensive guide)

**Contents:**
- **Goals & Non-Goals** - What's included/excluded
- **Database Schema Changes** - SQL migrations with explanations
- **Data Preparation** - Trello export instructions, data cleaning checklist
- **Import Process** - Step-by-step workflow with examples
- **Course Name Normalization** - Pattern matching rules
- **Post-Import Validation** - SQL queries, Retool checks
- **Testing Workflow** - 4-phase testing approach (sample → 2024 → 2023 → 2022)
- **Integration** - How it works with attendance sheets
- **Troubleshooting** - Common issues and fixes
- **Maintenance** - Adding more data, correcting errors

### 3. **Import Script**
**File:** `import_historical_courses.php` (450+ lines)

**Features:**
- ✅ **Flexible CSV parsing** - Auto-detects column names
- ✅ **Student matching** - Email (primary), name (fallback), creates cache for performance
- ✅ **Course name normalization** - Pattern-based mapping with fallback
- ✅ **Date parsing** - Handles multiple formats (US/EU), extracts years
- ✅ **Idempotent** - Safe to re-run, skips duplicates
- ✅ **Dry-run mode** - Validate before committing changes
- ✅ **Manual .env loading** - No Composer dependency
- ✅ **Detailed reporting** - Statistics, unmatched records export
- ✅ **Command-line options** - Configurable date formats, create missing students, verbose output

**Usage Examples:**
```bash
# Dry run (validation only)
php import_historical_courses.php trello_export.csv --dry-run

# Live import
php import_historical_courses.php trello_export.csv

# EU date format
php import_historical_courses.php trello_export.csv --date-format="d/m/Y"

# Create missing students
php import_historical_courses.php trello_export.csv --create-missing

# Verbose output
php import_historical_courses.php trello_export.csv --verbose
```

### 4. **Database Migration**
**File:** `database/migrations/20251119_000001_add_historical_course_fields.php`

**Changes:**
```sql
-- course_offerings table
ALTER TABLE course_offerings ADD COLUMN is_historical BOOLEAN DEFAULT FALSE;
CREATE INDEX idx_is_historical ON course_offerings(is_historical);

-- enrollments table
ALTER TABLE enrollments ADD COLUMN historical_metadata JSON NULL;

-- Optimization indexes
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_student_course ON enrollments(student_id, course_offering_id);
```

**Purpose:**
- `is_historical` - Filters historical courses from active course dropdowns
- `historical_metadata` - Preserves original Trello data as JSON
- Indexes - Speeds up student matching and duplicate detection

---

## 🔄 Workflow Summary

### Step 1: Prepare Database
```bash
# SSH to server
ssh u5021d9810@u5021d9810.ssh.webspace-data.io

# Run migration
cd domains/mixtreelangdb.nl/mtl_app
php artisan migrate
```

### Step 2: Export from Trello
1. Open Trello board with historical data
2. Board Menu → Print and Export → Export as CSV
3. Download file

### Step 3: Clean Data
- Verify email addresses valid
- Check course names for typos
- Standardize date formats
- Remove duplicate rows

### Step 4: Upload & Import
```bash
# Upload CSV
scp trello_historical.csv u5021d9810@u5021d9810.ssh.webspace-data.io:~/

# Upload script
scp import_historical_courses.php u5021d9810@u5021d9810.ssh.webspace-data.io:domains/mixtreelangdb.nl/mtl_app/

# SSH and test
ssh u5021d9810@u5021d9810.ssh.webspace-data.io
cd domains/mixtreelangdb.nl/mtl_app

# Dry run first
php import_historical_courses.php ~/trello_historical.csv --dry-run

# Review output, then run for real
php import_historical_courses.php ~/trello_historical.csv
```

### Step 5: Validate
```sql
-- Check historical courses created
SELECT COUNT(*) FROM course_offerings WHERE is_historical = TRUE;

-- Check enrollments created
SELECT COUNT(*) FROM enrollments e
JOIN course_offerings c ON e.course_offering_id = c.id
WHERE c.is_historical = TRUE;

-- Check previous_courses populated
SELECT COUNT(*) FROM students 
WHERE previous_courses IS NOT NULL AND previous_courses != '';

-- Sample student check
SELECT s.email, s.previous_courses, COUNT(e.id) as historical_count
FROM students s
LEFT JOIN enrollments e ON s.id = e.student_id
LEFT JOIN course_offerings c ON e.course_offering_id = c.id
WHERE c.is_historical = TRUE
GROUP BY s.id
LIMIT 10;
```

### Step 6: Resolve Unmatched
1. Review `historical_import_unmatched_YYYY-MM-DD.csv`
2. Fix email mismatches in database or CSV
3. Re-run import (idempotent - safe)

---

## 🎯 Key Features

### Student Matching Intelligence
- **Primary:** Email exact match (case-insensitive)
- **Secondary:** First + Last name match
- **Ambiguity Handling:** Exports unmatched records for manual resolution
- **Performance:** Caches matched students to avoid redundant queries

### Course Name Normalization
```php
// Example mappings
'/A2.*Morning.*Enschede/i' => 'A2 PR_MORN_ENSCH'
'/B1.*Evening.*Online/i' => 'B1 PR_EVE_ONLINE'
```
- Handles variations in historical course naming
- Extensible pattern system
- Fallback to original name if no pattern matches

### Date Parsing Flexibility
- Tries multiple formats: m/d/Y, d/m/Y, Y-m-d
- Extracts year from partial dates
- Configurable via `--date-format` option

### Idempotent Design
- Checks for existing enrollments before creating
- Skips duplicates automatically
- Safe to re-run after fixing data issues

---

## 📊 Expected Results

### Import Statistics Example
```
Summary:
  Total rows processed: 350
  Students matched (email): 280
  Students matched (name): 40
  Students unmatched: 30
  Courses created: 45
  Enrollments created: 320
  Enrollments skipped (duplicates): 0
  Previous courses updated: 300
  
Time: 18.5 seconds
```

### Database Impact
- **course_offerings:** +40-60 historical courses (2022-2024)
- **enrollments:** +300-500 historical enrollment records
- **students.previous_courses:** 250-350 students updated

### Student Profile Display (After Import)
```
Student: Maria González (maria.gonzalez@email.com)

Enrollment History:
  ✓ A1 Intensive (Completed) - 2022-06-15 to 2022-07-15
  ✓ A2 Morning Enschede (Completed) - 2023-01-09 to 2023-04-12
  ✓ B1 Afternoon Online (Completed) - 2023-09-11 to 2023-12-15
  → B2 Evening Online (Active) - 2024-10-21 to 2025-01-31

Previous Courses: A1 Intensive (2022), A2 Morning Enschede (2023), B1 Afternoon Online (2023)
```

---

## 🔗 Integration Points

### With Attendance Sheets
- `previous_courses` field auto-populated when creating new attendance sheet
- Student name italicized if has previous courses
- Teachers see student history immediately

### With Admin Interface (Retool)
- Historical enrollments visible in student enrollment list
- Filter option: "Show Historical Courses"
- Enrollment metadata includes original Trello data

### With Future Features
- Certificate generation: Can retroactively generate for historical courses (>=80% completion)
- Analytics: Student retention rates across multiple courses
- Marketing: Identify returning students for targeted campaigns

---

## ⚠️ Important Notes

### What This Import DOES
✅ Creates historical course records  
✅ Links students to past courses  
✅ Populates `previous_courses` field  
✅ Preserves Trello metadata  
✅ Enables admin view of complete history  

### What This Import DOES NOT
❌ Import historical attendance (session-level records)  
❌ Import historical invoices/payments  
❌ Import historical certificates  
❌ Modify current/active courses  
❌ Change student contact information  

---

## 📁 Files Created

1. **HISTORICAL_COURSES_WORKFLOW.md** - Complete workflow guide
2. **import_historical_courses.php** - Import script
3. **database/migrations/20251119_000001_add_historical_course_fields.php** - Schema changes
4. **specs/001-title-english-language/spec.md** - Updated with FR-016
5. **HISTORICAL_COURSES_IMPLEMENTATION.md** - This summary document

---

## 🚀 Next Steps

### Immediate
1. [ ] Review specification update (FR-016)
2. [ ] Run database migration on server
3. [ ] Export sample historical data from Trello (10-20 records)
4. [ ] Test import with sample data (dry-run)
5. [ ] Validate results in database and Retool

### Phase 1: 2024 Courses
6. [ ] Export all 2024 courses from Trello
7. [ ] Clean and validate data
8. [ ] Run dry-run import
9. [ ] Review unmatched records
10. [ ] Run live import
11. [ ] Spot-check 10% of students

### Phase 2: 2023 Courses
12. [ ] Repeat Phase 1 for 2023 data

### Phase 3: 2022 Courses
13. [ ] Repeat Phase 1 for 2022 data

### Future Enhancements
- [ ] Automated Trello → Database sync (webhook-based)
- [ ] Student-facing portal to view course history
- [ ] Historical attendance retroactive entry (if needed)
- [ ] Link to historical invoices (separate import workflow)

---

## 📞 Support

**Questions:**
- Review HISTORICAL_COURSES_WORKFLOW.md for detailed guidance
- Check import script output for specific errors
- Review SQL validation queries in workflow guide

**Common Issues:**
- Email mismatch → Update student.email or CSV
- Course name not normalizing → Add pattern to $courseNameMappings
- Duplicate enrollments → Already exists, skip is correct behavior
- Unmatched students → Export to CSV for manual review

**Script Help:**
```bash
php import_historical_courses.php --help
```

---

## ✨ Summary

Complete solution delivered for importing 2022-2024 historical course data from Trello. The system:

- **Preserves historical relationships** without requiring attendance data
- **Integrates seamlessly** with existing student profiles and attendance workflow
- **Handles edge cases** (missing emails, name ambiguity, date variations)
- **Provides visibility** to administrators for complete student history
- **Supports future expansion** for certificates, analytics, and student portals

**Ready to deploy!** Start with sample data dry-run, then progressively import 2024 → 2023 → 2022.
