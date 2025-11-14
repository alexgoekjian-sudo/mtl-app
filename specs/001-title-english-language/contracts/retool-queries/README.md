# Retool Saved Queries

Place the SQL files in Retool as saved queries or copy their SQL into Retool query editors. Parameter placeholders use Retool's moustache format (e.g. {{first_name}}).

Files and parameters:
- students_list.sql — {{limit}}, {{offset}}
- create_student.sql — {{first_name}}, {{last_name}}, {{email}}, {{phone}}
- enroll_student.sql — {{student_id}}, {{course_offering_id}}
- invoices_list.sql — {{limit}}, {{offset}}

Notes:
- In Retool, bind the parameters to component values (e.g. a text input or table selectedRow.data.id).
- Use parameterized queries in Retool to avoid SQL injection and pass values as query parameters rather than string interpolation.
