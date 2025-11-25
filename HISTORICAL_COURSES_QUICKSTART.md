# Historical Course Import - Quick Start

## 🚀 Complete Setup in 5 Steps

### 1. Run Migration
```bash
ssh u5021d9810@u5021d9810.ssh.webspace-data.io
cd domains/mixtreelangdb.nl/mtl_app
php artisan migrate
```

### 2. Export Trello Data
- Board Menu → Print and Export → Export as CSV
- Download file (e.g., `Trello_Historical_2024.csv`)

### 3. Upload Files
```bash
scp import_historical_courses.php u5021d9810@u5021d9810.ssh.webspace-data.io:domains/mixtreelangdb.nl/mtl_app/
scp Trello_Historical_2024.csv u5021d9810@u5021d9810.ssh.webspace-data.io:~/
```

### 4. Dry Run (Test)
```bash
ssh u5021d9810@u5021d9810.ssh.webspace-data.io
cd domains/mixtreelangdb.nl/mtl_app
php import_historical_courses.php ~/Trello_Historical_2024.csv --dry-run
```

Review output, check unmatched records.

### 5. Live Import
```bash
php import_historical_courses.php ~/Trello_Historical_2024.csv
```

---

## 📋 CSV Format Expected

**Required Columns:**
- First name / First Name / FirstName
- Surname / Last name / Last Name / LastName  
- Course Name / Card Name / Title

**Optional but Recommended:**
- Email address / Email
- Level
- Start Date
- End Date
- Location
- Notes

**Example Row:**
```
"John","Doe","john.doe@example.com","A2 Morning Enschede 2023","A2","01/09/2023","01/12/2023","Enschede"
```

---

## ✅ Validation Queries

```sql
-- Historical courses created
SELECT COUNT(*) FROM course_offerings WHERE is_historical = TRUE;

-- Historical enrollments
SELECT COUNT(*) FROM enrollments e
JOIN course_offerings c ON e.course_offering_id = c.id
WHERE c.is_historical = TRUE;

-- Students with history
SELECT COUNT(*) FROM students 
WHERE previous_courses IS NOT NULL AND previous_courses != '';

-- Sample student
SELECT s.email, s.previous_courses 
FROM students s 
WHERE previous_courses IS NOT NULL 
LIMIT 5;
```

---

## 🔧 Common Options

```bash
# EU date format (DD/MM/YYYY instead of MM/DD/YYYY)
php import_historical_courses.php file.csv --date-format="d/m/Y"

# Create students not found in database
php import_historical_courses.php file.csv --create-missing

# Verbose output (see each row)
php import_historical_courses.php file.csv --verbose

# Combine options
php import_historical_courses.php file.csv --dry-run --verbose
```

---

## ⚠️ Troubleshooting

**"Student not found"**
→ Check email in database matches CSV  
→ Use `--create-missing` to create new students

**"Multiple students with same name"**
→ Add email column to CSV for exact matching  
→ Manually merge duplicate students in database first

**"Invalid date format"**
→ Use `--date-format="d/m/Y"` for EU dates  
→ Or convert CSV to YYYY-MM-DD format

**"Course name not normalized"**
→ Script uses original name as fallback (works fine)  
→ Optionally add mapping pattern to script

---

## 📊 Expected Output

```
Historical Course Import
CSV File: Trello_Historical_2024.csv
Date Format: m/d/Y

Column mapping:
  First Name: First name
  Last Name: Surname
  Email: Email address
  Course Name: Course Name
  
Processing rows...

============================================================
IMPORT COMPLETE
============================================================

Summary:
  Total rows processed: 120
  Students matched (email): 100
  Students matched (name): 15
  Students unmatched: 5
  Courses created: 15
  Enrollments created: 115
  Previous courses updated: 110

Unmatched records exported to: historical_import_unmatched_2025-11-19.csv

Done!
```

---

## 📖 Full Documentation

- **HISTORICAL_COURSES_WORKFLOW.md** - Complete guide
- **HISTORICAL_COURSES_IMPLEMENTATION.md** - Summary & specs
- **specs/001-title-english-language/spec.md** - FR-016 requirement

---

## 🎯 What Gets Created

**course_offerings table:**
- Historical courses with `is_historical = TRUE`
- Won't appear in teacher course dropdowns
- Visible in admin student history view

**enrollments table:**
- Status: 'completed'
- historical_metadata: JSON with original Trello data

**students table:**
- previous_courses field updated
- Format: "Course Name (Year), Course Name (Year)"

**Result:**
→ Complete student course history  
→ Auto-populated "Previous Courses" on attendance sheets  
→ Student name italicized if has previous courses
