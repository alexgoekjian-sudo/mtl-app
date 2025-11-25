-- Add archive functionality via direct SQL
-- Run this on the production database

-- Add is_active column to students table
ALTER TABLE students 
ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 
AFTER profile_notes;

-- Add status column to course_offerings table
ALTER TABLE course_offerings 
ADD COLUMN status ENUM('draft', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'active' 
AFTER online;

-- Create index for better query performance
CREATE INDEX idx_students_is_active ON students(is_active);
CREATE INDEX idx_course_offerings_status ON course_offerings(status);

-- Optional: Mark old courses as completed (courses that ended > 6 months ago)
-- Uncomment if you want to run this:
-- UPDATE course_offerings 
-- SET status = 'completed' 
-- WHERE end_date < DATE_SUB(CURDATE(), INTERVAL 6 MONTH);

-- Optional: Archive inactive students (no enrollments in last 2 years)
-- Uncomment if you want to run this:
-- UPDATE students s
-- SET is_active = 0
-- WHERE s.id NOT IN (
--     SELECT DISTINCT e.student_id 
--     FROM enrollments e 
--     JOIN course_offerings co ON e.course_id = co.id 
--     WHERE co.end_date > DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
-- );
