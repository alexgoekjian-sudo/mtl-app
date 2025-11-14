-- enroll_student.sql
-- Parameters: {{student_id}}, {{course_offering_id}}
INSERT INTO enrollments (student_id, course_offering_id, status, enrolled_at, created_at, updated_at)
VALUES ({{student_id}}, {{course_offering_id}}, 'registered', NOW(), NOW(), NOW());
