# Retool Integration Setup Guide

This guide shows how to connect Retool to your MTL backend and use the provided endpoints.

## Step 1: Create a Retool Service Token

On the server, run the seeder to create a dedicated Retool service user and generate an API token:

```bash
cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app
php artisan db:seed --class=RetoolServiceUserSeeder --force
```

This will output:
- Email: retool@mixtreelang.nl
- API Token: <48-character-hex-token>

**Copy the API token** — you'll paste it into Retool.

## Step 2: Create a REST API Resource in Retool

1. In Retool, go to **Resources** → **Create new** → **REST API**
2. Configure the resource:
   - **Name**: MTL Backend
   - **Base URL**: `https://mixtreelangdb.nl`
   - **Authentication**: None (we'll use headers)
   - **Headers** (add a default header):
     - **Key**: `Authorization`
     - **Value**: `Bearer <PASTE_TOKEN_HERE>` (replace `<PASTE_TOKEN_HERE>` with the token from Step 1)

3. Save the resource.

## Step 3: Example Queries

### List Students (simple array, no pagination)
- **Resource**: MTL Backend
- **Method**: GET
- **URL**: `/api/retool/students`
- **Optional params**: `?limit=1000` (default 1000)
- **Response**: Array of student objects

In Retool, bind to a Table component:
- **Data**: `{{ listStudents.data }}`

**Recommended: Use Archive Views for Filtering**

Instead of showing all students, use the database views for cleaner data:

#### Active Students Only (default view)
- **Method**: GET
- **URL**: `/api/retool/students` with custom SQL query in Retool SQL resource:
  ```sql
  SELECT * FROM active_students
  ```
- **Benefits**: Excludes archived students, includes enrollment summary (total_enrollments, active_enrollments, completed_courses, last_course_end_date)

#### Archived Students (historical records)
- **SQL Query**:
  ```sql
  SELECT * FROM archived_students
  ```
- **Use case**: View inactive students with days_since_last_course
- **Toggle**: Add a checkbox in Retool to switch between active_students and archived_students views

### List Course Offerings (simple array)
- **Resource**: MTL Backend
- **Method**: GET
- **URL**: `/api/retool/course_offerings`
- **Response**: Array of course objects

**Recommended: Use Status-Filtered Views**

Use database views to show only relevant courses:

#### Active Courses Only
- **SQL Query**:
  ```sql
  SELECT * FROM active_course_offerings
  ```
- **Benefits**: Only active courses, includes enrollment counts, available spots, timing_status (upcoming/ongoing/past)

#### Upcoming Courses (not yet started)
- **SQL Query**:
  ```sql
  SELECT * FROM upcoming_courses ORDER BY start_date ASC
  ```
- **Use case**: Planning view, shows enrolled_count and spots_available

#### Ongoing Courses (currently running)
- **SQL Query**:
  ```sql
  SELECT * FROM ongoing_courses
  ```
- **Benefits**: Includes session_count and completed_sessions for progress tracking

#### Completed Courses (finished)
- **SQL Query**:
  ```sql
  SELECT * FROM completed_courses ORDER BY end_date DESC
  ```
- **Use case**: Historical view, shows students_completed and students_dropped

#### All Courses with Status Filter (flexible)
- Add a dropdown in Retool with options: All / Active / Upcoming / Ongoing / Completed / Cancelled
- Use dynamic SQL based on selection:
  ```sql
  SELECT * FROM course_offerings 
  WHERE status = '{{ dropdown.value }}'
  -- or use the specific views above
  ```

### List Sessions (simple array)
- **Resource**: MTL Backend
- **Method**: GET
- **URL**: `/api/retool/sessions`
- **Optional params**: `?limit=1000` (default 1000)
- **Response**: Array of session objects with courseOffering relationship
- **Note**: Sessions include `course_offering` nested object

### Get All Data in One Call (optional convenience)
- **Resource**: MTL Backend
- **Method**: GET
- **URL**: `/api/retool/all`
- **Response**:
  ```json
  {
    "students": [...],
    "course_offerings": [...],
    "sessions": [...]
  }
  ```

Access in Retool:
- Students: `{{ getAllData.data.students }}`
- Courses: `{{ getAllData.data.course_offerings }}`
- Sessions: `{{ getAllData.data.sessions }}`

### Create a Student
- **Resource**: MTL Backend
- **Method**: POST
- **URL**: `/api/students`
- **Body** (JSON):
  ```json
  {
    "first_name": "{{ form.first_name.value }}",
    "last_name": "{{ form.last_name.value }}",
    "email": "{{ form.email.value }}",
    "phone": "{{ form.phone.value }}",
    "initial_level": "{{ form.initial_level.value }}",
    "current_level": "{{ form.current_level.value }}",
    "profile_notes": "{{ form.profile_notes.value }}"
  }
  ```
- **Response**: Created student object with id

### Update a Student
- **Resource**: MTL Backend
- **Method**: PUT
- **URL**: `/api/students/{{ table.selectedRow.data.id }}`
- **Body** (JSON): Send only fields you want to update
  ```json
  {
    "phone": "{{ form.phone.value }}"
  }
  ```

### Delete a Student
- **Resource**: MTL Backend
- **Method**: DELETE
- **URL**: `/api/students/{{ table.selectedRow.data.id }}`
- **Response**: `{ "status": "deleted" }`

### Create a Course Offering
- **Resource**: MTL Backend
- **Method**: POST
- **URL**: `/api/course_offerings`
- **Body** (JSON):
  ```json
  {
    "course_key": "{{ form.course_key.value }}",
    "course_full_name": "{{ form.course_full_name.value }}",
    "level": "{{ form.level.value }}",
    "program": "{{ form.program.value }}",
    "start_date": "{{ form.start_date.value }}",
    "end_date": "{{ form.end_date.value }}",
    "hours_total": {{ form.hours_total.value }},
    "schedule": {{ form.schedule.value }},
    "price": "{{ form.price.value }}",
    "capacity": {{ form.capacity.value }},
    "location": "{{ form.location.value }}",
    "online": {{ form.online.value }}
  }
  ```

### Create a Session
- **Resource**: MTL Backend
- **Method**: POST
- **URL**: `/api/sessions`
- **Body** (JSON):
  ```json
  {
    "course_offering_id": {{ form.course_offering_id.value }},
    "date": "{{ form.date.value }}",
    "start_time": "{{ form.start_time.value }}",
    "end_time": "{{ form.end_time.value }}",
    "teacher_id": {{ form.teacher_id.value }}
  }
  ```
- **Response**: Created session object with id, courseOffering and teacher relationships
- **Note**: `teacher_id` is optional and references the `users` table

### Update a Session
- **Resource**: MTL Backend
- **Method**: PUT
- **URL**: `/api/sessions/{{ table.selectedRow.data.id }}`
- **Body** (JSON): Send only fields you want to update
  ```json
  {
    "teacher_id": {{ form.teacher_id.value }},
    "start_time": "{{ form.start_time.value }}"
  }
  ```

### Delete a Session
- **Resource**: MTL Backend
- **Method**: DELETE
- **URL**: `/api/sessions/{{ table.selectedRow.data.id }}`
- **Response**: `{ "status": "deleted" }`

## Step 4: CORS Support

CORS is enabled for all API endpoints via the `CorsMiddleware`. If you need to restrict origins:
- Edit `app/Http/Middleware/CorsMiddleware.php`
- Change `Access-Control-Allow-Origin` from `*` to a specific domain (e.g., `https://yourdomain.retool.com`)

## Step 5: Test the Integration

### Quick test from command line (server):
```bash
# Get the retool token from the seeder output
TOKEN="paste_token_here"

# Test students endpoint
curl -i -H "Authorization: Bearer $TOKEN" https://mixtreelangdb.nl/api/retool/students

# Test course offerings endpoint
curl -i -H "Authorization: Bearer $TOKEN" https://mixtreelangdb.nl/api/retool/course_offerings
```

Expected: 200 OK with JSON array.

## Available Endpoints Summary

### Database Views (Recommended for Retool Tables)

These pre-built views provide filtered, aggregated data optimized for common use cases:

**Student Views:**
- `active_students` — Active students with enrollment summary
- `archived_students` — Inactive students with last activity date

**Course Views:**
- `active_course_offerings` — Active courses with enrollment stats and timing
- `upcoming_courses` — Courses not yet started (sorted by start_date)
- `ongoing_courses` — Currently running courses with session progress
- `completed_courses` — Finished courses with completion statistics

**Usage in Retool:**
Create a SQL query resource pointing to your database, then use:
```sql
SELECT * FROM active_students;
SELECT * FROM upcoming_courses;
-- etc.
```

### Retool-Optimized (simple arrays):
- `GET /api/retool/students` — returns array of students (limit param optional)
- `GET /api/retool/course_offerings` — returns array of courses (limit param optional)
- `GET /api/retool/sessions` — returns array of sessions with course_offering relationship (limit param optional)
- `GET /api/retool/all` — returns { students: [...], course_offerings: [...], sessions: [...] }

### Full CRUD (paginated Laravel responses):
- `GET /api/students` — paginated list
- `POST /api/students` — create
- `GET /api/students/{id}` — show one
- `PUT /api/students/{id}` — update
- `DELETE /api/students/{id}` — delete
- Same pattern for `/api/course_offerings`
- Same pattern for `/api/sessions`

### Auth:
- `POST /api/auth/login` — get token (email/password) — returns `{ token, user }`
- `POST /api/auth/logout` — invalidate token (requires token)
- `GET /api/auth/me` — get current user (requires token)

### Legacy Retool helpers:
- `POST /api/record-payment`
- `POST /api/trigger-import`

## Security Notes

- The Retool service user has role `retool` and no password (API token only).
- Rotate the token periodically by re-running the seeder or updating the DB directly.
- If you deploy to production, consider IP allowlisting or rate-limiting on protected endpoints.
- Never commit tokens to git; store them in Retool securely.

## Troubleshooting

- **401 Unauthorized**: Check that the Authorization header is set correctly in Retool resource.
- **CORS errors**: Ensure `CorsMiddleware` is applied (it's global in the bootstrap).
- **Empty arrays**: Seed some data first or check DB connection in the app logs (`storage/logs/lumen.log`).
- **500 errors**: Tail app logs on the server:
  ```bash
  tail -n 200 -f /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/storage/logs/lumen.log
  ```

## Next Steps

- Build Retool tables/forms using the queries above.
- Add more endpoints as needed (enrollments, sessions, attendance, payments, etc.).
- Implement role-based access if you need to restrict certain operations.

