-- create_student.sql
-- Parameters: {{first_name}}, {{last_name}}, {{email}}, {{phone}}
INSERT INTO students (first_name, last_name, email, phone, created_at, updated_at)
VALUES ({{first_name}}, {{last_name}}, {{email}}, {{phone}}, NOW(), NOW());
