# MTL App - Complete Retool Frontend Guide

## Table of Contents
1. [Initial Setup](#initial-setup)
2. [Dashboard Overview Page](#dashboard-overview-page)
3. [Student Management](#student-management)
4. [Lead Management](#lead-management)
5. [Course & Session Management](#course--session-management)
6. [Enrollment Workflow](#enrollment-workflow)
7. [Invoice & Payment Management](#invoice--payment-management)
8. [Booking Management](#booking-management)
9. [Task Management](#task-management)
10. [Reports & Analytics](#reports--analytics)

---

## Initial Setup

### 1. Create REST API Resource
**Name**: `MTL_API`

**Configuration**:
- Base URL: `https://mixtreelangdb.nl`
- Headers:
  - Key: `Authorization`
  - Value: `Bearer A317F31717358A2C316D9758857028526ABD0BC53D4399FA`
  - Key: `Content-Type`
  - Value: `application/json`

**Test**: Click "Test" button to verify connection returns 200 OK.

---

## Dashboard Overview Page

### Page Layout
Create a new app called "MTL School Management" with a dashboard as the home page.

### Components Needed

#### 1. **Statistics Cards** (Top Row)
Use 4 **Statistic** components in a horizontal container:

**Card 1: Total Students**
- Query: `getOverview`
  ```
  Resource: MTL_API
  Method: GET
  URL: /api/retool/all
  ```
- Primary value: `{{ getOverview.data.students.length }}`
- Label: "Active Students"
- Icon: "User"

**Card 2: Active Enrollments**
- Primary value: `{{ getOverview.data.enrollments.length }}`
- Label: "Active Enrollments"
- Icon: "BookOpen"

**Card 3: Pending Invoices**
- Primary value: `{{ getOverview.data.invoices.filter(i => i.status === 'pending').length }}`
- Label: "Pending Invoices"
- Icon: "FileText"

**Card 4: Pending Tasks**
- Primary value: `{{ getOverview.data.tasks.filter(t => t.status === 'pending').length }}`
- Label: "Pending Tasks"
- Icon: "CheckSquare"

#### 2. **Recent Leads Table**
Component: **Table**
- Query: `getRecentLeads`
  ```
  Resource: MTL_API
  Method: GET
  URL: /api/retool/leads?limit=10
  ```
- Data: `{{ getRecentLeads.data }}`
- Columns to show:
  - `first_name` (First Name)
  - `last_name` (Last Name)
  - `email` (Email)
  - `phone` (Phone)
  - `source` (Source)
  - `status` (Status)
  - `created_at` (Date Created)
- Action column: Button "Convert to Student" (links to lead conversion page)

#### 3. **Active Tasks List**
Component: **Table**
- Query: `getActiveTasks`
  ```
  Resource: MTL_API
  Method: GET
  URL: /api/retool/tasks
  ```
- Data: `{{ getActiveTasks.data.filter(t => t.status === 'pending') }}`
- Columns:
  - `title` (Task)
  - `priority` (Priority) - use tags with colors: high=red, medium=yellow, low=green
  - `due_date` (Due Date)
  - `description` (Description)
- Action column: Button "Complete" → triggers `completeTask` mutation

#### 4. **Quick Actions Container**
Component: **Container** with buttons in a row:

**Button 1: New Lead**
- Text: "+ New Lead"
- Style: Primary
- Action: Open modal `newLeadModal`

**Button 2: New Enrollment**
- Text: "+ New Enrollment"
- Style: Primary
- Action: Open modal `newEnrollmentModal`

**Button 3: Record Payment**
- Text: "+ Record Payment"
- Style: Success
- Action: Open modal `newPaymentModal`

**Button 4: Create Task**
- Text: "+ Create Task"
- Style: Default
- Action: Open modal `newTaskModal`

---

## Student Management

### Page: "Students"

#### Main Table Component
Component: **Table** (`studentsTable`)

**Query**: `getStudents`
```
Resource: MTL_API
Method: GET
URL: /api/retool/students
```

**Table Configuration**:
- Data: `{{ getStudents.data }}`
- Columns:
  - `id` (ID) - hidden by default
  - `first_name` (First Name)
  - `last_name` (Last Name)
  - `email` (Email)
  - `phone` (Phone)
  - `dob` (Date of Birth) - format as date
  - `country_of_origin` (Country)
  - `city_of_residence` (City)
  - `languages` (Languages)
  - `created_at` (Registered) - format as date

**Action Buttons** (in action column):
1. **Edit Button**
   - Icon: "Edit"
   - Action: 
     ```javascript
     // Set current student
     currentStudent.setValue(studentsTable.selectedRow.data);
     // Open edit modal
     editStudentModal.open();
     ```

2. **View Details Button**
   - Icon: "Eye"
   - Action: Navigate to student detail page with `{{ studentsTable.selectedRow.data.id }}`

**Search Bar** (above table):
Component: **Text Input**
- Placeholder: "Search students by name, email, or phone..."
- On change: Filter table
  ```javascript
  {{ getStudents.data.filter(s => 
    s.first_name.toLowerCase().includes(searchStudents.value.toLowerCase()) ||
    s.last_name.toLowerCase().includes(searchStudents.value.toLowerCase()) ||
    s.email.toLowerCase().includes(searchStudents.value.toLowerCase())
  ) }}
  ```

**Add Student Button** (top right):
Component: **Button**
- Text: "+ Add Student"
- Style: Primary
- Action: Open `newStudentModal`

#### New Student Modal
Component: **Modal** (`newStudentModal`)

**Form Components**:
1. **Text Input**: `firstName_new`
   - Label: "First Name *"
   - Required: true

2. **Text Input**: `lastName_new`
   - Label: "Last Name *"
   - Required: true

3. **Text Input**: `email_new`
   - Label: "Email *"
   - Required: true
   - Validation: Email format

4. **Text Input**: `phone_new`
   - Label: "Phone"

5. **Date Picker**: `dob_new`
   - Label: "Date of Birth"

6. **Text Input**: `countryOfOrigin_new`
   - Label: "Country of Origin"

7. **Text Input**: `cityOfResidence_new`
   - Label: "City of Residence"

8. **Text Input**: `languages_new`
   - Label: "Languages (comma-separated)"
   - Placeholder: "e.g., English, Spanish, French"

9. **Text Area**: `previousCourses_new`
   - Label: "Previous Courses (if any)"

**Submit Button**:
- Text: "Create Student"
- Query: `createStudent`
  ```
  Resource: MTL_API
  Method: POST
  URL: /api/students
  Body:
  {
    "first_name": {{ firstName_new.value }},
    "last_name": {{ lastName_new.value }},
    "email": {{ email_new.value }},
    "phone": {{ phone_new.value }},
    "dob": {{ dob_new.value }},
    "country_of_origin": {{ countryOfOrigin_new.value }},
    "city_of_residence": {{ cityOfResidence_new.value }},
    "languages": {{ languages_new.value }},
    "previous_courses": {{ previousCourses_new.value }}
  }
  ```
- Success event:
  ```javascript
  // Show success notification
  utils.showNotification({ title: 'Success', description: 'Student created successfully', notificationType: 'success' });
  // Refresh students table
  getStudents.trigger();
  // Close modal
  newStudentModal.close();
  // Reset form
  firstName_new.reset();
  lastName_new.reset();
  email_new.reset();
  phone_new.reset();
  dob_new.reset();
  countryOfOrigin_new.reset();
  cityOfResidence_new.reset();
  languages_new.reset();
  previousCourses_new.reset();
  ```

#### Edit Student Modal
Component: **Modal** (`editStudentModal`)

Same form fields as New Student Modal, but:
- Field names: `firstName_edit`, `lastName_edit`, etc.
- Default values populated from `currentStudent` temp state:
  - `firstName_edit.value = {{ currentStudent.value.first_name }}`
  - `lastName_edit.value = {{ currentStudent.value.last_name }}`
  - etc.

**Submit Button**:
- Query: `updateStudent`
  ```
  Resource: MTL_API
  Method: PUT
  URL: /api/students/{{ currentStudent.value.id }}
  Body: (same as create)
  ```

---

## Lead Management

### Page: "Leads"

#### Leads Table
Component: **Table** (`leadsTable`)

**Query**: `getLeads`
```
Resource: MTL_API
Method: GET
URL: /api/retool/leads
```

**Columns**:
- `first_name` (First Name)
- `last_name` (Last Name)
- `email` (Email)
- `phone` (Phone)
- `source` (Source) - tag component with color coding
- `status` (Status) - tag: new=blue, contacted=yellow, qualified=green, converted=gray, lost=red
- `preferred_course_type` (Interested In)
- `notes` (Notes) - truncate long text
- `created_at` (Created) - format as date

**Action Buttons**:
1. **Edit** - opens edit modal
2. **Convert to Student** - triggers conversion workflow
3. **Mark as Lost** - updates status to "lost"

**Filter Bar** (above table):
Component: **Select** (`leadStatusFilter`)
- Options: ["All", "new", "contacted", "qualified", "converted", "lost"]
- On change: Filter `getLeads.data` by status

Component: **Select** (`leadSourceFilter`)
- Options: ["All", "website", "referral", "walk-in", "social_media", "other"]
- On change: Filter by source

#### New Lead Modal
Component: **Modal** (`newLeadModal`)

**Form Fields**:
1. **Text Input**: `leadFirstName_new` - "First Name *" (required)
2. **Text Input**: `leadLastName_new` - "Last Name *" (required)
3. **Text Input**: `leadEmail_new` - "Email"
4. **Text Input**: `leadPhone_new` - "Phone"
5. **Select**: `leadSource_new` - "Source *" (required)
   - Options: ["website", "referral", "walk-in", "social_media", "other"]
6. **Select**: `leadStatus_new` - "Status"
   - Options: ["new", "contacted", "qualified", "converted", "lost"]
   - Default: "new"
7. **Text Input**: `leadPreferredCourseType_new` - "Interested Course Type"
8. **Text Area**: `leadNotes_new` - "Notes"

**Submit Query**: `createLead`
```
Resource: MTL_API
Method: POST
URL: /api/leads
Body:
{
  "first_name": {{ leadFirstName_new.value }},
  "last_name": {{ leadLastName_new.value }},
  "email": {{ leadEmail_new.value }},
  "phone": {{ leadPhone_new.value }},
  "source": {{ leadSource_new.value }},
  "status": {{ leadStatus_new.value || "new" }},
  "preferred_course_type": {{ leadPreferredCourseType_new.value }},
  "notes": {{ leadNotes_new.value }}
}
```

#### Convert Lead to Student Modal
Component: **Modal** (`convertLeadModal`)

**Display Lead Info** (read-only text):
- "Converting: {{ leadsTable.selectedRow.data.first_name }} {{ leadsTable.selectedRow.data.last_name }}"

**Additional Info Needed**:
1. **Date Picker**: `convertDob` - "Date of Birth *" (required)
2. **Text Input**: `convertCountry` - "Country of Origin *" (required)
3. **Text Input**: `convertCity` - "City of Residence"
4. **Text Input**: `convertLanguages` - "Languages"

**Two-Step Process**:

**Query 1**: `createStudentFromLead`
```
Resource: MTL_API
Method: POST
URL: /api/students
Body:
{
  "first_name": {{ leadsTable.selectedRow.data.first_name }},
  "last_name": {{ leadsTable.selectedRow.data.last_name }},
  "email": {{ leadsTable.selectedRow.data.email }},
  "phone": {{ leadsTable.selectedRow.data.phone }},
  "dob": {{ convertDob.value }},
  "country_of_origin": {{ convertCountry.value }},
  "city_of_residence": {{ convertCity.value }},
  "languages": {{ convertLanguages.value }}
}
```

**Query 2** (on success of Query 1): `markLeadConverted`
```
Resource: MTL_API
Method: PUT
URL: /api/leads/{{ leadsTable.selectedRow.data.id }}
Body:
{
  "status": "converted",
  "notes": {{ leadsTable.selectedRow.data.notes + "\n\nConverted to student on " + new Date().toISOString() }}
}
```

**Success Actions**:
```javascript
utils.showNotification({ title: 'Success', description: 'Lead converted to student!', notificationType: 'success' });
getLeads.trigger();
getStudents.trigger();
convertLeadModal.close();
```

---

## Course & Session Management

### Page: "Courses"

#### Courses Table
Component: **Table** (`coursesTable`)

**Query**: `getCourses`
```
Resource: MTL_API
Method: GET
URL: /api/retool/course_offerings
```

**Columns**:
- `name` (Course Name)
- `type` (Type) - tag: group=blue, private=purple, intensive=orange
- `level` (Level)
- `start_date` (Start Date)
- `end_date` (End Date)
- `price` (Price) - format as currency €
- `max_students` (Capacity)
- `book_included` (Book Included) - checkbox/badge
- `teacher_hourly_rate` (Teacher Rate) - format as currency
- `classroom_cost` (Room Cost) - format as currency

**Computed Column**: `profit_per_student`
```javascript
{{ 
  coursesTable.selectedRow.data.price - 
  (coursesTable.selectedRow.data.teacher_hourly_rate * 40) - // Assume 40 hours
  coursesTable.selectedRow.data.classroom_cost -
  coursesTable.selectedRow.data.admin_overhead 
}}
```

**Action Buttons**:
1. **Edit Course**
2. **View Sessions**
3. **Delete** (with confirmation)

#### New Course Modal
Component: **Modal** (`newCourseModal`)

**Form Fields**:
1. **Text Input**: `courseName_new` - "Course Name *" (required)
2. **Select**: `courseType_new` - "Type *"
   - Options: ["group", "private", "intensive"]
3. **Text Input**: `courseLevel_new` - "Level" (e.g., A1, A2, B1, B2, C1, C2)
4. **Date Picker**: `courseStartDate_new` - "Start Date *"
5. **Date Picker**: `courseEndDate_new` - "End Date *"
6. **Number Input**: `coursePrice_new` - "Price (€) *"
7. **Number Input**: `courseMaxStudents_new` - "Max Students"
8. **Number Input**: `courseTeacherRate_new` - "Teacher Hourly Rate (€)"
9. **Number Input**: `courseClassroomCost_new` - "Classroom Cost (€)"
10. **Number Input**: `courseAdminOverhead_new` - "Admin Overhead (€)"
11. **Checkbox**: `courseBookIncluded_new` - "Book Included"
12. **Text Area**: `courseDescription_new` - "Description"

**Submit Query**: `createCourse`
```
Resource: MTL_API
Method: POST
URL: /api/course_offerings
Body: (all fields from form)
```

#### Sessions Sub-Table
When "View Sessions" clicked, show modal with sessions for that course.

Component: **Table** (`sessionsTable`)

**Query**: `getSessions`
```
Resource: MTL_API
Method: GET
URL: /api/retool/sessions
```

**Filtered Data**: `{{ getSessions.data.filter(s => s.course_offering_id === coursesTable.selectedRow.data.id) }}`

**Columns**:
- `session_date` (Date)
- `start_time` (Start Time)
- `end_time` (End Time)
- `topic` (Topic)
- `classroom` (Room)

**Add Session Button**:
- Opens `newSessionModal` with `course_offering_id` pre-filled

#### New Session Modal
**Form Fields**:
1. **Date Picker**: `sessionDate_new` - "Date *"
2. **Time Input**: `sessionStartTime_new` - "Start Time *"
3. **Time Input**: `sessionEndTime_new` - "End Time *"
4. **Text Input**: `sessionTopic_new` - "Topic"
5. **Text Input**: `sessionClassroom_new` - "Classroom"
6. **Hidden Field**: `sessionCourseId_new` = `{{ coursesTable.selectedRow.data.id }}`

**Submit Query**: `createSession`
```
Resource: MTL_API
Method: POST
URL: /api/sessions
Body:
{
  "course_offering_id": {{ sessionCourseId_new.value }},
  "session_date": {{ sessionDate_new.value }},
  "start_time": {{ sessionStartTime_new.value }},
  "end_time": {{ sessionEndTime_new.value }},
  "topic": {{ sessionTopic_new.value }},
  "classroom": {{ sessionClassroom_new.value }}
}
```

---

## Enrollment Workflow

### Page: "Enrollments"

#### Enrollments Table
Component: **Table** (`enrollmentsTable`)

**Query**: `getEnrollments`
```
Resource: MTL_API
Method: GET
URL: /api/retool/enrollments
```

**Columns** (with joins):
- `student_id` - use transformer to show student name:
  ```javascript
  {{ getStudents.data.find(s => s.id === enrollmentsTable.selectedRow.data.student_id)?.first_name + ' ' + getStudents.data.find(s => s.id === enrollmentsTable.selectedRow.data.student_id)?.last_name }}
  ```
- `course_offering_id` - show course name (same transformer pattern)
- `enrollment_date` (Enrolled) - format as date
- `status` (Status) - tag: active=green, completed=blue, dropped=red, on_hold=yellow
- `mid_course_assessment` (Mid-Assessment)
- `final_grade` (Final Grade)
- `payment_status` (Payment) - tag: pending=red, partial=yellow, paid=green

**Filter Bar**:
- **Select**: Filter by status
- **Select**: Filter by student
- **Select**: Filter by course

**Action Buttons**:
1. **Edit** - update status, grades
2. **View Invoice** - navigate to associated invoice
3. **Record Attendance** - quick modal to mark attendance for next session

#### New Enrollment Modal
Component: **Modal** (`newEnrollmentModal`)

**Form Fields**:
1. **Select**: `enrollmentStudent_new` - "Student *" (required)
   - Options: `{{ getStudents.data }}`
   - Option labels: `{{ item.first_name + ' ' + item.last_name }}`
   - Option values: `{{ item.id }}`

2. **Select**: `enrollmentCourse_new` - "Course *" (required)
   - Options: `{{ getCourses.data }}`
   - Option labels: `{{ item.name + ' (' + item.start_date + ')' }}`
   - Option values: `{{ item.id }}`

3. **Select**: `enrollmentStatus_new` - "Status"
   - Options: ["active", "completed", "dropped", "on_hold"]
   - Default: "active"

4. **Text Input**: `enrollmentMidAssessment_new` - "Mid-Course Assessment"

5. **Number Input**: `enrollmentFinalGrade_new` - "Final Grade"

6. **Select**: `enrollmentPaymentStatus_new` - "Payment Status"
   - Options: ["pending", "partial", "paid"]
   - Default: "pending"

**Submit Query**: `createEnrollment`
```
Resource: MTL_API
Method: POST
URL: /api/enrollments
Body:
{
  "student_id": {{ enrollmentStudent_new.value }},
  "course_offering_id": {{ enrollmentCourse_new.value }},
  "status": {{ enrollmentStatus_new.value || "active" }},
  "mid_course_assessment": {{ enrollmentMidAssessment_new.value }},
  "final_grade": {{ enrollmentFinalGrade_new.value }},
  "payment_status": {{ enrollmentPaymentStatus_new.value || "pending" }}
}
```

**Success Actions**:
```javascript
// Create invoice automatically
createInvoiceForEnrollment.trigger({
  additionalScope: {
    studentId: enrollmentStudent_new.value,
    courseId: enrollmentCourse_new.value,
    amount: getCourses.data.find(c => c.id === enrollmentCourse_new.value).price
  }
});
getEnrollments.trigger();
newEnrollmentModal.close();
```

---

## Invoice & Payment Management

### Page: "Invoices"

#### Invoices Table
Component: **Table** (`invoicesTable`)

**Query**: `getInvoices`
```
Resource: MTL_API
Method: GET
URL: /api/retool/invoices
```

**Columns**:
- `invoice_number` (Invoice #)
- `student_id` - transformer to show student name
- `invoice_date` (Date) - format as date
- `total` (Total) - format as currency €
- `discount_percent` (Discount %)
- `discount_amount` (Discount €) - format as currency
- `status` (Status) - tag: pending=red, partial=yellow, paid=green, overdue=dark red
- `due_date` (Due Date) - format as date, highlight if overdue

**Computed Column**: `amount_paid`
```javascript
{{ getPayments.data.filter(p => p.invoice_id === invoicesTable.selectedRow.data.id && p.status === 'completed' && !p.is_refund).reduce((sum, p) => sum + parseFloat(p.amount), 0) }}
```

**Computed Column**: `balance_due`
```javascript
{{ invoicesTable.selectedRow.data.total - amount_paid }}
```

**Action Buttons**:
1. **View Items** - show invoice items in modal
2. **Record Payment** - opens payment modal
3. **Send Email** - trigger email sending
4. **Download PDF** - future enhancement

#### New Invoice Modal
Component: **Modal** (`newInvoiceModal`)

**Form Fields**:
1. **Select**: `invoiceStudent_new` - "Student *" (required)
   - Populate from `getStudents.data`

2. **Date Picker**: `invoiceDate_new` - "Invoice Date *"
   - Default: today

3. **Date Picker**: `invoiceDueDate_new` - "Due Date *"
   - Default: 30 days from today

4. **Select**: `invoiceDiscountRule_new` - "Apply Discount Rule" (optional)
   - Options: `{{ getDiscountRules.data.filter(d => d.is_active) }}`
   - Option labels: `{{ item.name + ' (' + item.percent + '%)' }}`
   - Option values: `{{ item.id }}`

5. **Number Input**: `invoiceDiscountPercent_new` - "Or Custom Discount %"

6. **JSON Editor**: `invoiceItems_new` - "Invoice Items *" (required)
   - Default value:
   ```json
   [
     {
       "description": "Course Fee",
       "amount": 0
     }
   ]
   ```

7. **Text (Read-only)**: Subtotal
   - Value: `{{ invoiceItems_new.value.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0) }}`

8. **Text (Read-only)**: Discount Amount
   - Value: `{{ (invoiceItems_new.value.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0) * (invoiceDiscountPercent_new.value || 0) / 100).toFixed(2) }}`

9. **Text (Read-only)**: Total
   - Value: `{{ (invoiceItems_new.value.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0) * (1 - (invoiceDiscountPercent_new.value || 0) / 100)).toFixed(2) }}`

**Submit Query**: `createInvoice`
```
Resource: MTL_API
Method: POST
URL: /api/invoices
Body:
{
  "student_id": {{ invoiceStudent_new.value }},
  "invoice_date": {{ invoiceDate_new.value }},
  "due_date": {{ invoiceDueDate_new.value }},
  "items": {{ invoiceItems_new.value }},
  "discount_percent": {{ invoiceDiscountPercent_new.value || 0 }},
  "discount_amount": {{ (invoiceItems_new.value.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0) * (invoiceDiscountPercent_new.value || 0) / 100) }},
  "total": {{ (invoiceItems_new.value.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0) * (1 - (invoiceDiscountPercent_new.value || 0) / 100)) }},
  "status": "pending"
}
```

#### Payments Table (Sub-section or separate tab)
Component: **Table** (`paymentsTable`)

**Query**: `getPayments`
```
Resource: MTL_API
Method: GET
URL: /api/retool/payments
```

**Columns**:
- `invoice_id` - show invoice number
- `payment_date` (Date) - format as date
- `amount` (Amount) - format as currency
- `payment_method` (Method) - tag colors
- `status` (Status) - tag: pending=yellow, completed=green, failed=red, refunded=gray
- `is_refund` (Refund) - checkbox/badge
- `mollie_payment_id` (Mollie ID)
- `notes` (Notes)

**Filter**: By invoice_id when viewing from invoice detail

#### New Payment Modal
Component: **Modal** (`newPaymentModal`)

**Form Fields**:
1. **Select**: `paymentInvoice_new` - "Invoice *" (required)
   - Options: `{{ getInvoices.data.filter(i => i.status !== 'paid') }}`
   - Option labels: `{{ item.invoice_number + ' - €' + item.total }}`
   - Option values: `{{ item.id }}`
   - Show balance due for selected invoice

2. **Number Input**: `paymentAmount_new` - "Amount *" (required)
   - Max: Balance due on invoice

3. **Date Picker**: `paymentDate_new` - "Payment Date *"
   - Default: today

4. **Select**: `paymentMethod_new` - "Payment Method *"
   - Options: ["cash", "bank_transfer", "credit_card", "ideal", "mollie"]

5. **Select**: `paymentStatus_new` - "Status"
   - Options: ["pending", "completed", "failed"]
   - Default: "completed"

6. **Text Input**: `paymentMollieId_new` - "Mollie Payment ID" (if method = mollie)

7. **Checkbox**: `paymentIsRefund_new` - "This is a refund"

8. **Text Area**: `paymentNotes_new` - "Notes"

**Submit Query**: `createPayment`
```
Resource: MTL_API
Method: POST
URL: /api/payments
Body:
{
  "invoice_id": {{ paymentInvoice_new.value }},
  "amount": {{ paymentAmount_new.value }},
  "payment_date": {{ paymentDate_new.value }},
  "payment_method": {{ paymentMethod_new.value }},
  "status": {{ paymentStatus_new.value || "completed" }},
  "mollie_payment_id": {{ paymentMollieId_new.value }},
  "is_refund": {{ paymentIsRefund_new.value || false }},
  "notes": {{ paymentNotes_new.value }}
}
```

**Success Actions**:
```javascript
utils.showNotification({ title: 'Success', description: 'Payment recorded!', notificationType: 'success' });
getPayments.trigger();
getInvoices.trigger(); // Refresh to see updated status
newPaymentModal.close();
```

---

## Booking Management

### Page: "Bookings"

#### Bookings Table
Component: **Table** (`bookingsTable`)

**Query**: `getBookings`
```
Resource: MTL_API
Method: GET
URL: /api/retool/bookings
```

**Columns**:
- `event_id` (Cal.com Event ID)
- `event_title` (Event Title)
- `booking_type` (Type) - tag: level_check=blue, trial=green, consultation=purple
- `scheduled_at` (Scheduled) - format as datetime
- `attendee_name` (Attendee)
- `attendee_email` (Email)
- `attendee_phone` (Phone)
- `status` (Status) - tag: scheduled=blue, completed=green, cancelled=red, no_show=gray
- `assigned_teacher` (Teacher)
- `notes` (Notes)

**Filter Bar**:
- Filter by status
- Filter by booking_type
- Filter by date range

**Action Buttons**:
1. **Mark Completed**
2. **Mark No-Show**
3. **Cancel Booking**
4. **Convert to Lead** - create lead from booking info

#### New Booking Modal
Component: **Modal** (`newBookingModal`)

**Form Fields**:
1. **Text Input**: `bookingEventId_new` - "Cal.com Event ID *"
2. **Text Input**: `bookingEventTitle_new` - "Event Title"
3. **Select**: `bookingType_new` - "Booking Type *"
   - Options: ["level_check", "trial", "consultation"]
4. **DateTime Picker**: `bookingScheduledAt_new` - "Scheduled Date/Time *"
5. **Text Input**: `bookingAttendeeName_new` - "Attendee Name *"
6. **Text Input**: `bookingAttendeeEmail_new` - "Attendee Email"
7. **Text Input**: `bookingAttendeePhone_new` - "Attendee Phone"
8. **Text Input**: `bookingAssignedTeacher_new` - "Assigned Teacher"
9. **Select**: `bookingStatus_new` - "Status"
   - Options: ["scheduled", "completed", "cancelled", "no_show"]
   - Default: "scheduled"
10. **JSON Editor**: `bookingMetadata_new` - "Additional Info (JSON)"
11. **Text Area**: `bookingNotes_new` - "Notes"

**Submit Query**: `createBooking`
```
Resource: MTL_API
Method: POST
URL: /api/bookings
Body: (all form fields)
```

---

## Task Management

### Page: "Tasks"

#### Tasks Kanban Board (or Table)
Component: **Kanban** or **Table** (`tasksTable`)

**Query**: `getTasks`
```
Resource: MTL_API
Method: GET
URL: /api/retool/tasks
```

**Kanban Configuration** (if using Kanban):
- Columns: Pending, In Progress, Completed
- Group by: `status`
- Card fields:
  - Title: `{{ item.title }}`
  - Description: `{{ item.description }}`
  - Due date: `{{ item.due_date }}`
  - Priority badge: `{{ item.priority }}`
  - Assigned to: `{{ item.assigned_to_user_id }}`

**Table Configuration** (if using Table):
- Columns:
  - `title` (Task)
  - `description` (Description)
  - `status` (Status) - tag: pending=yellow, completed=green
  - `priority` (Priority) - tag: low=gray, medium=blue, high=red
  - `due_date` (Due Date) - highlight if overdue
  - `assigned_to_user_id` (Assigned To)
  - `related_entity_type` (Related To)
  - `related_entity_id` (Entity ID)

**Filter Bar**:
- Filter by status
- Filter by priority
- Filter by assigned user
- Filter by due date (overdue, today, this week)

**Action Buttons**:
1. **Edit Task**
2. **Complete Task** - triggers `completeTask` query
3. **Delete**

#### New Task Modal
Component: **Modal** (`newTaskModal`)

**Form Fields**:
1. **Text Input**: `taskTitle_new` - "Title *" (required)
2. **Text Area**: `taskDescription_new` - "Description"
3. **Select**: `taskPriority_new` - "Priority"
   - Options: ["low", "medium", "high"]
   - Default: "medium"
4. **Date Picker**: `taskDueDate_new` - "Due Date"
5. **Number Input**: `taskAssignedTo_new` - "Assigned To (User ID)"
6. **Select**: `taskRelatedType_new` - "Related Entity Type"
   - Options: ["Student", "Lead", "Enrollment", "Invoice", "Booking", null]
7. **Number Input**: `taskRelatedId_new` - "Related Entity ID"
8. **Text Area**: `taskNotes_new` - "Notes"

**Submit Query**: `createTask`
```
Resource: MTL_API
Method: POST
URL: /api/tasks
Body:
{
  "title": {{ taskTitle_new.value }},
  "description": {{ taskDescription_new.value }},
  "status": "pending",
  "priority": {{ taskPriority_new.value || "medium" }},
  "due_date": {{ taskDueDate_new.value }},
  "assigned_to_user_id": {{ taskAssignedTo_new.value }},
  "related_entity_type": {{ taskRelatedType_new.value }},
  "related_entity_id": {{ taskRelatedId_new.value }},
  "notes": {{ taskNotes_new.value }}
}
```

#### Complete Task Query
Query: `completeTask`
```
Resource: MTL_API
Method: POST
URL: /api/tasks/{{ tasksTable.selectedRow.data.id }}/complete
Body: {}
```

**Success**: Refresh tasks and show notification

---

## Reports & Analytics

### Page: "Reports"

#### Revenue Dashboard

**Component 1: Total Revenue Card**
- Query all invoices: `getInvoices`
- Calculate: `{{ getInvoices.data.filter(i => i.status === 'paid').reduce((sum, i) => sum + parseFloat(i.total), 0) }}`
- Display as large statistic with currency format

**Component 2: Revenue by Month Chart**
Component: **Chart** (Line or Bar)
- X-axis: Month
- Y-axis: Total revenue
- Data transformer:
```javascript
{{
  getInvoices.data
    .filter(i => i.status === 'paid')
    .reduce((acc, invoice) => {
      const month = new Date(invoice.invoice_date).toLocaleString('default', { month: 'short', year: 'numeric' });
      acc[month] = (acc[month] || 0) + parseFloat(invoice.total);
      return acc;
    }, {})
}}
```

**Component 3: Course Profitability Table**
Component: **Table**

Data: `{{ getCourses.data }}`

Columns:
- `name` (Course)
- Enrolled students (count from enrollments)
- Revenue (price × enrolled)
- Costs (teacher_rate × hours + classroom + overhead)
- Profit (Revenue - Costs)
- Profit margin %

**Component 4: Student Acquisition Chart**
Component: **Chart** (Line)
- Show students registered per month
- Show leads generated per month
- Conversion rate line

**Component 5: Top Discount Rules Table**
Component: **Table**
- Data: `{{ getDiscountRules.data }}`
- Columns:
  - `name` (Rule)
  - `percent` (Discount %)
  - `rule_type` (Type)
  - Times used (count invoices with this discount)
  - Total discount given

#### Attendance Report

**Component: Attendance Table**
Query: `getAttendanceRecords`
```
Resource: MTL_API
Method: GET
URL: /api/retool/attendance_records
```

**Filters**:
- By student
- By session
- By date range
- By status (present/absent/late)

**Aggregate Stats**:
- Overall attendance rate: `{{ (getAttendanceRecords.data.filter(a => a.status === 'present').length / getAttendanceRecords.data.length * 100).toFixed(1) }}%`
- Students at risk (<80% attendance)

#### Certificate Export Report

Component: **Table** (`certificatesTable`)

Query: `getCertificates`
```
Resource: MTL_API
Method: GET
URL: /api/retool/certificate_exports
```

Columns:
- Student name
- Course name
- Attendance %
- Eligible (Yes/No badge)
- Export status
- Exported at

Filter: Show only eligible students

**Action**: Bulk export button to mark as exported

---

## Navigation Menu

Create a left sidebar or top navigation with these items:

1. **Dashboard** (home icon)
2. **Students** (users icon)
3. **Leads** (user-plus icon)
4. **Courses** (book icon)
5. **Enrollments** (clipboard icon)
6. **Invoices** (file-text icon)
7. **Payments** (dollar-sign icon)
8. **Bookings** (calendar icon)
9. **Tasks** (check-square icon)
10. **Reports** (bar-chart icon)
11. **Settings** (gear icon) - for discount rules, etc.

---

## Global Queries (Run on App Load)

Set these queries to run automatically when app loads:

1. `getOverview` - /api/retool/all
2. `getStudents` - /api/retool/students
3. `getCourses` - /api/retool/course_offerings
4. `getSessions` - /api/retool/sessions
5. `getLeads` - /api/retool/leads
6. `getEnrollments` - /api/retool/enrollments
7. `getInvoices` - /api/retool/invoices
8. `getPayments` - /api/retool/payments
9. `getBookings` - /api/retool/bookings
10. `getTasks` - /api/retool/tasks
11. `getDiscountRules` - /api/retool/discount_rules
12. `getCertificates` - /api/retool/certificate_exports
13. `getAttendanceRecords` - /api/retool/attendance_records

---

## Styling & UX Best Practices

### Color Scheme
- Primary: Blue (#1890ff) - for main actions
- Success: Green (#52c41a) - for completed/paid status
- Warning: Orange (#faad14) - for pending/partial
- Error: Red (#f5222d) - for overdue/failed
- Info: Cyan (#13c2c2) - for informational badges

### Status Tag Colors
**Invoice Status**:
- pending: Orange
- partial: Yellow
- paid: Green
- overdue: Dark Red

**Lead Status**:
- new: Blue
- contacted: Purple
- qualified: Green
- converted: Gray
- lost: Red

**Task Priority**:
- low: Gray
- medium: Blue
- high: Red

### Notifications
Always show notifications on:
- Successful create/update/delete
- Errors with helpful messages
- Async operations completion

### Loading States
Use loading spinners on:
- Table data fetching
- Form submissions
- Modal opens that load data

### Validation
- Mark required fields with *
- Show validation errors inline
- Disable submit buttons until form is valid
- Use helpful placeholder text

---

## Quick Start Checklist

- [ ] Create MTL_API resource with bearer token
- [ ] Test /api/retool/all endpoint
- [ ] Build Dashboard page with stats cards
- [ ] Create Students page with table and CRUD modal
- [ ] Create Leads page with table and conversion workflow
- [ ] Create Courses page with table
- [ ] Create Enrollments page
- [ ] Create Invoices page with payment sub-section
- [ ] Create Bookings page
- [ ] Create Tasks page (Kanban or Table)
- [ ] Create Reports page with charts
- [ ] Add navigation menu
- [ ] Set up global queries to run on load
- [ ] Test full workflow: Lead → Student → Enrollment → Invoice → Payment
- [ ] Add custom branding/logo

---

## Advanced Features (Future)

1. **Automated Emails**: Trigger email on invoice creation, payment confirmation
2. **Mollie Integration**: Direct payment links in invoices
3. **Cal.com Webhook Listener**: Auto-create bookings from Cal.com events
4. **Bulk Operations**: Bulk invoice generation, bulk email sending
5. **PDF Generation**: Invoice PDFs, certificate PDFs
6. **Role-based Access**: Admin vs Coordinator vs Teacher views
7. **Audit Trail Viewer**: Show all changes to critical records
8. **Advanced Reporting**: Cohort analysis, retention rates, LTV calculations

---

**Documentation Version**: 1.0.0  
**Last Updated**: November 2025
