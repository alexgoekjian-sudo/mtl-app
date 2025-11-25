# Specification Update: Enhanced Course Types & Financial Management
**Date**: November 25, 2025
**Status**: Approved
**Impact**: Major - New course types, financial complexity, automation features

---

## Overview

This update extends the original specification to support:
1. **Three course types**: Group scheduled, Bespoke group, Private lessons
2. **Advanced financial features**: Teacher rates, partner discounts, student credits, automated discounts
3. **Operational automation**: Task reminders, three-level notes, custom fields
4. **Course management**: Pre-start modifications, mid-course transfers, profitability tracking

---

## Course Types (Updated FR-004)

### 1. Group Courses (Scheduled)
**Description**: Standard courses running on a fixed schedule with per-student enrollment and payment.

**Characteristics**:
- Fixed start and end dates
- Pre-defined schedule (days/times)
- Individual student invoicing
- Classroom or online delivery
- Books sometimes included, sometimes separate charge

**Implementation**: `course_offerings.course_type = 'group_scheduled'`

---

### 2. Bespoke Group Courses (NEW)

**Description**: Custom courses for in-company students or special groups. Can be on-site, online, or at third-party location.

**Characteristics**:
- Typically invoiced as bulk quotation to company/partner
- Invoice itemizes each student individually
- Individual student payments tracked against bulk invoice
- Students may withdraw (requires invoice adjustment)
- Students still require level checks
- Must appear as enrolled students in system
- Custom locations supported (client offices, third-party venues)

**Partner Invoicing**:
- Partners receive 10% discount on course rate per student (configurable)
- Partners charge their own rates to end students (not tracked in system)
- School communicates with students about educational matters only
- All registration and financial communication goes through partner

**Implementation**:
- `course_offerings.course_type = 'bespoke_group'`
- `course_offerings.is_bespoke = TRUE`
- `course_offerings.delivery_location = 'third_party'` (or onsite/online)
- `enrollments.partner_id` links to partner entity
- `invoices.invoice_type = 'partner_bulk'`
- `invoices.is_bulk_itemized = TRUE`

---

### 3. Private Lessons (NEW)

**Description**: One-to-one sessions with teacher assigned by school. Sold as packages with minimum 10 hours.

**Characteristics**:
- Sold as packages: 10h, 20h, 30h (standard) or custom hours
- Specialized packages: IELTS preparation, introductory courses
- Top-up options when package depleted
- Two-rate system:
  - Student rate (what student pays)
  - Teacher rate (what school pays teacher)
- Profit margin displayed: `student_rate - teacher_rate`
- Sessions scheduled ad-hoc (not in advance)
- No attendance tracking needed (teacher invoices for hours delivered)
- Student may be enrolled in group course AND private lessons simultaneously
- Invoiced together or separately (reporting must distinguish)
- Student history shows all previous courses and private lesson activity
- **Packages do NOT expire** - students schedule irregularly with teachers
- School invoices student in advance for package
- Teacher invoices school for hours delivered

**Implementation**:
- New table: `private_lesson_packages`
- New table: `private_lesson_sessions`
- `enrollments.package_id` for private lesson enrollments
- Invoice line items separate for group courses vs private lessons
- Student profile shows both course enrollments and package history

---

## Teacher Rates (NEW - FR-022)

**Description**: Centralized management of teacher rates with history tracking.

**Features**:
- Default rates per teacher:
  - Group course hourly rate
  - Private lesson hourly rate
- Ad-hoc rates for specific courses (override default)
- Rate history tracking for accuracy (effective_from, effective_to dates)
- Periodic rate reviews with audit trail

**Implementation**:
- New table: `teacher_rates`
- `course_offerings.ad_hoc_teacher_rate` for course-specific overrides
- Rate retrieval logic:
  1. Check ad_hoc_teacher_rate on course_offering
  2. Fall back to current default rate from teacher_rates
  3. Filter by effective_from <= course_start_date AND (effective_to IS NULL OR effective_to >= course_start_date)

---

## Payments & Invoicing (Enhanced)

### Part Payments (FR-010 Enhanced)
**Description**: System must support part payments independent of Mollie's built-in functionality.

**Implementation**:
- Multiple `payment` records per invoice
- `payments.is_partial = TRUE`
- Invoice status logic:
  - `paid` when SUM(payments.amount) >= invoice.total
  - `partial` when SUM(payments.amount) < invoice.total
  - `overdue` when due_date < NOW() AND status != 'paid'

---

### Discounts (NEW - FR-026)

**Types**:
1. **Automatic - Returning Students**: 5% discount by default
   - Triggered when `students.total_enrollments > 0`
   - Applied automatically at enrollment creation
   - Can be overridden manually

2. **Manual - Tailored**: Custom discounts per student/enrollment
   - Coordinator sets discount_percentage or discount_amount
   - Reason required for audit

3. **Free Courses**: Occasionally offered
   - `enrollments.is_free_course = TRUE`
   - `enrollments.final_price = 0`

4. **Partner Discount**: 10% default (configurable per partner)
   - Applied when `enrollments.partner_id IS NOT NULL`
   - Taken from `partners.discount_percentage`

**Implementation**:
- New table: `discount_rules` (define automatic rules)
- `enrollments.discount_rule_id` (links to rule applied)
- `enrollments.discount_percentage` (actual % applied)
- `enrollments.discount_amount` (actual € amount)
- `enrollments.is_free_course` (boolean flag)
- Calculation: `final_price = course_price * (1 - discount_percentage/100) - discount_amount`

---

### Advance Payments / Student Credit (NEW - FR-025)

**Description**: Students may pay in advance (e.g., employer budget) to cover multiple future courses/lessons.

**Features**:
- Creates credit on student account
- Credit applied when enrolling in courses
- System logs redemption internally
- Generates receipt/confirmation (not full invoice)
- **Credits expire after 1 year**
- Scheduled job marks expired credits

**Workflow**:
1. Student makes advance payment (invoice created)
2. Credit record created: `student_credits` (balance = amount, status = 'active')
3. Student enrolls in course
4. Coordinator applies credit to enrollment
5. System creates `credit_redemption` record
6. Updates `student_credits.balance -= amount_redeemed`
7. If balance = 0, status = 'depleted'
8. Updates `enrollments.credit_applied`
9. Generates receipt (email to student)

**Implementation**:
- New table: `student_credits`
- New table: `credit_redemptions`
- `students.credit_balance` (calculated field: SUM(active credits.balance))
- `enrollments.credit_applied`
- `enrollments.amount_owed = final_price + book_fee - credit_applied`
- `student_credits.expires_at` = created_at + 1 year
- Scheduled job: `UPDATE student_credits SET status='expired' WHERE expires_at < NOW() AND status='active'`

---

### Third-Party Partners (NEW - FR-024)

**Description**: Some students come through partners. School communicates with students about education only; all registration/invoicing through partner.

**Partner Details Stored**:
- Company name
- Contact information
- Agreed discount rate (typically 10%, configurable)

**Partner Association**:
- Set at enrollment level (not student level)
- Student may be linked to partner for one enrollment but enroll independently later

**Income Calculation**:
- Partner income = SUM(enrolled students) * (course_price * (1 - partner_discount_percentage/100))

**Implementation**:
- New table: `partners`
- `enrollments.partner_id` (nullable FK)
- `invoices.invoice_type = 'partner_bulk'` for partner invoices
- `invoices.partner_id` links invoice to partner
- `invoices.is_bulk_itemized = TRUE` (shows individual student line items)

---

### Non-Mollie Payments (FR-034)

**Description**: Fallback invoicing for payments outside Mollie.

**Payment Methods**:
- Cash (requires cash account)
- Bank transfer
- Manual entry

**Implementation**:
- `payments.payment_source ENUM('mollie', 'bank_transfer', 'cash', 'credit_redemption')`
- `invoices.payment_method` tracks preferred/actual method
- New table: `cash_accounts` (optional, for cash tracking)
- `invoices.cash_account_id` when payment_method = 'cash'

---

### Refunds (Existing - No Change)
**Status**: Already supported via negative Payment records.
**Process**: Manual actions by Finance, no automated proration.

---

## Location & Session Costs (NEW)

### Classroom Rates (FR-027)
**Description**: Classroom hourly rates vary by room.

**Features**:
- Each classroom has defined hourly_rate
- Rate applied to course cost calculation
- Cost = hours_total * classroom_hourly_rate

**Implementation**:
- New table: `classrooms` (name, location, hourly_rate, capacity)
- `course_offerings.classroom_id` (FK to classrooms)
- `course_offerings.classroom_hourly_rate` (cached from classroom for cost calc)
- `sessions.classroom_id` (specific session location)

---

### Online Session Costs (FR-027)
**Description**: Online sessions incur hosting cost (Zoom/Google Meet).

**Features**:
- Fixed cost per session or total cost for course
- Factored into course cost calculations
- Cost = online_hosting_cost (flat fee or per-session)

**Implementation**:
- `course_offerings.online_hosting_cost` (total cost for course)
- `sessions.hosting_cost` (per-session if needed)
- Included in `course_offerings.total_cost_calculated`

---

## Notes System (Enhanced - FR-028)

**Description**: Notes at three levels for different contexts.

**Levels**:
1. **Student Notes**: General notes about student (preferences, issues, history)
   - `activities` where `related_entity_type='Student'`

2. **Enrollment Notes**: Notes tied to specific enrollment (e.g., "Special discount offered for next course")
   - `activities` where `related_entity_type='Enrollment'`

3. **Course Offering Notes**: Notes about particular course offering (e.g., "Room change", "Substitute teacher week 3")
   - `activities` where `related_entity_type='CourseOffering'`

**Implementation**:
- Existing `activities` table extended
- `activities.related_entity_type ENUM` includes 'Enrollment', 'CourseOffering'
- UI filters notes by context (student profile shows student+enrollment notes, course page shows course notes)

---

## Automated Reminders & Task Management (NEW - FR-029)

**Description**: System generates automated tasks/reminders for key actions.

**Task Types**:

1. **Pre-Course Payment Chase**
   - Trigger: Course starts in 7 days AND enrollment.status = 'pending'
   - Task assigned to coordinator
   - Due date: 2 days before start
   - Body: "Student [name] enrolled in [course] has not paid. Invoice #[num], amount €[amt]"

2. **Lead Follow-Up**
   - Trigger: New lead created OR manual "follow up in X days"
   - Recurring reminders to re-establish contact
   - Due date: Configurable (3 days, 1 week, 2 weeks)

3. **Teacher Attendance Sheet**
   - Trigger: Friday before course start_date
   - Send teacher attendance sheet URL with student names, emails, course details
   - Auto-send email or create task to send manually

4. **Introductory Email Tracking**
   - Tag (manual if necessary) to indicate if intro email sent to class attendees
   - Task created if not sent by 3 days before start

5. **Mid-Course Continuation Email**
   - Trigger: Mid-point of course (total_sessions / 2)
   - Reminder to send continuation/re-enrollment email
   - Due date: Mid-course session date

6. **Certificate Preparation**
   - Trigger: Course end_date - 7 days
   - Reminder to prepare certificates for students with >= 80% attendance
   - Task assigned to admin/coordinator

7. **Manual Due Dates**
   - User can manually set due dates for actions on individual students
   - Appear in to-do schedule alongside automated reminders

**Implementation**:
- New table: `automated_tasks`
- Scheduled job runs daily: checks conditions and creates tasks
- Task creation is idempotent (check if task already exists before creating)
- Tasks link to related entities: `related_entity_type`, `related_entity_id`
- Dashboard widget shows pending tasks sorted by due_date

---

## Course & Enrollment Changes (NEW)

### Pre-Start Course Modifications (FR-030)

**Description**: Before course starts, details may change (hours, days, times).

**Impact**:
- Schedule changes
- Attendance sheet regeneration
- Teacher payment recalculation
- Classroom/hosting cost recalculation
- Student amount review (flagged for manual decision)

**Automatic Recalculation**:
- `total_cost_calculated = (hours_total * teacher_rate) + (hours_total * classroom_rate) + hosting_cost`
- Teacher payment: `hours_total * teacher_hourly_rate`
- Classroom cost: `hours_total * classroom_hourly_rate`

**Manual Review**:
- `enrollments.requires_manual_price_review = TRUE` when course modified before start
- Coordinator reviews whether to adjust student amounts (case-by-case)
- Options: Keep price, prorate reduction, full refund difference

**Implementation**:
- `course_offerings.modified_pre_start = TRUE` (flag)
- `course_offerings.modification_notes` (audit log)
- `enrollments.pre_start_modification_flag = TRUE` (flag)
- `enrollments.requires_manual_price_review = TRUE`
- UI workflow: Modify course → System recalcs costs → Flags enrollments → Coordinator reviews each

---

### Mid-Course Student Transfers (FR-031)

**Description**: Students may switch classes mid-course or early on.

**Workflow**:
1. Identify student in wrong level/class (after first session trial or mid-course assessment)
2. Disenroll from current course (status = 'cancelled', transfer reason)
3. Enroll in target course
4. Link enrollments: `transferred_from_enrollment_id`, `transferred_to_enrollment_id`
5. Activity log records transfer with both course references
6. Payment/credit handling (manual by finance)

**Implementation**:
- `enrollments.transferred_from_enrollment_id` (FK to enrollments)
- `enrollments.transferred_to_enrollment_id` (FK to enrollments)
- `enrollments.status = 'cancelled'` with notes: "Transferred to [course_name]"
- Activity created: type='enrollment', body='Student transferred from [Course A] to [Course B]'
- UI: Transfer button → Select target course → System creates new enrollment + updates old

---

## Custom Fields (NEW - FR-032)

**Description**: User-defined custom fields for extensibility.

**Supported Entities**:
- Students
- Course Offerings
- Enrollments

**Field Types**:
- Text (VARCHAR)
- Number (DECIMAL)
- Date (DATE)
- Boolean (TINYINT)
- Select (ENUM-like, options stored in JSON)

**Configuration**:
- Admin defines fields: entity_type, field_name, field_type, options, is_required, display_order
- Fields rendered dynamically on entity forms
- Values stored in `custom_field_values` table (EAV pattern)

**Implementation**:
- New table: `custom_fields` (field definitions)
- New table: `custom_field_values` (actual values, polymorphic)
- UI: Admin panel for field management
- Forms dynamically include custom fields based on entity_type
- API returns custom fields as part of entity JSON

---

## Financial Reporting & Profitability (NEW - FR-033)

**Description**: Business manager assesses whether course can go ahead and monthly performance.

**Dashboard Metrics**:

1. **Course Profitability**:
   - Formula: `(Total student payments) - (Teacher cost) - (Classroom cost) - (Admin overhead)`
   - Teacher cost: `hours_total * teacher_hourly_rate`
   - Classroom cost: `hours_total * classroom_hourly_rate` OR `online_hosting_cost`
   - Admin overhead: `SUM(enrollments.admin_overhead_amount)` (15% of enrollment price default)

2. **Course Viability Decision**:
   - Number of students registered: `COUNT(enrollments WHERE status IN ('pending', 'registered'))`
   - Number paid: `COUNT(enrollments WHERE status = 'registered')`
   - Revenue: `SUM(enrollments.final_price + enrollments.book_fee - enrollments.credit_applied)`
   - Costs: `teacher_cost + classroom_cost + admin_overhead`
   - Profit/Loss: `Revenue - Costs`
   - Break-even students: Calculate minimum enrollments to cover costs

3. **Monthly Overview**:
   - Courses running this month
   - Oversubscribed courses (capacity < enrollments)
   - Courses in profit vs loss
   - **Decision logic**: If many courses oversubscribed and in profit, can run some courses at loss

4. **Admin Overhead**:
   - Percentage per enrollment (default 15%, configurable)
   - `enrollments.admin_overhead_percentage` (can be overridden per enrollment)
   - `enrollments.admin_overhead_amount` (calculated field)

**Implementation**:
- Dashboard widgets with SQL queries
- Configurable admin_overhead_percentage in system settings
- `enrollments.admin_overhead_percentage` defaults to system setting
- Profit calculation includes all cost factors
- Color-coded courses: green (profit), yellow (break-even), red (loss)

---

## Book Costs (NEW - Enhanced FR-004)

**Description**: Books sometimes included in course price, sometimes separate charge.

**Configuration**:
- `course_offerings.book_included` (BOOLEAN)
- `course_offerings.book_cost` (DECIMAL)

**Invoice Logic**:
- If `book_included = TRUE`: No separate line item
- If `book_included = FALSE`: Add line item to invoice with `book_cost`
- `enrollments.book_fee` tracks actual book charge for enrollment

**Implementation**:
- `course_offerings.book_included` (default TRUE)
- `course_offerings.book_cost` (default 0)
- `enrollments.book_fee` copied from course_offerings at enrollment creation
- `enrollments.amount_owed` includes book_fee
- Invoice generation adds book as line item if applicable

---

## Updated Functional Requirements

**NEW**:
- FR-021: Private Lesson Package Management
- FR-022: Teacher Rate Management
- FR-023: Bespoke Group Course Management
- FR-024: Partner Management
- FR-025: Advance Payment & Credit System
- FR-026: Enhanced Discount System
- FR-027: Classroom & Location Costing
- FR-028: Three-Level Notes System
- FR-029: Automated Task & Reminder System
- FR-030: Pre-Start Course Modification
- FR-031: Mid-Course Student Transfer
- FR-032: Custom Fields System
- FR-033: Financial Reporting & Profitability
- FR-034: Cash Account & Non-Mollie Payments
- FR-035: Part Payment Support (Enhanced FR-010)

**UPDATED**:
- FR-004: Course offerings now support 3 types (group_scheduled, bespoke_group, private_lessons)
- FR-009: Invoices support partner_bulk type and bulk itemization
- FR-010: Enhanced part payment tracking
- FR-015: Enhanced to include enrollment-level and course-level notes via activities

---

## Updated Key Entities

**NEW**:
- PrivateLessonPackage
- PrivateLessonSession
- TeacherRate
- Partner
- StudentCredit
- CreditRedemption
- Classroom
- DiscountRule
- AutomatedTask
- CustomField
- CustomFieldValue

**MODIFIED**:
- CourseOffering: Added course_type, bespoke fields, classroom/cost fields, book fields
- Enrollment: Added partner, discount, credit, book fee, admin overhead fields
- Student: Added is_returning, total_enrollments, credit_balance
- Invoice: Added invoice_type, partner fields, payment_method
- Payment: Added is_partial, payment_source
- Activity: Extended entity types to include Enrollment, CourseOffering, PrivateLessonPackage

---

## Migration Notes

### Backward Compatibility
- All new fields have defaults or allow NULL
- Existing courses default to `course_type='group_scheduled'`
- Existing enrollments work without partner/discount/credit
- Existing invoices default to `invoice_type='standard'`

### Seed Data Required
1. Default discount rule: "5% Returning Student Discount"
2. System setting: `admin_overhead_percentage = 15.00`
3. Initial classrooms (if known)
4. Initial teacher rates (if available)

### Phased Rollout Recommended
1. Phase 1: Teacher rates, classrooms, partners, discounts
2. Phase 2: Private lessons system
3. Phase 3: Credits and advanced payments
4. Phase 4: Automation (tasks, reminders)
5. Phase 5: Custom fields
6. Phase 6: Reporting dashboards

---

## Testing Requirements

### Must Test
1. Existing group course workflow (ensure no regressions)
2. New private lesson package creation and session scheduling
3. Partner enrollment with bulk invoice generation
4. Automatic returning student discount application
5. Credit application to enrollment
6. Pre-start course modification with cost recalculation
7. Mid-course student transfer workflow
8. Automated task generation (payment chase, attendance sheet reminder)
9. Profitability dashboard calculations
10. Book cost handling (included vs separate)

### Edge Cases
1. Student with both group enrollment and private lesson package
2. Partner student switching to independent student
3. Credit expiration (after 1 year)
4. Package depletion and top-up
5. Course modification after some students paid (manual review workflow)
6. Free course enrollment (discount = 100%)
7. Multiple partial payments reaching invoice total

---

## Open Items

### Resolved ✅
- Book costs: Sometimes included, sometimes separate charge per course type
- Admin overhead: Fixed percentage per enrollment (default 15%)
- Partner payment terms: 10% discount on course rate per student
- Private lesson expiration: Packages do NOT expire
- Credit expiration: 1 year from creation
- Trial sessions: Use existing `enrollment.is_trial` flag

### Remaining
None - all questions answered.

---

## Approval

**Status**: ✅ APPROVED
**Date**: November 25, 2025
**Approved By**: User
**Next Steps**: 
1. Create database migrations
2. Update data model documentation
3. Implement new tables and modify existing
4. Create Eloquent models
5. Build API endpoints
6. Update Retool UI
