# Data Model Update: Extended Entities (November 2025)

**Date**: November 25, 2025  
**Purpose**: Document all new and modified entities for enhanced course types and financial management

---

## New Entities

### PrivateLessonPackage
Private lesson hour packages sold to students for one-on-one instruction.

```sql
- id: bigint PK AUTO_INCREMENT
- student_id: bigint FK students(id) ON DELETE CASCADE
- package_type: ENUM('standard_10h','standard_20h','standard_30h','bespoke','ielts','intro')
- total_hours: decimal(5,2)
- hours_used: decimal(5,2) DEFAULT 0
- hours_remaining: decimal(5,2) GENERATED ALWAYS AS (total_hours - hours_used) STORED
- student_hourly_rate: decimal(10,2) -- What student pays per hour
- teacher_hourly_rate: decimal(10,2) -- What school pays teacher per hour  
- profit_margin: decimal(10,2) GENERATED ALWAYS AS (student_hourly_rate - teacher_hourly_rate) STORED
- delivery_mode: ENUM('onsite','online')
- assigned_teacher_id: bigint FK users(id) ON DELETE SET NULL
- status: ENUM('active','completed','cancelled') DEFAULT 'active'
- purchased_at: datetime
- invoice_id: bigint FK invoices(id) ON DELETE SET NULL
- notes: text NULL
- created_at, updated_at timestamps
```

**Indices**: student_id, status, assigned_teacher_id  
**Notes**: Packages do NOT expire. Students schedule with teacher irregularly.

---

### PrivateLessonSession  
Individual private lesson sessions delivered against a package.

```sql
- id: bigint PK AUTO_INCREMENT
- package_id: bigint FK private_lesson_packages(id) ON DELETE CASCADE
- session_date: date
- start_time: time
- end_time: time
- hours_delivered: decimal(4,2)
- teacher_id: bigint FK users(id) ON DELETE RESTRICT
- location: varchar(255) NULL
- delivery_mode: ENUM('onsite','online')
- session_notes: text NULL
- student_attended: boolean DEFAULT TRUE
- created_at, updated_at timestamps
```

**Indices**: package_id, session_date, teacher_id  
**Notes**: No attendance tracking required. Teacher invoices school for hours delivered.

---

### TeacherRate
Historical and current rates for teachers (group courses and private lessons).

```sql
- id: bigint PK AUTO_INCREMENT
- teacher_id: bigint FK users(id) ON DELETE CASCADE
- rate_type: ENUM('group_course','private_lesson')
- hourly_rate: decimal(10,2)
- effective_from: date
- effective_to: date NULL
- is_default: boolean DEFAULT TRUE
- notes: text NULL
- created_at, updated_at timestamps
```

**Unique Constraint**: (teacher_id, rate_type, is_default, effective_to) -- Only one default rate active per type  
**Indices**: teacher_id, effective_from/effective_to  
**Notes**: Supports rate history tracking. NULL effective_to means currently active.

---

### Partner
Third-party companies/organizations that refer students.

```sql
- id: bigint PK AUTO_INCREMENT
- company_name: varchar(255) UNIQUE
- contact_name: varchar(255) NULL
- contact_email: varchar(255) NULL
- contact_phone: varchar(48) NULL
- discount_percentage: decimal(5,2) DEFAULT 10.00 -- Default 10%
- payment_terms: varchar(255) NULL
- is_active: boolean DEFAULT TRUE
- notes: text NULL
- created_at, updated_at timestamps
```

**Indices**: is_active, company_name  
**Notes**: Partners receive discounted rate. They charge their own rates to end students.

---

### StudentCredit
Advance payments creating credit balance for future course enrollments.

```sql
- id: bigint PK AUTO_INCREMENT
- student_id: bigint FK students(id) ON DELETE CASCADE
- amount: decimal(10,2) -- Original credit amount
- balance: decimal(10,2) -- Remaining balance
- source: ENUM('advance_payment','refund','promotional','other')
- status: ENUM('active','depleted','expired') DEFAULT 'active'
- created_at: timestamp DEFAULT CURRENT_TIMESTAMP
- expires_at: datetime GENERATED ALWAYS AS (DATE_ADD(created_at, INTERVAL 1 YEAR)) STORED
- notes: text NULL
```

**Indices**: student_id, status, expires_at  
**Notes**: Credits expire after 1 year. Scheduled job marks as 'expired' when expires_at < NOW().

---

### CreditRedemption
Tracks when credits are applied to enrollments.

```sql
- id: bigint PK AUTO_INCREMENT
- credit_id: bigint FK student_credits(id) ON DELETE CASCADE
- enrollment_id: bigint FK enrollments(id) ON DELETE CASCADE
- amount_redeemed: decimal(10,2)
- redeemed_at: datetime
- receipt_sent: boolean DEFAULT FALSE
- receipt_sent_at: datetime NULL
- created_by_user_id: bigint FK users(id) ON DELETE SET NULL
- created_at, updated_at timestamps
```

**Indices**: credit_id, enrollment_id  
**Notes**: Full audit trail of credit usage. Receipt generated when credit applied (not full invoice).

---

### Classroom
Physical or online rooms with hourly rental costs.

```sql
- id: bigint PK AUTO_INCREMENT
- name: varchar(255)
- location: varchar(255)
- hourly_rate: decimal(10,2)
- capacity: int NULL
- is_active: boolean DEFAULT TRUE
- notes: text NULL
- created_at, updated_at timestamps
```

**Unique Constraint**: (name, location)  
**Indices**: is_active  
**Notes**: Hourly rate used in course cost calculations.

---

### DiscountRule
Automated and manual discount rules.

```sql
- id: bigint PK AUTO_INCREMENT
- rule_type: ENUM('returning_student','manual','partner','free_course')
- discount_percentage: decimal(5,2) NULL
- discount_amount: decimal(10,2) NULL
- is_automatic: boolean DEFAULT FALSE
- is_active: boolean DEFAULT TRUE
- description: text NULL
- created_at, updated_at timestamps
```

**Notes**: Seed data includes "5% Returning Student" rule with is_automatic=TRUE.

---

### AutomatedTask
System-generated and manual tasks/reminders for staff.

```sql
- id: bigint PK AUTO_INCREMENT
- task_type: ENUM('payment_chase','lead_followup','attendance_sheet','intro_email',
                 'midcourse_continuation','certificate_prep','manual')
- related_entity_type: varchar(255) NULL
- related_entity_id: bigint NULL
- assigned_to_user_id: bigint FK users(id) ON DELETE SET NULL
- title: varchar(255)
- body: text NULL
- due_date: date
- status: ENUM('pending','completed','cancelled') DEFAULT 'pending'
- completed_at: datetime NULL
- created_at, updated_at timestamps
```

**Indices**: due_date, status, task_type, (related_entity_type, related_entity_id)  
**Notes**: Scheduled job creates tasks based on date triggers. Idempotent creation.

---

### CustomField
User-defined field definitions for extensibility.

```sql
- id: bigint PK AUTO_INCREMENT
- entity_type: ENUM('student','course_offering','enrollment')
- field_name: varchar(255)
- field_type: ENUM('text','number','date','boolean','select')
- field_options: JSON NULL -- For select fields: ["Option 1", "Option 2"]
- is_required: boolean DEFAULT FALSE
- is_active: boolean DEFAULT TRUE
- display_order: int DEFAULT 0
- created_at, updated_at timestamps
```

**Unique Constraint**: (entity_type, field_name)  
**Notes**: EAV pattern for flexible field addition without schema changes.

---

### CustomFieldValue
Actual values for custom fields (EAV pattern).

```sql
- id: bigint PK AUTO_INCREMENT
- custom_field_id: bigint FK custom_fields(id) ON DELETE CASCADE
- entity_type: varchar(255) -- 'Student', 'CourseOffering', 'Enrollment'
- entity_id: bigint
- field_value: text NULL
- created_at, updated_at timestamps
```

**Indices**: custom_field_id, (entity_type, entity_id)  
**Notes**: Polymorphic storage. field_value stores all types as text, cast on retrieval.

---

## Modified Entities

### CourseOffering (MODIFIED)
**New Columns**:
```sql
- course_type: ENUM('group_scheduled','bespoke_group','private_lessons') DEFAULT 'group_scheduled'
- is_bespoke: boolean DEFAULT FALSE
- delivery_location: ENUM('onsite','online','third_party') DEFAULT 'onsite'
- classroom_id: bigint FK classrooms(id) ON DELETE SET NULL
- teacher_hourly_rate: decimal(10,2) NULL
- classroom_hourly_rate: decimal(10,2) NULL -- Cached from classroom
- online_hosting_cost: decimal(10,2) DEFAULT 0
- ad_hoc_teacher_rate: decimal(10,2) NULL -- Override default teacher rate
- book_included: boolean DEFAULT TRUE
- book_cost: decimal(10,2) DEFAULT 0
- total_cost_calculated: decimal(10,2) GENERATED ALWAYS AS (
    (hours_total * COALESCE(ad_hoc_teacher_rate, teacher_hourly_rate, 0)) +
    (hours_total * COALESCE(classroom_hourly_rate, 0)) +
    online_hosting_cost
  ) STORED
- modified_pre_start: boolean DEFAULT FALSE
- modification_notes: text NULL
```

**New Indices**: course_type, delivery_location

---

### Enrollment (MODIFIED)
**New Columns**:
```sql
- partner_id: bigint FK partners(id) ON DELETE SET NULL
- discount_rule_id: bigint FK discount_rules(id) ON DELETE SET NULL
- discount_percentage: decimal(5,2) DEFAULT 0
- discount_amount: decimal(10,2) DEFAULT 0
- is_free_course: boolean DEFAULT FALSE
- final_price: decimal(10,2) NULL -- After discounts
- credit_applied: decimal(10,2) DEFAULT 0
- book_fee: decimal(10,2) DEFAULT 0
- admin_overhead_percentage: decimal(5,2) DEFAULT 15.00
- admin_overhead_amount: decimal(10,2) GENERATED ALWAYS AS (
    COALESCE(final_price, 0) * (admin_overhead_percentage / 100)
  ) STORED
- amount_owed: decimal(10,2) GENERATED ALWAYS AS (
    COALESCE(final_price, 0) + COALESCE(book_fee, 0) - COALESCE(credit_applied, 0)
  ) STORED
- package_id: bigint FK private_lesson_packages(id) ON DELETE SET NULL
- pre_start_modification_flag: boolean DEFAULT FALSE
- requires_manual_price_review: boolean DEFAULT FALSE
```

**New Indices**: partner_id, discount_rule_id

---

### Student (MODIFIED)
**New Columns**:
```sql
- is_returning: boolean DEFAULT FALSE
- total_enrollments: int DEFAULT 0
- credit_balance: decimal(10,2) DEFAULT 0 -- Calculated: SUM(active credits.balance)
- preferred_contact_method: ENUM('email','phone','whatsapp') DEFAULT 'email'
```

**New Indices**: is_returning

---

### Invoice (MODIFIED)
**New Columns**:
```sql
- invoice_type: ENUM('standard','partner_bulk','private_lesson','credit_receipt') DEFAULT 'standard'
- partner_id: bigint FK partners(id) ON DELETE SET NULL
- is_bulk_itemized: boolean DEFAULT FALSE
- payment_method: ENUM('mollie','bank_transfer','cash','credit') DEFAULT 'mollie'
- cash_account_id: bigint NULL
```

**New Indices**: invoice_type, partner_id

---

### Payment (MODIFIED)
**New Columns**:
```sql
- is_partial: boolean DEFAULT FALSE
- payment_source: ENUM('mollie','bank_transfer','cash','credit_redemption') DEFAULT 'mollie'
```

**New Indices**: payment_source

---

### Activity (MODIFIED)
**Updated ENUM**:
```sql
- related_entity_type: ENUM('Lead','Student','Enrollment','CourseOffering','PrivateLessonPackage')
```

**Notes**: Extended to support enrollment notes and course offering notes.

---

### Session (MODIFIED)
**New Columns**:
```sql
- classroom_id: bigint FK classrooms(id) ON DELETE SET NULL
- delivery_mode: ENUM('onsite','online') DEFAULT 'onsite'
- hosting_cost: decimal(10,2) DEFAULT 0
```

---

## Calculated Fields & Generated Columns

### Key Generated Columns

1. **PrivateLessonPackage**:
   - `hours_remaining = total_hours - hours_used`
   - `profit_margin = student_hourly_rate - teacher_hourly_rate`

2. **StudentCredit**:
   - `expires_at = DATE_ADD(created_at, INTERVAL 1 YEAR)`

3. **CourseOffering**:
   - `total_cost_calculated` (teacher + classroom + hosting costs)

4. **Enrollment**:
   - `admin_overhead_amount = final_price * (admin_overhead_percentage / 100)`
   - `amount_owed = final_price + book_fee - credit_applied`

### Key Calculated Fields (Application Layer)

1. **Student.credit_balance**:
   ```sql
   SELECT SUM(balance) FROM student_credits 
   WHERE student_id = ? AND status = 'active'
   ```

2. **Student.is_returning**:
   ```sql
   UPDATE students SET is_returning = 1 WHERE total_enrollments > 0
   ```

3. **Course Profitability**:
   ```sql
   Revenue = SUM(enrollments.final_price + enrollments.book_fee - enrollments.credit_applied)
   TeacherCost = hours_total * teacher_hourly_rate
   ClassroomCost = hours_total * classroom_hourly_rate OR online_hosting_cost
   AdminOverhead = SUM(enrollments.admin_overhead_amount)
   Profit = Revenue - TeacherCost - ClassroomCost - AdminOverhead
   ```

---

## Relationships Summary

### One-to-Many
- Student → PrivateLessonPackages
- PrivateLessonPackage → PrivateLessonSessions
- Teacher (User) → TeacherRates
- Student → StudentCredits
- StudentCredit → CreditRedemptions
- Partner → Enrollments
- CourseOffering → Enrollments
- CustomField → CustomFieldValues

### Many-to-One
- Enrollment → Partner
- Enrollment → DiscountRule
- Enrollment → PrivateLessonPackage
- CourseOffering → Classroom
- Session → Classroom
- CreditRedemption → Enrollment
- CreditRedemption → StudentCredit

### Polymorphic
- Activity → (Lead | Student | Enrollment | CourseOffering | PrivateLessonPackage)
- CustomFieldValue → (Student | CourseOffering | Enrollment)
- AutomatedTask → (Lead | Student | Enrollment | CourseOffering)

---

## Indexing Strategy

### High-Priority Indices (Query Performance)

1. **Enrollments**:
   - `(student_id, status)` - Student profile course list
   - `(course_offering_id, status)` - Course roster
   - `(partner_id)` - Partner reporting
   - `(status, created_at)` - Payment chase automation

2. **PrivateLessonPackages**:
   - `(student_id, status)` - Student package list
   - `(assigned_teacher_id, status)` - Teacher workload

3. **StudentCredits**:
   - `(student_id, status)` - Credit balance calculation
   - `(expires_at, status)` - Expiration job

4. **AutomatedTasks**:
   - `(due_date, status)` - Dashboard widget
   - `(assigned_to_user_id, status)` - User task list
   - `(related_entity_type, related_entity_id)` - Entity-specific tasks

5. **Activities**:
   - `(related_entity_type, related_entity_id, created_at)` - Timeline queries

6. **CourseOfferings**:
   - `(course_type, status, start_date)` - Course filtering
   - `(delivery_location)` - Location reporting

---

## Data Integrity Rules

### Constraints

1. **Credit Balance**: `student_credits.balance >= 0`
2. **Package Hours**: `private_lesson_packages.hours_used <= total_hours`
3. **Discount Percentage**: `enrollments.discount_percentage BETWEEN 0 AND 100`
4. **Classroom Capacity**: `COUNT(enrollments) <= course_offerings.capacity`

### Triggers (Application Layer)

1. **After Enrollment Created**:
   - Increment `students.total_enrollments`
   - Set `students.is_returning = TRUE` if total > 0
   - Apply automatic discount if is_returning

2. **After Payment Received**:
   - Update `invoice.status = 'paid'` when sum(payments) >= total
   - Update `enrollment.status = 'registered'` when invoice paid

3. **After Credit Redemption**:
   - Decrease `student_credits.balance`
   - Set `student_credits.status = 'depleted'` if balance = 0
   - Update `students.credit_balance`

4. **After Private Lesson Session**:
   - Increase `private_lesson_packages.hours_used`
   - Set `status = 'completed'` if hours_remaining = 0

5. **Daily Scheduled Job**:
   - Mark expired credits: `UPDATE student_credits SET status='expired' WHERE expires_at < NOW()`
   - Generate automated tasks based on date triggers
   - Update course status: `UPDATE course_offerings SET status='completed' WHERE end_date < CURDATE()`

---

## Migration Strategy

### Phase 1: Core Infrastructure
1. Create new tables: partners, classrooms, discount_rules
2. Create teacher_rates table
3. Alter course_offerings (add course_type, classroom_id, cost fields, book fields)
4. Alter enrollments (add partner_id, discount fields, book_fee, admin_overhead)
5. Alter students (add is_returning, total_enrollments, credit_balance)
6. Alter invoices (add invoice_type, partner_id, payment_method)
7. Alter payments (add is_partial, payment_source)
8. Seed discount_rules with "5% Returning Student"

### Phase 2: Private Lessons
1. Create private_lesson_packages table
2. Create private_lesson_sessions table

### Phase 3: Credits System
1. Create student_credits table
2. Create credit_redemptions table

### Phase 4: Automation
1. Create automated_tasks table
2. Alter activities (extend ENUM)

### Phase 5: Custom Fields
1. Create custom_fields table
2. Create custom_field_values table

### Phase 6: Data Population
1. Update existing students: `is_returning = (total_enrollments > 0)`
2. Populate teacher_rates from existing data
3. Create initial classroom records

---

## Performance Considerations

### Query Optimization

1. **Student Profile Page**:
   - JOIN enrollments ON student_id
   - JOIN private_lesson_packages ON student_id
   - JOIN student_credits ON student_id WHERE status='active'
   - Consider caching credit_balance

2. **Course Roster**:
   - JOIN enrollments ON course_offering_id WHERE status IN ('registered','active')
   - JOIN students ON enrollment.student_id
   - Limit to active/registered to avoid full table scan

3. **Partner Reporting**:
   - JOIN enrollments ON partner_id
   - JOIN course_offerings ON enrollment.course_offering_id
   - SUM calculations with HAVING clause

4. **Profitability Dashboard**:
   - Complex calculation query - consider materialized view or cache
   - Recalculate nightly for dashboard widgets

### Caching Strategy

1. **Student credit_balance**: Recalculate on credit create/redeem, cache on student record
2. **Course profitability**: Calculate on-demand, cache for 1 hour
3. **Teacher rates**: Cache current rates, invalidate on rate update
4. **Automated tasks**: Cache user task counts, invalidate on task status change

---

## Backup & Audit Requirements

### Critical Tables (Daily Backup)
- invoices
- payments
- enrollments
- student_credits
- credit_redemptions
- private_lesson_packages
- teacher_rates

### Audit Logging (Application Layer)
- All changes to invoices (before/after JSON)
- All changes to payments
- All changes to enrollments (especially status, price fields)
- All credit redemptions
- All discount applications
- All course modifications (pre-start)

---

## Testing Checklist

### Data Integrity Tests
- [ ] Credit balance never negative
- [ ] Package hours_used never > total_hours
- [ ] Enrollment amount_owed = final_price + book_fee - credit_applied
- [ ] Course total_cost_calculated formula accurate
- [ ] Admin_overhead_amount = final_price * (percentage / 100)

### Workflow Tests
- [ ] Private lesson package purchase → invoice → payment
- [ ] Credit purchase → credit applied to enrollment → balance reduced
- [ ] Partner enrollment → bulk invoice → student withdrawn → invoice adjusted
- [ ] Course modification before start → cost recalc → enrollments flagged
- [ ] Returning student enrollment → automatic 5% discount
- [ ] Credit expiration after 1 year
- [ ] Package depletion → top-up purchase

### Reporting Tests
- [ ] Course profitability calculation accurate
- [ ] Student course history (group + private lessons)
- [ ] Partner revenue calculation
- [ ] Teacher payment calculation (hours * rate)
- [ ] Classroom utilization report

---

## Notes

- All decimal currency fields: `DECIMAL(10,2)` for €99,999,999.99 max
- All percentage fields: `DECIMAL(5,2)` for 0.00% to 999.99%
- Use GENERATED columns where possible (MySQL 5.7+) for calculated fields
- JSON columns for flexible data (schedule, field_options, webhook_payload)
- ENUM types for fixed value sets (status, types, sources)
- Foreign keys with appropriate ON DELETE actions (CASCADE for dependent data, SET NULL for optional refs)
- Timestamps for audit trail (created_at, updated_at on all tables)
- Soft deletes not used - rely on is_active flags and status fields
