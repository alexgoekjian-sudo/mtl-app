# Teacher Attendance System

Simple web-based attendance tracking interface for teachers (non-Retool).

## ✅ Current Status (Updated: November 19, 2025)

**FULLY OPERATIONAL** - All core features implemented and tested:
- ✅ Google Sheets export to CSV functionality
- ✅ PHP import scripts (attendance data + session generation)
- ✅ Teacher attendance grid with date headers, student data, checkboxes
- ✅ Mid-level dropdown at halfway point
- ✅ Attendance percentages calculated correctly
- ✅ Pre-generation of all sessions for courses (shows headers even without students)
- ✅ Date format issues resolved (ISO timestamp → YYYY-MM-DD)
- ✅ All schema compatibility issues fixed

## Components

### 1. Web Interface
**File:** `public/teacher-attendance.html`

**Features:**
- Course selector dropdown
- Grid-based attendance view with sticky columns (Name, Country, Start Level)
- Date headers based on course schedule (automatically generated)
- Checkbox attendance marking (auto-save)
- Mid-level dropdown at schedule halfway point
- Teacher Notes and Previous Courses textareas with auto-resize
- Student email column (toggle show/hide, bulk copy)
- Attendance percentage per student
- Midpoint reminder when mid-level evaluations are due

**Grid Columns:**
1. **Student Name** (sticky) - Italicized if Previous Courses exists
2. **Country** (sticky) - From country_of_origin field
3. **Start Level** (sticky) - From initial_level field
4. **Date Columns** - One per session date (checkboxes for attendance)
5. **Mid Level** - Dropdown at halfway point in schedule
6. **Teacher Notes** - Auto-resizing textarea
7. **Previous Courses** - Auto-resizing textarea
8. **Student Email** - Toggleable visibility
9. **Attendance %** - Calculated from attendance records

**Access:**
Navigate to: `https://mixtreelangdb.nl/teacher-attendance.html`

### 2. API Endpoints
**Controller:** `app/Http/Controllers/TeacherAttendanceController.php`

**Routes (all require authentication token):**
- `GET /api/teacher/courses` - List all courses with attendance_id
- `GET /api/teacher/courses/{id}` - Get course details
- `GET /api/teacher/courses/{id}/attendance?date=YYYY-MM-DD` - Get students enrolled in course with country/level data
- `POST /api/teacher/courses/{id}/sessions` - Create new session
- `POST /api/teacher/courses/{id}/attendance` - Save attendance (bulk update)
- `GET /api/teacher/courses/{id}/summary` - Course info + all sessions (generates date headers)
- `GET /api/teacher/courses/{courseId}/students/{studentId}/attendance` - Student attendance history with statistics

**Important Implementation Notes:**
- **Date Handling:** Controller uses `$session->getAttributes()['date']` to return raw YYYY-MM-DD format (bypasses Laravel date casting that produces ISO timestamps)
- **Country Field:** API maps `country_of_origin` to `country` for frontend compatibility
- **Enrollment Data:** Joins enrollments table to get `mid_course_level` and `mid_course_notes`
- **All queries use Carbon::now()** instead of Laravel's now() helper

### 3. Google Sheets Export Script
**File:** `exportAttendanceSheets.gs` (Google Apps Script)

Export all attendance sheets from Google Sheets workbook to CSV files.

**Installation:**
1. Open Google Sheets workbook
2. Extensions → Apps Script
3. Paste exportAttendanceSheets.gs code
4. Save and refresh spreadsheet
5. New menu appears: "Attendance Export"

**Usage:**
1. Click "Attendance Export" → "Export All Sheets to CSV"
2. Script creates timestamped folder in Google Drive
3. Exports each sheet (except "Template") as CSV
4. Creates summary file with export log

**Features:**
- Skips template sheets automatically
- Proper CSV escaping for commas, quotes, newlines
- Creates Drive folder: "MTL_Attendance_Export_YYYYMMDD_HHMMSS"
- Generates summary.txt with export statistics
- Custom menu for easy access

### 4. PHP Import Scripts

#### a) Main Attendance Import
**File:** `import_google_attendance.php`

Import Google Sheets CSV exports into database with full schedule generation.

**CSV Format:**
```
Student Name, Student Email, Dates Present, Initial Level, Mid Level, Teacher Notes, Previous Courses, Trello Card ID, Country
```

**Usage:**
```bash
# Import single file
php import_google_attendance.php "path/to/file.csv"

# Import entire directory
php import_google_attendance.php "path/to/MTL_Attendance_Export/"
```

**Features:**
- **No Composer Required** - Manual .env parsing
- Extracts attendance_id from filename pattern: `- ATTENDANCE_ID.csv`
- Finds course by attendance_id
- **Generates COMPLETE course schedule** from start_date + end_date + schedule JSON
- Creates ALL sessions for entire course (not just attended dates)
- Parses multiple date formats: ISO (2025-10-22), M/D (10/22), month name (Oct 22)
- Creates/updates students with: initial_level, country_of_origin, previous_courses, profile_notes
- Creates/updates enrollments with: status='active', mid_course_level, mid_course_notes
- Creates attendance_records only for dates listed in "Dates Present"
- **Idempotent** - Safe to re-run (uses ON DUPLICATE KEY UPDATE)

**Schedule JSON Format:**
```json
{"days":["M","T","Th","F"],"time_range":"12:30-15:00"}
```

**Day Abbreviations:** M=Monday, T=Tuesday, W=Wednesday, Th=Thursday, F=Friday, Sa=Saturday, Su=Sunday

**Output Example:**
```
Course found: A2 PR_MORN_EDMON_7 (ID: 123)
Generated 60 session dates for full course schedule
Processing row 1: John Doe
  - Student created/updated
  - Enrollment created (status: active)
  - Parsed 12 attendance dates
  - Created 12 attendance records
Total: 45 students processed, 540 attendance records
```

#### b) Session Pre-Generation
**File:** `create_all_sessions.php`

Pre-generate sessions for ALL courses based on schedule JSON (enables grid headers for courses without students).

**Usage:**
```bash
php create_all_sessions.php
```

**Features:**
- Processes ALL courses with start_date + end_date + schedule
- Uses same generateCourseSchedule() logic as import script
- **Idempotent** - Checks for existing sessions before creating
- Reports: courses processed, sessions created, sessions already existed
- Handles missing start_time/end_time/teacher_id fields gracefully

**Output Example:**
```
Processing course: A2 PR_MORN_EDMON_7
  ✓ Created 60 sessions
  
Processing course: B1 AFT_ONLINE_3
  - 45 sessions already exist
  ✓ Created 15 sessions

Summary: 25 courses processed, 800 sessions created, 1200 already existed
```

#### c) Session Cleanup
**File:** `cleanup_sessions.php`

Delete all sessions and attendance for a course to allow clean re-import.

**Usage:**
```bash
php cleanup_sessions.php ATTENDANCE_ID
```

**Features:**
- Interactive confirmation before deletion
- Deletes in correct order (attendance_records first, then sessions)
- Reports counts of deleted records
- Safety check prevents accidental deletions

## Authentication

The teacher interface uses token-based authentication. Tokens are stored in `localStorage`.

**Login:**
Teachers must obtain an API token from an administrator, or use the login endpoint:
```bash
curl -X POST https://yourdomain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"teacher@example.com","password":"password"}'
```

Response includes `token` which should be saved.

**Setting Token in Browser:**
Open browser console on teacher-attendance.html and run:
```javascript
localStorage.setItem('auth_token', 'YOUR_TOKEN_HERE');
location.reload();
```

## Database Schema

### students
- `id` - Primary key
- `first_name`, `last_name`, `email` (UNIQUE)
- `initial_level` - Starting level (A1-, A1, A1+, A2-, etc.)
- `current_level` - Current level (updated during course)
- `country_of_origin` - Student's country
- `previous_courses` - TEXT (comma-separated list of past courses)
- `profile_notes` - TEXT (general student notes)

### enrollments
- `id` - Primary key
- `student_id` - Foreign key to students
- `course_offering_id` - Foreign key to course_offerings
- `status` - ENUM('active', 'dropped', 'completed') - **MUST be 'active' for grid display**
- `mid_course_level` - Level at midpoint evaluation
- `mid_course_notes` - TEXT (teacher notes from midpoint)

### sessions
- `id` - Primary key
- `course_offering_id` - Foreign key to course_offerings
- `date` - DATE (YYYY-MM-DD format) - **Column name is 'date' not 'session_date'**
- `start_time`, `end_time` - TIME (nullable)
- `teacher_id` - Foreign key to users (nullable)
- `status` - ENUM('scheduled', 'completed', 'cancelled')

### attendance_records
- `id` - Primary key
- `session_id` - Foreign key to sessions
- `student_id` - Foreign key to students
- `status` - ENUM('present', 'late', 'absent', 'excused')
- `note` - TEXT (optional teacher notes)
- `recorded_by` - Foreign key to users (who marked attendance)
- `recorded_at` - TIMESTAMP
- **UNIQUE KEY** on (session_id, student_id) - prevents duplicates

### course_offerings
- `id` - Primary key
- `attendance_id` - VARCHAR (unique identifier used in CSV filenames)
- `course_full_name` - Course display name
- `start_date`, `end_date` - DATE
- `schedule` - JSON - Format: `{"days":["M","T","Th","F"],"time_range":"12:30-15:00"}`
- `start_time`, `end_time` - TIME (nullable)
- `teacher_id` - Foreign key to users (nullable)

## Deployment Workflow

### Server Details
- **Host:** u5021d9810.ssh.webspace-data.io
- **User:** u5021d9810
- **Web Root:** ~/public_html/
- **Laravel App:** ~/domains/mixtreelangdb.nl/mtl_app/
- **Database:** u5021d9810_mtldb (MariaDB)
- **Import Directory:** ~/MTL_Attendance_Export/

### Complete Deployment Steps

#### 1. Export from Google Sheets
```
1. Open Google Sheets workbook
2. Attendance Export → Export All Sheets to CSV
3. Download exported folder from Google Drive
4. Extract to local directory (e.g., MTL_Attendance_Export)
```

#### 2. Upload Files to Server
```bash
# Upload scripts
scp import_google_attendance.php u5021d9810@u5021d9810.ssh.webspace-data.io:domains/mixtreelangdb.nl/mtl_app/
scp create_all_sessions.php u5021d9810@u5021d9810.ssh.webspace-data.io:domains/mixtreelangdb.nl/mtl_app/
scp cleanup_sessions.php u5021d9810@u5021d9810.ssh.webspace-data.io:domains/mixtreelangdb.nl/mtl_app/

# Upload CSV files
scp -r MTL_Attendance_Export u5021d9810@u5021d9810.ssh.webspace-data.io:~/

# Upload controller (if updated)
scp app/Http/Controllers/TeacherAttendanceController.php u5021d9810@u5021d9810.ssh.webspace-data.io:domains/mixtreelangdb.nl/mtl_app/app/Http/Controllers/

# Upload web interface (if updated)
scp public/teacher-attendance.html u5021d9810@u5021d9810.ssh.webspace-data.io:domains/mixtreelangdb.nl/mtl_app/public/
```

#### 3. Run Import Scripts on Server
```bash
# SSH to server
ssh u5021d9810@u5021d9810.ssh.webspace-data.io

# Navigate to app directory
cd domains/mixtreelangdb.nl/mtl_app

# Pre-generate all sessions (shows headers for all courses)
php create_all_sessions.php

# Import attendance data
php import_google_attendance.php ~/MTL_Attendance_Export/

# Check logs if any issues
tail -50 storage/logs/lumen.log
```

#### 4. Access Teacher Interface
1. Navigate to: `https://mixtreelangdb.nl/teacher-attendance.html`
2. Enter API token: `3ce98e5411281c79ca8e134e27876eddaa0c3484edd228d28b166049c3494887`
3. Select course from dropdown
4. Verify date headers display correctly
5. Verify student data shows: Name, Country, Initial Level
6. Verify checkboxes tick for attended dates
7. Test mid-level dropdown (appears at halfway point)
8. Test attendance percentage calculation

## Troubleshooting

### Common Issues & Solutions

#### Grid Display Issues

**"Invalid Date" in headers**
- **Cause:** Session dates being serialized to ISO timestamps (2025-10-22T22:00:00.000000Z)
- **Fix:** Controller uses `$session->getAttributes()['date']` to get raw YYYY-MM-DD format
- **Location:** TeacherAttendanceController.php line 232 in getStudentAttendance()

**Attendance checkboxes not ticking**
- **Cause:** Date format mismatch between attendance map and schedule array
- **Fix:** Both must use YYYY-MM-DD format (fixed by getAttributes() approach above)
- **Debug:** Check browser console for attendance map vs schedule dates

**Missing student data (Country, Initial Level)**
- **Cause:** Import script not populating fields OR enrollment status not 'active'
- **Fix 1:** Re-import with updated import_google_attendance.php
- **Fix 2:** Check enrollments.status = 'active' in database

**No date headers showing**
- **Cause:** No sessions exist for course yet
- **Fix:** Run `php create_all_sessions.php` to pre-generate all sessions

#### Import Script Errors

**"Course not found for attendance_id: XXX"**
- Check filename format: `Class Attendance & Certificate System - ATTENDANCE_ID.csv`
- Verify attendance_id exists in course_offerings table
- Match is case-sensitive

**"Column 'country_of_origin' cannot be null"**
- CSV missing Country column OR student row has empty country
- Add default value or update CSV with country data

**"Column 'initial_level' cannot be null"**
- CSV missing Initial Level column OR student has empty initial level
- Import script now handles this - re-run import

**"Undefined array key 'start_time'" warnings**
- Course missing start_time/end_time/teacher_id fields (nullable)
- Not an error - sessions created with NULL values
- Fixed in create_all_sessions.php using `??` operator

**Duplicate attendance records**
- UNIQUE constraint prevents duplicates (session_id, student_id)
- Import uses ON DUPLICATE KEY UPDATE - safe to re-run

#### API Errors

**"Failed to load courses"**
- Check API token is valid: `3ce98e5411281c79ca8e134e27876eddaa0c3484edd228d28b166049c3494887`
- Verify routes loaded: `php artisan route:list`
- Check storage/logs/lumen.log for errors

**"No students found"**
- Verify enrollments.status = 'active' (not 'pending' or 'completed')
- Check student_id and course_offering_id in enrollments table
- Confirm course has enrolled students

#### Authentication Issues

**Token not persisting**
- Browser localStorage may be cleared on close
- Re-enter token: `localStorage.setItem('auth_token', 'TOKEN')`
- Check browser console for localStorage errors

**"Unauthorized" errors**
- Token expired or invalid
- Verify token in database: users.api_token column
- Generate new token if needed

## Development Notes

### Critical Implementation Details

**Date Handling in Laravel/Lumen:**
- Session model has `date` cast as `'date:Y-m-d'` but JSON serialization still produces ISO timestamps
- **Solution:** Use `$session->getAttributes()['date']` in controller to bypass model casting
- Never rely on Carbon date casting for API responses - always return raw DB value

**Schedule Generation:**
- Course schedule stored as JSON: `{"days":["M","T","Th","F"],"time_range":"12:30-15:00"}`
- generateCourseSchedule() function parses days and generates all dates between start_date and end_date
- Day mapping: M=1 (Monday), T=2, W=3, Th=4, F=5, Sa=6, Su=7
- Uses DatePeriod to iterate through date range efficiently

**Import Script Architecture:**
- Manual .env parsing (no Composer dependency) - loadEnv() function
- Idempotent design: uses INSERT ... ON DUPLICATE KEY UPDATE for all tables
- Full schedule generation on import ensures grid shows complete course structure
- Handles multiple date formats in CSV: ISO (2025-10-22), M/D (10/22), month name (Oct 22)

**Database Constraints:**
- UNIQUE(session_id, student_id) on attendance_records prevents duplicates
- enrollments.status MUST be 'active' for students to appear in grid
- Foreign key constraints require deletion order: attendance_records → sessions

### Adding New Features

1. **New API Endpoint:**
   - Add method to TeacherAttendanceController.php
   - Add route in routes/web.php: `$router->get('/api/teacher/...', 'TeacherAttendanceController@method');`
   - Use Carbon::now() not now() helper
   - Return raw date values with getAttributes() for date columns
   - Test with curl before deploying

2. **New Grid Column:**
   - Update TeacherAttendanceController.php getAttendance() to join/select new field
   - Add column to teacher-attendance.html header and body
   - Add sticky-col class if left-fixed positioning needed
   - Test auto-save if editable field

3. **New Import Field:**
   - Update CSV format documentation
   - Add field to import_google_attendance.php INSERT/UPDATE statements
   - Test with sample CSV
   - Run cleanup_sessions.php + re-import to test on existing course

### Testing Checklist

Before deploying changes:
- [ ] Test locally if possible (PHP version 7.4+, MySQL 5.7+)
- [ ] Check all files uploaded to correct server paths
- [ ] Verify .env file has correct database credentials
- [ ] Run database migrations if schema changed
- [ ] Clear Laravel cache: `php artisan cache:clear`
- [ ] Test import script with single file before batch import
- [ ] Test grid display in browser console (check for errors)
- [ ] Verify API responses return expected format
- [ ] Check storage/logs/lumen.log for PHP errors
- [ ] Test attendance save functionality
- [ ] Verify attendance percentages calculate correctly

### Future Enhancements

**Completed:**
- [x] Google Apps Script CSV exporter
- [x] Full schedule generation from JSON
- [x] Session pre-generation for all courses
- [x] Date format compatibility (ISO to YYYY-MM-DD)
- [x] Grid-based attendance interface
- [x] Mid-level evaluation tracking
- [x] Previous courses tracking
- [x] Student email export

**Pending:**
- [ ] Teacher login page (instead of manual token entry)
- [ ] Attendance reports and exports (PDF/CSV)
- [ ] Email notifications for low attendance
- [ ] Bulk session creation from admin panel
- [ ] Student-facing attendance history view
- [ ] QR code check-in for students
- [ ] Mobile-optimized grid layout
- [ ] Undo/redo for attendance changes
- [ ] Attendance analytics dashboard
- [ ] Integration with certificate generation system
