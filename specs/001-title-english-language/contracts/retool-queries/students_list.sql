-- students_list.sql
-- Parameters: {{limit}} (default 100), {{offset}} (default 0)
SELECT id, first_name, last_name, email, phone, initial_level, current_level, created_at
FROM students
ORDER BY last_name, first_name
LIMIT {{limit}} OFFSET {{offset}};
