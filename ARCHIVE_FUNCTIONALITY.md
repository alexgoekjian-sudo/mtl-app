# Archive Functionality Implementation

## Overview
Added archive/status management for students and course offerings to handle historical data without cluttering active views.

## Database Changes

### Migration: `20251121_000006_add_archive_functionality.php`

#### Students Table
- **New Column:** `is_active` (BOOLEAN, default 1)
  - `1` = Active student (shows in normal views)
  - `0` = Archived student (hidden by default, searchable if they return)

#### Course Offerings Table
- **New Column:** `status` (ENUM: 'draft', 'active', 'completed', 'cancelled', default 'active')
  - `draft` = Course being planned
  - `active` = Course available for enrollment or in progress
  - `completed` = Course finished
  - `cancelled` = Course cancelled

## Model Updates

### Student Model
**New Fillable:** `is_active`

**New Scopes:**
- `Student::active()` - Only active students
- `Student::archived()` - Only archived students

**New Methods:**
- `$student->archive()` - Mark student as inactive, log activity
- `$student->restore()` - Restore student to active, log activity

**Usage Examples:**
```php
// Get only active students
$activeStudents = Student::active()->get();

// Get archived students
$archivedStudents = Student::archived()->get();

// Archive a student
$student->archive();

// Restore from archive
$student->restore();
```

### CourseOffering Model
**New Fillable:** `status`

**New Scopes:**
- `CourseOffering::active()` - Only active courses
- `CourseOffering::upcoming()` - Active courses not yet started
- `CourseOffering::ongoing()` - Active courses currently running
- `CourseOffering::completed()` - Completed courses or past end_date

**New Methods:**
- `$course->markCompleted()` - Set status to completed
- `$course->markCancelled()` - Set status to cancelled
- `$course->isOngoing()` - Check if currently running
- `$course->hasEnded()` - Check if finished

**Usage Examples:**
```php
// Get upcoming courses
$upcoming = CourseOffering::upcoming()->get();

// Get ongoing courses
$ongoing = CourseOffering::ongoing()->get();

// Mark course as completed
$course->markCompleted();

// Check if course is ongoing
if ($course->isOngoing()) {
    // ...
}
```

## Database Views

Created 6 new views in `database/views/archive_views.sql`:

### 1. `active_students`
Shows only active students with enrollment summary:
- Total enrollments
- Active enrollments
- Completed courses
- Last course end date

### 2. `active_course_offerings`
Shows only active courses with:
- Enrollment counts
- Available spots
- Timing status (upcoming/ongoing/past)

### 3. `upcoming_courses`
Active courses with future start dates:
- Ordered by start date
- Shows enrolled count and spots available

### 4. `ongoing_courses`
Active courses currently in progress:
- Session counts (total and completed)
- Enrolled student count

### 5. `completed_courses`
Finished courses:
- Student completion/dropout counts
- Total sessions held

### 6. `archived_students`
Archived students with:
- Total enrollments
- Last course date
- Days since last course

## Deployment Steps

### 1. Run Migration
```bash
ssh u5021d9810@web0091
cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app
php artisan migrate
```

### 2. Create Database Views
```bash
mysql -u u5021d9810 -p u5021d9810_mtldb < database/views/archive_views.sql
```

### 3. Update Existing Data (Optional)
```sql
-- Mark old courses as completed (courses that ended > 6 months ago)
UPDATE course_offerings 
SET status = 'completed' 
WHERE end_date < DATE_SUB(CURDATE(), INTERVAL 6 MONTH);

-- Archive inactive students (no enrollments in last 2 years)
UPDATE students s
SET is_active = 0
WHERE s.id NOT IN (
    SELECT DISTINCT e.student_id 
    FROM enrollments e 
    JOIN course_offerings co ON e.course_id = co.id 
    WHERE co.end_date > DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
);
```

## Retool Integration

### Recommended Table Filters

**Students Table:**
- Add toggle: "Show Archived Students" (default: OFF)
- Query: `SELECT * FROM active_students` or `SELECT * FROM archived_students`

**Course Offerings Table:**
- Add dropdown filter: "Status" (All/Active/Upcoming/Ongoing/Completed/Cancelled)
- Use views: `upcoming_courses`, `ongoing_courses`, `completed_courses`

**Enrollment Views:**
- Default to showing only enrollments for active courses
- Add option to include historical data

## Benefits

1. **Clean Active Views** - Normal operations only see current, relevant data
2. **Data Preservation** - Historical records remain searchable and intact
3. **Automatic Filtering** - Database views provide pre-filtered datasets
4. **Returner Support** - Archived students can be easily restored if they return
5. **Course Lifecycle** - Proper status tracking from draft → active → completed
6. **Performance** - Smaller active datasets improve query speed

## Notes

- All existing students default to `is_active = 1`
- All existing courses default to `status = 'active'`
- Archive actions are logged in activities table
- Views are automatically updated as underlying data changes
- Can use views in Retool for performance-optimized, pre-filtered data
