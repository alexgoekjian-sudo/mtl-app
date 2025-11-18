#!/usr/bin/env python3
"""
Generate SQL INSERT statements from normalized JSON files
This creates a backup SQL script in case the PHP import doesn't work
"""

import json
import re
from datetime import datetime

def escape_sql(value):
    """Escape string values for SQL"""
    if value is None:
        return 'NULL'
    if isinstance(value, bool):
        return '1' if value else '0'
    if isinstance(value, (int, float)):
        return str(value)
    if isinstance(value, (dict, list)):
        return "'" + json.dumps(value).replace("'", "''") + "'"
    # String
    return "'" + str(value).replace("'", "''").replace("\\", "\\\\") + "'"

def extract_level(course_key):
    """Extract level from course_key (e.g., 'A1 BEGINNER' -> 'A1')"""
    match = re.match(r'^([ABC][12])\b', course_key or '')
    return escape_sql(match.group(1) if match else None)

def extract_type(schedule_type):
    """Extract type from schedule_type"""
    schedule_type_lower = (schedule_type or '').lower()
    if 'morning' in schedule_type_lower:
        return escape_sql('morning')
    elif 'evening' in schedule_type_lower:
        return escape_sql('evening')
    elif 'afternoon' in schedule_type_lower:
        return escape_sql('afternoon')
    elif 'online' in schedule_type_lower:
        return escape_sql('online')
    elif 'intensive' in schedule_type_lower:
        return escape_sql('intensive')
    return 'NULL'

def extract_hours(hours_raw):
    """Extract hours_total from hours_raw string"""
    if not hours_raw:
        return 'NULL'
    match = re.search(r'(\d+)\s*hours?', hours_raw)
    return str(int(match.group(1))) if match else 'NULL'

def infer_program(course_key):
    """Infer program from course_key"""
    course_key_lower = (course_key or '').lower()
    if 'intensive' in course_key_lower:
        return escape_sql('intensive')
    elif 'conversation' in course_key_lower:
        return escape_sql('conversation')
    elif 'business' in course_key_lower:
        return escape_sql('business')
    elif '1-2-1' in course_key_lower:
        return escape_sql('private')
    return escape_sql('general')

def build_schedule(data):
    """Build schedule JSON from days and time_range"""
    if not data.get('days'):
        return 'NULL'
    schedule = {
        'days': data['days'],
        'time_range': data.get('time_range')
    }
    return escape_sql(schedule)

def generate_course_inserts(json_file, output_file):
    """Generate SQL INSERT statements for courses"""
    with open(json_file, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    inserts = []
    inserts.append("-- Course Offerings Import")
    inserts.append("-- Generated: " + datetime.now().isoformat())
    inserts.append("-- Source: " + json_file)
    inserts.append("")
    
    for row in data:
        if row.get('import_status') != 'ok':
            continue
        
        course = row['course_offering']
        
        # Build values
        attendance_id = escape_sql(course.get('attendance_id'))
        round_val = course.get('round')
        round_num = int(round_val) if round_val is not None else 1
        course_key = escape_sql(course.get('course_key'))
        course_full_name = escape_sql(course.get('course_full_name'))
        level = extract_level(course.get('course_key'))
        program = infer_program(course.get('course_key'))
        type_val = extract_type(course.get('schedule_type'))
        start_date = escape_sql(course.get('start_date'))
        end_date = escape_sql(course.get('end_date'))
        hours_total = extract_hours(course.get('hours_raw'))
        schedule = build_schedule(course)
        price = escape_sql(course.get('price'))
        location = escape_sql(course.get('location'))
        online = '1' if course.get('delivery_mode') == 'online' else '0'
        course_book = escape_sql(course.get('course_book'))
        
        sql = f"""INSERT INTO course_offerings (
    attendance_id, round, course_key, course_full_name, level, program, type,
    start_date, end_date, hours_total, schedule, price, location, online,
    course_book, created_at, updated_at
) VALUES (
    {attendance_id}, {round_num}, {course_key}, {course_full_name}, {level}, {program}, {type_val},
    {start_date}, {end_date}, {hours_total}, {schedule}, {price}, {location}, {online},
    {course_book}, NOW(), NOW()
);"""
        
        inserts.append(sql)
    
    # Write to file
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(inserts))
    
    print(f"Generated {len(inserts)-4} course INSERT statements")
    print(f"Output: {output_file}")

def generate_student_inserts(json_file, output_file):
    """Generate SQL INSERT statements for students and leads"""
    with open(json_file, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    inserts = []
    inserts.append("-- Students and Leads Import")
    inserts.append("-- Generated: " + datetime.now().isoformat())
    inserts.append("-- Source: " + json_file)
    inserts.append("")
    inserts.append("-- Note: Run this AFTER importing courses")
    inserts.append("")
    
    student_count = 0
    lead_count = 0
    
    for row in data:
        student = row.get('student', {})
        
        # Skip if no contact info
        if not student.get('email') and not student.get('phone'):
            continue
        
        # Determine if Lead or Student
        has_level_check = student.get('assessed_level') or student.get('placement_result')
        has_course = student.get('course_name') and student.get('linked_course_key')
        
        first_name = escape_sql(student.get('first_name'))
        last_name = escape_sql(student.get('last_name'))
        email = escape_sql(student.get('email'))
        phone = escape_sql(student.get('phone'))
        country = escape_sql(student.get('country'))
        city = escape_sql(student.get('city'))
        notes = escape_sql(student.get('activity_notes'))
        
        if has_level_check or has_course:
            # Create as Student
            languages = escape_sql(student.get('languages'))
            assessed_level = escape_sql(student.get('assessed_level'))
            
            sql = f"""INSERT INTO students (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    initial_level, current_level, languages, profile_notes, created_at, updated_at
) VALUES (
    {first_name}, {last_name}, {email}, {phone}, {country}, {city},
    {assessed_level}, {assessed_level}, {languages}, {notes}, NOW(), NOW()
);"""
            
            inserts.append(sql)
            student_count += 1
            
            # Add enrollment if course is linked
            if has_course:
                attendance_id = escape_sql(student.get('linked_course_key'))
                inserts.append(f"""
-- Create enrollment for student (email: {email.strip("'")}) to course (attendance_id: {attendance_id.strip("'")})
INSERT INTO enrollments (
    student_id, course_offering_id, status, enrolled_at, created_at, updated_at
)
SELECT 
    s.id,
    co.id,
    'active',
    NOW(),
    NOW(),
    NOW()
FROM students s
CROSS JOIN course_offerings co
WHERE s.email = {email}
  AND co.attendance_id = {attendance_id}
  AND NOT EXISTS (
    SELECT 1 FROM enrollments e 
    WHERE e.student_id = s.id AND e.course_offering_id = co.id
  );
""")
        else:
            # Create as Lead
            source = escape_sql(student.get('source', 'trello_import'))
            
            sql = f"""INSERT INTO leads (
    first_name, last_name, email, phone, country_of_origin, city_of_residence,
    source, notes, created_at, updated_at
) VALUES (
    {first_name}, {last_name}, {email}, {phone}, {country}, {city},
    {source}, {notes}, NOW(), NOW()
);"""
            
            inserts.append(sql)
            lead_count += 1
    
    # Write to file
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(inserts))
    
    print(f"Generated {student_count} student INSERT statements")
    print(f"Generated {lead_count} lead INSERT statements")
    print(f"Output: {output_file}")

if __name__ == '__main__':
    import os
    
    base_dir = os.path.dirname(os.path.abspath(__file__))
    imports_dir = os.path.join(base_dir, 'specs', '001-title-english-language', 'imports', 'out')
    
    # Generate course inserts
    courses_json = os.path.join(imports_dir, 'courses_normalized.json')
    courses_sql = os.path.join(base_dir, 'import_courses.sql')
    
    if os.path.exists(courses_json):
        generate_course_inserts(courses_json, courses_sql)
    else:
        print(f"ERROR: {courses_json} not found")
    
    # Generate student/lead inserts
    trello_json = os.path.join(imports_dir, 'trello_normalized.json')
    students_sql = os.path.join(base_dir, 'import_students_leads.sql')
    
    if os.path.exists(trello_json):
        generate_student_inserts(trello_json, students_sql)
    else:
        print(f"ERROR: {trello_json} not found")
    
    print("\n✓ SQL import scripts generated successfully!")
    print("\nTo import:")
    print("  mysql -u username -p database_name < import_courses.sql")
    print("  mysql -u username -p database_name < import_students_leads.sql")
