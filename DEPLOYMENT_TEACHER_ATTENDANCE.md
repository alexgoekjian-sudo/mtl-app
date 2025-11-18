# Teacher Attendance System - Deployment Guide

## Quick Deploy (Copy-Paste Commands)

### 1. Commit and Push Code Changes
```powershell
cd C:\Users\alex\MTL_App
git add app/Http/Controllers/TeacherAttendanceController.php
git add routes/web.php
git add public/teacher-attendance.html
git add import_attendance.php
git add TEACHER_ATTENDANCE_README.md
git commit -m "Add teacher attendance system with web interface and CSV import"
git push origin feat/deploy-scripts-improvements
```

### 2. Deploy to Server via SSH
```bash
# SSH into server
ssh u5021d9810@access977968164.webspace-data.io

# Navigate to app directory
cd domains/mixtreelangdb.nl/mtl_app/

# Pull latest code
git pull origin feat/deploy-scripts-improvements

# Verify files are present
ls -la app/Http/Controllers/TeacherAttendanceController.php
ls -la public/teacher-attendance.html
ls -la import_attendance.php
```

### 3. Test the API Endpoints
```bash
# Test that routes are loaded (check for /api/teacher routes)
php artisan route:list | grep teacher

# If routes don't show up, clear cache
php artisan route:clear
php artisan cache:clear
```

### 4. Test Import Script
```bash
# Run import script with sample CSV
php import_attendance.php "specs/001-title-english-language/imports/Class Attendance & Certificate System - A2 PR_MORN_EDMON_7.csv"

# Expected output:
# Course found: [course name] (ID: [id])
# Attendance records created: [count]
# Students skipped (not found): [count]
# Unique session dates: [count]
```

### 5. Create Teacher User Account
```bash
# Login to MySQL
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb

# Create teacher user (if doesn't exist)
INSERT INTO users (email, password, role, created_at, updated_at) 
VALUES ('teacher@mixtreelangdb.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NOW(), NOW());
# Note: password is 'password' (hashed with bcrypt)

# Get the user ID for token generation
SELECT id, email, role FROM users WHERE email = 'teacher@mixtreelangdb.nl';

# Exit MySQL
exit;
```

### 6. Generate API Token for Teacher
```bash
# Use PHP to generate token
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
# Copy the output token

# Or use artisan command if available
php artisan token:generate teacher@mixtreelangdb.nl
```

### 7. Test Web Interface
```bash
# Access the page in browser:
# https://mixtreelangdb.nl/teacher-attendance.html

# Open browser console and set token:
localStorage.setItem('auth_token', 'PASTE_TOKEN_HERE');
location.reload();
```

## Verification Checklist

- [ ] Code pushed to Git repository
- [ ] Server pulled latest code (`git pull`)
- [ ] Controller file exists on server
- [ ] Routes file updated on server
- [ ] HTML file accessible in browser
- [ ] Import script tested with sample CSV
- [ ] Teacher user account created
- [ ] API token generated and saved
- [ ] Web interface loads without errors
- [ ] Course dropdown populates
- [ ] Attendance can be marked and saved
- [ ] Import creates sessions and attendance records

## File Locations on Server

```
/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/
├── app/Http/Controllers/TeacherAttendanceController.php   (API backend)
├── routes/web.php                                          (API routes)
├── public/teacher-attendance.html                          (Web UI)
├── import_attendance.php                                   (CSV import script)
└── TEACHER_ATTENDANCE_README.md                            (Documentation)
```

## API Endpoints (All require Bearer token)

```
GET  /api/teacher/courses
GET  /api/teacher/courses/{id}
GET  /api/teacher/courses/{id}/attendance?date=2025-01-15
POST /api/teacher/courses/{id}/sessions
POST /api/teacher/courses/{id}/attendance
GET  /api/teacher/courses/{id}/summary
GET  /api/teacher/courses/{courseId}/students/{studentId}/attendance
```

## Testing with cURL

```bash
# Set your token
TOKEN="your_token_here"

# Get all courses
curl -H "Authorization: Bearer $TOKEN" \
  https://mixtreelangdb.nl/api/teacher/courses

# Get attendance for specific date
curl -H "Authorization: Bearer $TOKEN" \
  "https://mixtreelangdb.nl/api/teacher/courses/1/attendance?date=2025-01-15"

# Save attendance
curl -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "session_date": "2025-01-15",
    "attendance": {
      "1": {"status": "present", "note": ""},
      "2": {"status": "late", "note": "Arrived 10 min late"},
      "3": {"status": "absent"}
    }
  }' \
  https://mixtreelangdb.nl/api/teacher/courses/1/attendance
```

## Troubleshooting

### Routes not found (404)
```bash
# Clear Laravel cache
php artisan route:clear
php artisan cache:clear
php artisan config:clear

# Verify .htaccess exists in public/
cat public/.htaccess
```

### Database connection errors
```bash
# Check .env file
cat .env | grep DB_

# Test MySQL connection
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb -e "SHOW TABLES;"
```

### Import script fails
```bash
# Check file permissions
chmod +x import_attendance.php

# Run with verbose error output
php -d display_errors=On import_attendance.php "path/to/file.csv"

# Check if course exists with attendance_id
mysql -u u5021d9810_mtldb -p u5021d9810_mtldb \
  -e "SELECT id, attendance_id, course_full_name FROM course_offerings WHERE attendance_id LIKE '%A2 PR_MORN_EDMON_7%';"
```

### Web interface shows "Failed to load"
1. Open browser DevTools (F12)
2. Check Console tab for errors
3. Check Network tab for failed requests
4. Verify token is set: `console.log(localStorage.getItem('auth_token'))`
5. Check API response in Network tab (should not be 401 Unauthorized)

## Next Steps After Deployment

1. **Import All Attendance CSVs**
   - Locate all CSV files: `Class Attendance & Certificate System - [ATTENDANCE_ID].csv`
   - Run import script for each: `php import_attendance.php "path/to/file.csv"`
   - Verify records created: `SELECT COUNT(*) FROM attendance_records;`

2. **Create Teacher Accounts**
   - Add email/password for each teacher in `users` table
   - Generate API tokens
   - Share credentials with teachers

3. **Train Teachers**
   - Share link: https://mixtreelangdb.nl/teacher-attendance.html
   - Provide login token
   - Walk through: Select course → Pick date → Mark attendance → Save

4. **Monitor Usage**
   - Check `attendance_records` table for new entries
   - Review `sessions` table for session creation
   - Check server logs for errors: `tail -f storage/logs/lumen.log`

## Security Notes

- All teacher endpoints require authentication (token middleware)
- Tokens should be kept secret (like passwords)
- Consider implementing token expiration (currently tokens don't expire)
- HTTPS is required for production use (credentials in transit)
- Consider adding role-based access control (teachers can only see their courses)

## Support

For issues or questions, refer to:
- Main documentation: `TEACHER_ATTENDANCE_README.md`
- Laravel Lumen docs: https://lumen.laravel.com/docs
- Server logs: `/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/storage/logs/`
