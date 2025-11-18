# Teacher Attendance System

Simple web-based attendance tracking interface for teachers (non-Retool).

## Components

### 1. Web Interface
**File:** `public/teacher-attendance.html`

**Features:**
- Course selector dropdown
- Date-based session navigation
- Student attendance grid with status buttons
- Real-time statistics (present/late/absent/excused counts, attendance rate)
- Auto-save functionality with visual confirmation
- Create new session for any date

**Status Options:**
- **Present** (green) - Student attended
- **Late** (yellow) - Student arrived late
- **Absent** (red) - Student did not attend
- **Excused** (gray) - Absence was excused
- **None** - No record (removes existing record)

**Access:**
Navigate to: `https://yourdomain.com/teacher-attendance.html`

### 2. API Endpoints
**Controller:** `app/Http/Controllers/TeacherAttendanceController.php`

**Routes (all require authentication token):**
- `GET /api/teacher/courses` - List all courses
- `GET /api/teacher/courses/{id}` - Get course details
- `GET /api/teacher/courses/{id}/attendance?date=YYYY-MM-DD` - Get attendance for specific date
- `POST /api/teacher/courses/{id}/sessions` - Create new session
- `POST /api/teacher/courses/{id}/attendance` - Save attendance (bulk update)
- `GET /api/teacher/courses/{id}/summary` - Course attendance summary
- `GET /api/teacher/courses/{courseId}/students/{studentId}/attendance` - Student attendance history

### 3. Import Script
**File:** `import_attendance.php`

Import attendance from CSV exports (Google Sheets format).

**CSV Format:**
```
Student Name, Student Email, Dates Present, Initial Level, Mid Level, Teacher Notes, Previous Courses, Trello Card ID, Country
```

**Usage:**
```bash
php import_attendance.php "path/to/Class Attendance & Certificate System - ATTENDANCE_ID.csv"
```

**Features:**
- Extracts attendance_id from filename pattern: `- ATTENDANCE_ID.csv`
- Finds course by attendance_id
- Parses comma-separated dates from "Dates Present" column
- Creates sessions automatically if they don't exist
- Links students via email lookup
- Updates student current_level from "Mid Level" column
- Creates attendance_records with status='present' for each date
- Skips duplicate records

**Output:**
```
Course found: A2 PR_MORN_EDMON_7 (ID: 123)
Attendance records created: 45
Students skipped (not found): 2
Unique session dates: 15
```

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

### attendance_records
- `id` - Primary key
- `session_id` - Foreign key to sessions table
- `student_id` - Foreign key to students table
- `status` - ENUM('present', 'late', 'absent', 'excused')
- `note` - TEXT (optional teacher notes)
- `recorded_by` - Foreign key to users table (who marked attendance)
- `recorded_at` - TIMESTAMP (when attendance was marked)

### sessions
- `id` - Primary key
- `course_offering_id` - Foreign key to course_offerings table
- `session_date` - DATE (the date of the class session)
- `status` - ENUM('scheduled', 'completed', 'cancelled')

## Deployment

### Upload Files to Server
```bash
# Upload controller
scp app/Http/Controllers/TeacherAttendanceController.php user@server:/path/to/mtl_app/app/Http/Controllers/

# Upload web interface
scp public/teacher-attendance.html user@server:/path/to/mtl_app/public/

# Upload import script
scp import_attendance.php user@server:/path/to/mtl_app/

# Update routes (if not done via git)
scp routes/web.php user@server:/path/to/mtl_app/routes/
```

### Test Import Script
```bash
ssh user@server
cd /path/to/mtl_app
php import_attendance.php "specs/001-title-english-language/imports/Class Attendance & Certificate System - A2 PR_MORN_EDMON_7.csv"
```

### Access Web Interface
1. Navigate to: `https://mixtreelangdb.nl/teacher-attendance.html`
2. Enter API token (get from admin or login)
3. Select course from dropdown
4. Pick date using date picker
5. Mark attendance for each student
6. Click "Save All Changes"

## Troubleshooting

### "Failed to load courses"
- Check that API token is valid
- Verify routes are loaded: `php artisan route:list`
- Check controller exists and namespace is correct
- Review server logs for errors

### "No students found"
- Verify students are enrolled in the selected course
- Check enrollments table: `status = 'active'`
- Ensure course_offering_id is correct

### Import script errors
- **"Course not found"**: Check that attendance_id in filename matches database
- **"Student not found"**: Verify student email exists in students table
- **"Failed to parse dates"**: Check CSV format - dates should be comma-separated YYYY-MM-DD

### Authentication issues
- Clear browser localStorage: `localStorage.clear()`
- Get new token from admin
- Verify middleware is not blocking teacher routes
- Check CORS settings if accessing from different domain

## Development Notes

### Adding New Features
1. Update `TeacherAttendanceController.php` for new endpoints
2. Add routes in `routes/web.php`
3. Update `teacher-attendance.html` JavaScript to call new endpoints
4. Test with Postman or curl before deploying

### API Response Format
All endpoints return JSON:
```json
{
  "session": {...},
  "students": [...],
  "attendance": [...]
}
```

Errors return:
```json
{
  "error": "Error message",
  "status": 400
}
```

### Future Enhancements
- [ ] Teacher login page (instead of manual token entry)
- [ ] Bulk import from multiple CSV files
- [ ] Attendance reports and exports
- [ ] Email notifications for low attendance
- [ ] Mobile app version
- [ ] QR code check-in for students
