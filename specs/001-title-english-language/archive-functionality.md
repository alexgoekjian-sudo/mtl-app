# Archive Functionality Specification

**Feature**: Student and Course Archive Management  
**Status**: Implemented  
**Migration**: `20251121_000006_add_archive_functionality.php`  
**Date**: 2025-11-21

## Overview

This specification extends the core data model with archive/lifecycle management capabilities for Students and Course Offerings to support:
- Hiding inactive/historical students from default views while preserving data
- Tracking course lifecycle from planning through completion
- Performance optimization via filtered database views
- Clean UI/UX in Retool by showing only relevant records

## Requirements

### FR-015: Student Archive Management
**Requirement**: System MUST support archiving/deactivating students (is_active flag) to hide inactive students from default views while preserving historical records and allowing restoration.

**Acceptance Criteria**:
- AC-015.1: Administrators can mark students as archived (is_active = 0)
- AC-015.2: Archived students do not appear in default student lists
- AC-015.3: Archived students remain searchable and can be viewed via "Show Archived" toggle
- AC-015.4: Administrators can restore archived students (is_active = 1)
- AC-015.5: Archive actions are logged in activity timeline

**User Scenarios**:
- **Scenario 1**: Student hasn't enrolled in 2+ years
  - Coordinator archives student to clean up active list
  - Student record preserved with full enrollment history
  - If student returns, coordinator restores from archive
  
- **Scenario 2**: Administrator reviewing student list
  - Default view shows only active students
  - Toggle "Show Archived" to review inactive students
  - View shows days_since_last_course for prioritization

### FR-016: Course Lifecycle Status Management
**Requirement**: System MUST support course lifecycle status management (draft, active, completed, cancelled) to track course state and filter course listings appropriately.

**Acceptance Criteria**:
- AC-016.1: Course offerings can be created with status = 'draft' for planning
- AC-016.2: Draft courses can be activated when ready for enrollment
- AC-016.3: Active courses can be marked completed when finished
- AC-016.4: Active or draft courses can be cancelled if needed
- AC-016.5: Course views automatically filter based on status and dates
- AC-016.6: Completed courses show student completion statistics

**User Scenarios**:
- **Scenario 1**: Planning new courses
  - Coordinator creates courses with status = 'draft'
  - Courses visible to staff but not offered to students
  - When ready, coordinator activates course
  
- **Scenario 2**: Course completion
  - Course end_date passes
  - System shows "Mark as Completed" action
  - Coordinator marks course completed
  - Course moves to completed_courses view with statistics
  
- **Scenario 3**: Course cancellation
  - Course needs to be cancelled due to low enrollment
  - Coordinator marks status = 'cancelled'
  - Enrolled students are notified
  - Course removed from active listings

## Data Model Extensions

### Student Table
**New Column**: `is_active` TINYINT(1) NOT NULL DEFAULT 1
- `1` = Active student (default, shows in normal views)
- `0` = Archived student (hidden by default, searchable)
- Index: `idx_students_is_active` for query performance

### CourseOffering Table
**New Column**: `status` ENUM('draft', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'active'
- `draft` = Course in planning, not yet offered
- `active` = Course available for enrollment or in progress
- `completed` = Course finished
- `cancelled` = Course cancelled
- Index: `idx_course_offerings_status` for query performance

## Database Views

### Student Views

#### active_students
**Purpose**: Default student list showing only active students with enrollment summary
**Columns**: All student fields + total_enrollments, active_enrollments, completed_courses, last_course_end_date
**Filter**: WHERE is_active = 1

#### archived_students
**Purpose**: Historical student records with inactivity metrics
**Columns**: All student fields + total_enrollments, last_course_date, days_since_last_course
**Filter**: WHERE is_active = 0
**Order**: By last_course_date DESC (most recently active first)

### Course Views

#### active_course_offerings
**Purpose**: Main course listing for active courses
**Columns**: All course fields + total_enrolled, active_enrolled, available_spots, timing_status
**Filter**: WHERE status = 'active'
**Timing Status**: 'upcoming' if start_date > today, 'past' if end_date < today, else 'ongoing'

#### upcoming_courses
**Purpose**: Courses not yet started, for planning and enrollment
**Columns**: All course fields + enrolled_count, spots_available
**Filter**: WHERE status = 'active' AND start_date > CURDATE()
**Order**: By start_date ASC

#### ongoing_courses
**Purpose**: Currently running courses with session progress
**Columns**: All course fields + enrolled_count, session_count, completed_sessions
**Filter**: WHERE status = 'active' AND start_date <= CURDATE() AND end_date >= CURDATE()
**Order**: By start_date ASC

#### completed_courses
**Purpose**: Finished courses with completion statistics
**Columns**: All course fields + total_enrolled, students_completed, students_dropped, total_sessions
**Filter**: WHERE status = 'completed' OR (status = 'active' AND end_date < CURDATE())
**Order**: By end_date DESC

## Model Extensions

### Student Model (app/Models/Student.php)

**New Fillable**: `is_active`
**New Cast**: `is_active` => 'boolean'

**New Scopes**:
```php
scopeActive($query)      // WHERE is_active = 1
scopeArchived($query)    // WHERE is_active = 0
```

**New Methods**:
```php
archive()   // Set is_active = 0, log activity
restore()   // Set is_active = 1, log activity
```

### CourseOffering Model (app/Models/CourseOffering.php)

**New Fillable**: `status`

**New Scopes**:
```php
scopeActive($query)      // WHERE status = 'active'
scopeUpcoming($query)    // Active courses with future start_date
scopeOngoing($query)     // Active courses currently running
scopeCompleted($query)   // Completed or past end_date
```

**New Methods**:
```php
markCompleted()    // Set status = 'completed'
markCancelled()    // Set status = 'cancelled'
isOngoing()        // Check if currently running
hasEnded()         // Check if finished
```

## Retool Integration

### Student Management
- Add "Show Archived Students" toggle (default: OFF)
- Use `active_students` view for default table
- Use `archived_students` view when toggle ON
- Add Archive/Restore button in action column
- Conditional display of `days_since_last_course` column when showing archived

### Course Management
- Add "Filter by Status" dropdown (Active/Upcoming/Ongoing/Completed/All)
- Dynamic query selection based on filter value
- Use appropriate view for each filter option
- Display conditional columns based on view (enrollment counts, session progress, completion stats)
- Add "Mark as Completed" action for past active courses
- Add "Cancel Course" action for draft/active courses

## Implementation Files

**Migration**: `database/migrations/20251121_000006_add_archive_functionality.php`
**Views SQL**: `database/views/archive_views.sql`
**Models Updated**: `app/Models/Student.php`, `app/Models/CourseOffering.php`
**Documentation**: `ARCHIVE_FUNCTIONALITY.md`, `RETOOL_SETUP.md`, `RETOOL_FRONTEND_GUIDE.md`

## Deployment

1. Upload migration and models to server
2. Run migration: `/opt/alt/php82/usr/bin/php -d opcache.enable=0 -d opcache.enable_cli=0 artisan migrate`
3. Create views: `mysql -u u5021d9810 -p u5021d9810_mtldb < database/views/archive_views.sql`
4. Update Retool queries to use new views
5. Optional: Run data cleanup to archive old students and mark completed courses

## Benefits

1. **Performance**: Smaller active datasets improve query speed
2. **UX**: Cleaner views focused on current operations
3. **Data Preservation**: Historical records remain intact and searchable
4. **Flexibility**: Easy restoration if archived students return
5. **Reporting**: Pre-aggregated metrics in views improve reporting performance
6. **Course Tracking**: Clear lifecycle management from planning to completion

## Testing

**Test Cases**:
1. Archive student → verify removed from active_students view
2. Restore student → verify appears in active_students view
3. Create draft course → verify not in active_course_offerings view
4. Activate course → verify appears in upcoming_courses view
5. Mark course completed → verify moves to completed_courses view with stats
6. Toggle "Show Archived" in Retool → verify correct data displayed
7. Filter courses by status → verify correct view used and columns displayed
