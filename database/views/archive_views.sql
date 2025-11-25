-- Active Students View
-- Shows only active (non-archived) students
DROP VIEW IF EXISTS `active_students`;
CREATE VIEW `active_students` AS
SELECT 
    s.id,
    s.lead_id,
    s.first_name,
    s.last_name,
    s.email,
    s.phone,
    s.country_of_origin,
    s.city_of_residence,
    s.dob,
    s.languages,
    s.previous_courses,
    s.initial_level,
    s.current_level,
    s.profile_notes,
    s.created_at,
    s.updated_at,
    -- Enrollment summary
    COUNT(DISTINCT e.id) AS total_enrollments,
    COUNT(DISTINCT CASE WHEN e.status IN ('active', 'registered') THEN e.id END) AS active_enrollments,
    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) AS completed_courses,
    MAX(co.end_date) AS last_course_end_date
FROM students s
LEFT JOIN enrollments e ON s.id = e.student_id
LEFT JOIN course_offerings co ON e.course_offering_id = co.id
WHERE s.is_active = 1
GROUP BY s.id;

-- Active Course Offerings View
-- Shows only active courses (not draft, completed, or cancelled)
DROP VIEW IF EXISTS `active_course_offerings`;
CREATE VIEW `active_course_offerings` AS
SELECT 
    co.id,
    co.attendance_id,
    co.round,
    co.course_key,
    co.course_full_name,
    co.level,
    co.program,
    co.type,
    co.book_included,
    co.course_book,
    co.start_date,
    co.end_date,
    co.hours_total,
    co.schedule,
    co.price,
    co.teacher_hourly_rate,
    co.classroom_cost,
    co.admin_overhead,
    co.capacity,
    co.location,
    co.online,
    co.status,
    co.created_at,
    co.updated_at,
    -- Enrollment summary
    COUNT(DISTINCT e.id) AS total_enrolled,
    COUNT(DISTINCT CASE WHEN e.status IN ('active', 'registered') THEN e.id END) AS active_enrolled,
    (co.capacity - COUNT(DISTINCT CASE WHEN e.status IN ('active', 'registered') THEN e.id END)) AS available_spots,
    -- Status indicators
    CASE 
        WHEN co.start_date > CURDATE() THEN 'upcoming'
        WHEN co.end_date < CURDATE() THEN 'past'
        ELSE 'ongoing'
    END AS timing_status
FROM course_offerings co
LEFT JOIN enrollments e ON co.id = e.course_offering_id
WHERE co.status = 'active'
GROUP BY co.id;

-- Upcoming Courses View
-- Active courses that haven't started yet
DROP VIEW IF EXISTS `upcoming_courses`;
CREATE VIEW `upcoming_courses` AS
SELECT 
    co.*,
    COUNT(DISTINCT e.id) AS enrolled_count,
    (co.capacity - COUNT(DISTINCT e.id)) AS spots_available
FROM course_offerings co
LEFT JOIN enrollments e ON co.id = e.course_offering_id AND e.status IN ('active', 'registered', 'pending')
WHERE co.status = 'active' 
  AND co.start_date > CURDATE()
GROUP BY co.id
ORDER BY co.start_date ASC;

-- Ongoing Courses View
-- Active courses currently in progress
DROP VIEW IF EXISTS `ongoing_courses`;
CREATE VIEW `ongoing_courses` AS
SELECT 
    co.*,
    COUNT(DISTINCT e.id) AS enrolled_count,
    COUNT(DISTINCT s.id) AS session_count,
    COUNT(DISTINCT CASE WHEN s.date <= CURDATE() THEN s.id END) AS completed_sessions
FROM course_offerings co
LEFT JOIN enrollments e ON co.id = e.course_offering_id AND e.status IN ('active', 'registered')
LEFT JOIN sessions s ON co.id = s.course_offering_id
WHERE co.status = 'active'
  AND co.start_date <= CURDATE() 
  AND co.end_date >= CURDATE()
GROUP BY co.id
ORDER BY co.start_date ASC;

-- Completed Courses View
-- Courses that have finished (status = completed OR end_date passed)
DROP VIEW IF EXISTS `completed_courses`;
CREATE VIEW `completed_courses` AS
SELECT 
    co.*,
    COUNT(DISTINCT e.id) AS total_enrolled,
    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) AS students_completed,
    COUNT(DISTINCT CASE WHEN e.status = 'dropped' THEN e.id END) AS students_dropped,
    COUNT(DISTINCT s.id) AS total_sessions
FROM course_offerings co
LEFT JOIN enrollments e ON co.id = e.course_offering_id
LEFT JOIN sessions s ON co.id = s.course_offering_id
WHERE co.status = 'completed' 
   OR (co.status = 'active' AND co.end_date < CURDATE())
GROUP BY co.id
ORDER BY co.end_date DESC;

-- Archived Students View
-- Shows only archived (inactive) students for historical reference
DROP VIEW IF EXISTS `archived_students`;
CREATE VIEW `archived_students` AS
SELECT 
    s.*,
    COUNT(DISTINCT e.id) AS total_enrollments,
    MAX(co.end_date) AS last_course_date,
    DATEDIFF(CURDATE(), MAX(co.end_date)) AS days_since_last_course
FROM students s
LEFT JOIN enrollments e ON s.id = e.student_id
LEFT JOIN course_offerings co ON e.course_offering_id = co.id
WHERE s.is_active = 0
GROUP BY s.id
ORDER BY last_course_date DESC;
