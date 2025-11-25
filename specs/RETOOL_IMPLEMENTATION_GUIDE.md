# Retool Implementation Guide - MTL App

## Overview

This guide provides practical implementation steps for building Retool apps using the new database views and API endpoints. All views are optimized for performance and ready to use directly in Retool.

---

## Database Connection Setup

### 1. Create MySQL Resource in Retool

**Connection Details:**
- Host: `mixtreelangdb.nl` (or server IP)
- Port: `3306`
- Database: `u5021d9810_mtldb`
- Username: Create dedicated read-only user (recommended)
- Password: Store in Retool Secret Manager

### 2. Create Read-Only Database User (Recommended)

```sql
-- Create read-only user for Retool
CREATE USER 'retool_readonly'@'%' IDENTIFIED BY 'SECURE_PASSWORD_HERE';

-- Grant SELECT on all tables and views
GRANT SELECT ON u5021d9810_mtldb.* TO 'retool_readonly'@'%';

-- Optionally grant write access to specific tables
GRANT INSERT, UPDATE ON u5021d9810_mtldb.leads TO 'retool_readonly'@'%';
GRANT INSERT, UPDATE ON u5021d9810_mtldb.students TO 'retool_readonly'@'%';
GRANT INSERT, UPDATE, DELETE ON u5021d9810_mtldb.enrollments TO 'retool_readonly'@'%';
GRANT INSERT, UPDATE, DELETE ON u5021d9810_mtldb.activities TO 'retool_readonly'@'%';

FLUSH PRIVILEGES;
```

---

## Database Views - Usage Guide

### Student Management

#### 1. Student Overview Dashboard
**View:** `student_overview`

**Purpose:** Main student list with enrollment statistics

**Retool Table Query:**
```sql
SELECT * FROM student_overview
ORDER BY last_enrollment_date DESC;
```

**Key Columns:**
- `total_enrollments` - Total courses enrolled
- `active_enrollments` - Currently active/registered
- `completed_courses` - Successfully completed
- `student_since` - Registration date

**Use Cases:**
- Main student directory table
- Student search/filter interface
- Quick enrollment status overview

---

#### 2. Student Course History
**View:** `student_course_history`

**Purpose:** Complete enrollment history for a student

**Retool Query (Filtered by Student):**
```sql
SELECT * FROM student_course_history
WHERE student_id = {{ studentTable.selectedRow.id }}
ORDER BY enrolled_at DESC;
```

**Key Columns:**
- `enrollment_status` - Current status
- `course_level` - Course difficulty
- `mid_course_level` - Progress assessment
- `mid_course_notes` - Teacher notes

**Use Cases:**
- Student detail page timeline
- Academic progression tracking
- Historical record review

---

### Enrollment Management

#### 3. Active Enrollments Detail
**View:** `active_enrollments_detail`

**Purpose:** Current enrollments with full course and student details

**Retool Table Query:**
```sql
SELECT * FROM active_enrollments_detail
WHERE status IN ('pending', 'registered', 'active')
ORDER BY start_date ASC;
```

**Filterable Columns:**
- `status` - pending/registered/active
- `level` - Course difficulty
- `program` - Course program
- `days_until_start` - Upcoming courses

**Use Cases:**
- Main enrollment management dashboard
- Upcoming course participant lists
- Quick access to student contact info

---

#### 4. Pending Enrollments - Action Required
**View:** `pending_enrollments_action_required`

**Purpose:** Priority list for pending enrollments needing follow-up

**Retool Table Query:**
```sql
SELECT * FROM pending_enrollments_action_required
ORDER BY days_pending DESC;
```

**Key Columns:**
- `priority` - Urgent/Follow up/Recent
- `days_pending` - How long waiting
- `has_payment` - Payment received flag

**Action Buttons:**
1. **Activate Enrollment** - Calls API endpoint
2. **Contact Student** - Pre-fills email/phone
3. **View Payment** - Links to payment view

**Retool Button Action (Activate):**
```javascript
// API Query: activateEnrollment
// Method: POST
// URL: https://mixtreelangdb.nl/api/enrollments/{{ table.selectedRow.enrollment_id }}/activate
// Headers: { "Authorization": "Bearer {{ secrets.API_TOKEN }}" }
// Body: { "reason": {{ reasonInput.value }} }
```

---

### Financial Management

#### 5. Payment Status Overview
**View:** `payment_status_overview`

**Purpose:** Track payment status for all enrollments

**Retool Table Query:**
```sql
SELECT * FROM payment_status_overview
WHERE payment_status IN ('Unpaid', 'Partial')
ORDER BY balance_due DESC;
```

**Key Columns:**
- `course_price` - Total amount due
- `total_paid` - Amount received
- `balance_due` - Outstanding balance
- `payment_status` - Paid/Partial/Unpaid/Override

**Color Coding (Retool):**
```javascript
// In table column mapper
{{ 
  item.payment_status === 'Paid' ? 'green' :
  item.payment_status === 'Partial' ? 'orange' :
  item.payment_status === 'Unpaid' ? 'red' :
  'blue'  // Override
}}
```

---

### Course Planning

#### 6. Course Offerings Summary
**View:** `course_offerings_summary`

**Purpose:** Course capacity planning and enrollment tracking

**Retool Table Query:**
```sql
SELECT * FROM course_offerings_summary
WHERE course_status IN ('Upcoming', 'In Progress')
ORDER BY start_date ASC;
```

**Key Metrics:**
- `current_enrollments` - Active students
- `pending_enrollments` - Awaiting activation
- `seats_available` - Remaining capacity
- `course_status` - Upcoming/In Progress/Completed

**Use Cases:**
- Course scheduling dashboard
- Capacity management
- Enrollment planning

---

#### 7. Upcoming Courses - Attention Needed
**View:** `upcoming_courses_attention`

**Purpose:** Courses requiring administrative action

**Retool Table Query:**
```sql
SELECT * FROM upcoming_courses_attention
WHERE attention_flag != 'Normal'
ORDER BY days_until_start ASC;
```

**Alert Types:**
- `Urgent - Pending enrollments need activation`
- `Starting soon` (within 3 days)
- `Almost full` (2 or fewer seats)

**Auto-Refresh:** Set to refresh every 30 minutes

---

### Marketing Analytics

#### 8. Marketing Source Performance
**View:** `marketing_source_performance`

**Purpose:** ROI analysis by lead source and channel

**Retool Chart/Table Query:**
```sql
SELECT * FROM marketing_source_performance
ORDER BY total_revenue DESC;
```

**Key Metrics:**
- `conversion_rate` - Lead to student %
- `total_revenue` - Generated revenue
- `revenue_per_lead` - ROI metric

**Retool Charts:**
1. **Bar Chart:** Revenue by marketing channel
2. **Pie Chart:** Lead distribution by source
3. **Line Chart:** Conversion rate trends

---

#### 9. Lead Conversion Funnel
**View:** `lead_conversion_funnel`

**Purpose:** Track lead progression through conversion stages

**Retool Table Query:**
```sql
SELECT * FROM lead_conversion_funnel
WHERE student_id IS NOT NULL
ORDER BY converted_date DESC;
```

**Key Columns:**
- `days_to_conversion` - Time to convert
- `total_enrollments` - Post-conversion activity
- `total_revenue` - Customer lifetime value

---

### Activity Tracking

#### 10. Recent Activities Timeline
**View:** `recent_activities_timeline`

**Purpose:** Global activity feed across all entities

**Retool Timeline Query:**
```sql
SELECT * FROM recent_activities_timeline
ORDER BY created_at DESC
LIMIT 50;
```

**Filter by Entity:**
```sql
SELECT * FROM recent_activities_timeline
WHERE entity_type = 'Student' AND entity_id = {{ selectedStudentId }}
ORDER BY created_at DESC;
```

**Timeline Component Setup:**
- **Title:** `subject`
- **Subtitle:** `entity_name` + `activity_type`
- **Body:** `body`
- **Timestamp:** `created_at`

---

## API Endpoints for Retool

### Authentication
All API requests require Bearer token:

```javascript
// In Retool Resource Headers
{
  "Authorization": "Bearer A317F31717358A2C316D9758857028526ABD0BC53D4399FA",
  "Content-Type": "application/json"
}
```

### 1. Create Activity
**Endpoint:** `POST /api/activities`

```javascript
// Retool Query: createActivity
{
  "entity_type": "Student",  // or "Lead", "Enrollment"
  "entity_id": {{ studentId }},
  "activity_type": "note",   // note, call, email, meeting, payment
  "subject": {{ subjectInput.value }},
  "body": {{ bodyTextarea.value }}
}
```

### 2. Create Pending Enrollment
**Endpoint:** `POST /api/enrollments`

```javascript
// Retool Query: createEnrollment
{
  "student_id": {{ studentDropdown.value }},
  "course_offering_id": {{ courseDropdown.value }},
  "status": "pending"
}
```

### 3. Activate Enrollment
**Endpoint:** `POST /api/enrollments/{id}/activate`

```javascript
// Retool Query: activateEnrollment
// URL: https://mixtreelangdb.nl/api/enrollments/{{ enrollmentId }}/activate
{
  "reason": {{ overrideReasonInput.value }}
}
```

### 4. Get Activities for Entity
**Endpoint:** `GET /api/activities?entity_type={type}&entity_id={id}`

```javascript
// Retool Query: getActivities
// URL: https://mixtreelangdb.nl/api/activities?entity_type={{ entityType }}&entity_id={{ entityId }}
```

---

## Retool App Templates

### Template 1: Student Management Dashboard

**Components:**
1. **Student Table** → Query: `student_overview`
2. **Detail Panel** → Shows selected student info
3. **Course History Table** → Query: `student_course_history` (filtered)
4. **Activity Timeline** → Query: `recent_activities_timeline` (filtered)
5. **Add Activity Button** → Calls `createActivity` API

**Layout:**
```
┌─────────────────────────────────────┬──────────────────┐
│ Student Table (filterable)          │ Selected Student │
│                                     │ - Details        │
│                                     │ - Course History │
│                                     │ - Activity Log   │
│                                     │ [+ Add Activity] │
└─────────────────────────────────────┴──────────────────┘
```

---

### Template 2: Enrollment Management Dashboard

**Components:**
1. **Status Tabs** → Pending / Active / Registered
2. **Enrollment Table** → Query: `active_enrollments_detail`
3. **Pending Actions Panel** → Query: `pending_enrollments_action_required`
4. **Activate Modal** → Form with reason input
5. **Payment Status** → Query: `payment_status_overview`

**Workflow:**
1. Select pending enrollment from table
2. Click "Activate" button
3. Modal opens with reason input
4. Submit calls `activateEnrollment` API
5. Table refreshes automatically

---

### Template 3: Course Planning Dashboard

**Components:**
1. **Course Table** → Query: `course_offerings_summary`
2. **Alert Banner** → Query: `upcoming_courses_attention` (attention_flag != Normal)
3. **Capacity Chart** → Visualize seats_available
4. **Enrollment Breakdown** → Pie chart by status

**Key Features:**
- Auto-refresh every 30 minutes
- Color-coded capacity warnings
- Quick links to enrollment lists

---

### Template 4: Marketing Analytics Dashboard

**Components:**
1. **KPI Metrics** → Total leads, conversion rate, revenue
2. **Source Performance Table** → Query: `marketing_source_performance`
3. **Conversion Funnel Chart** → Query: `lead_conversion_funnel`
4. **Revenue by Channel** → Bar chart
5. **Date Range Filter** → Filter by lead creation date

**Metrics:**
```sql
-- Total Leads (Stat component)
SELECT COUNT(*) as total FROM leads;

-- Conversion Rate (Stat component)
SELECT ROUND(COUNT(DISTINCT s.id) * 100.0 / COUNT(DISTINCT l.id), 2) as rate
FROM leads l
LEFT JOIN students s ON l.id = s.lead_id;

-- Total Revenue (Stat component)
SELECT SUM(total_revenue) FROM marketing_source_performance;
```

---

## Lead Forms - New Fields

### Lead Creation/Edit Form

**Reference Dropdown:**
```javascript
// Options
[
  { label: "Online Form", value: "online_form" },
  { label: "Level Check", value: "level_check" },
  { label: "Phone Call", value: "phone_call" },
  { label: "Walk-in", value: "walk_in" },
  { label: "Referral", value: "referral" },
  { label: "Other", value: "other" }
]
```

**Source Detail Dropdown:**
```javascript
// Options
[
  { label: "Google", value: "google" },
  { label: "Facebook", value: "facebook" },
  { label: "Instagram", value: "instagram" },
  { label: "AI/LinkedIn", value: "ai" },
  { label: "LinkedIn", value: "linkedin" },
  { label: "Referral Name", value: "referral_name" },
  { label: "Website Direct", value: "website_direct" },
  { label: "Other", value: "other" }
]
```

**Submit Query:**
```javascript
// Update Lead API
// Method: PUT
// URL: https://mixtreelangdb.nl/api/leads/{{ leadId }}
{
  "first_name": {{ firstNameInput.value }},
  "last_name": {{ lastNameInput.value }},
  "email": {{ emailInput.value }},
  "phone": {{ phoneInput.value }},
  "reference": {{ referenceDropdown.value }},
  "source_detail": {{ sourceDetailDropdown.value }}
}
```

---

## Performance Optimization Tips

### 1. Use Views Instead of Complex Joins
❌ **Don't:**
```sql
SELECT s.*, COUNT(e.id) as enrollment_count
FROM students s
LEFT JOIN enrollments e ON s.id = e.student_id
GROUP BY s.id;
```

✅ **Do:**
```sql
SELECT * FROM student_overview;
```

### 2. Cache Frequently Used Queries
- Enable "Cache query results" in Retool resource settings
- Set cache TTL to 5-15 minutes for dashboards
- Use manual refresh for real-time data

### 3. Limit Result Sets
```sql
-- Add pagination
SELECT * FROM active_enrollments_detail
LIMIT {{ table.pageSize }} OFFSET {{ table.pageOffset }};

-- Add filters
SELECT * FROM student_overview
WHERE email LIKE {{ '%' + searchInput.value + '%' }}
LIMIT 100;
```

### 4. Index Usage
Views automatically use indexes from underlying tables. No additional indexing needed.

---

## Security Best Practices

### 1. API Token Management
- Store API token in Retool Secret Manager
- Never hardcode in queries
- Rotate tokens regularly

### 2. Database User Permissions
- Use read-only user for analytics views
- Grant write access only to specific tables
- Use stored procedures for complex operations

### 3. Input Validation
```javascript
// Validate before API call
if (!emailInput.value || !emailInput.value.includes('@')) {
  utils.showNotification({
    title: 'Invalid Email',
    message: 'Please enter a valid email address',
    notificationType: 'error'
  });
  return;
}
```

### 4. Parameterized Queries
Always use {{ }} bindings, never string concatenation:

❌ **Don't:**
```sql
SELECT * FROM students WHERE id = ' + studentId + ';
```

✅ **Do:**
```sql
SELECT * FROM students WHERE id = {{ studentId }};
```

---

## Common Workflows

### Workflow 1: Enroll New Student in Course
1. Create student (if not exists)
2. Create pending enrollment via API
3. Create invoice (manual or via API)
4. Student pays via Mollie
5. Webhook auto-activates enrollment
6. Activity logged automatically

### Workflow 2: Manual Enrollment Activation
1. View pending enrollments table
2. Select enrollment row
3. Click "Activate" button
4. Enter payment override reason
5. Submit → API call → Status updates
6. Table refreshes

### Workflow 3: Track Marketing ROI
1. Open marketing analytics dashboard
2. Select date range
3. View `marketing_source_performance` table
4. Sort by `revenue_per_lead`
5. Identify best performing channels
6. Export data for reporting

---

## Troubleshooting

### View Not Found
```sql
-- Check if view exists
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Recreate view from database/views/useful_database_views.sql
```

### Slow Query Performance
```sql
-- Check execution plan
EXPLAIN SELECT * FROM student_overview;

-- Add indexes to underlying tables if needed
CREATE INDEX idx_enrollments_student_status ON enrollments(student_id, status);
```

### API 500 Errors
- Check Laravel logs: `storage/logs/lumen.log`
- Verify API token in request headers
- Check required fields in request body

---

## Next Steps

1. **Create Retool MySQL resource** with connection details
2. **Import view queries** as saved queries in Retool
3. **Build student management dashboard** (Template 1)
4. **Add enrollment workflow** (Template 2)
5. **Configure webhook in Mollie** dashboard
6. **Set up marketing analytics** (Template 4)
7. **Train team** on new features

---

## Resources

- **API Documentation:** See `FEATURE_DEPLOYMENT_GUIDE.md`
- **Database Views:** See `database/views/useful_database_views.sql`
- **Migration Notes:** See `DEPLOYMENT_NOTES.md`
- **Retool Docs:** https://docs.retool.com/docs/mysql-integration
