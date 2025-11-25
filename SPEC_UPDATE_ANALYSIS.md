# Specification Update Analysis
**Date**: November 25, 2025
**Purpose**: Analyze new requirements against current system design and propose database schema enhancements

---

## Executive Summary

The new requirements introduce significant complexity around:
1. **Course types**: Adding bespoke group courses and private lesson packages
2. **Financial complexity**: Teacher rates, partner discounts, advance payments/credits, part payments
3. **Operational features**: Automated reminders, notes system, custom fields
4. **Course modifications**: Pre-start changes and mid-course transfers

**Impact Assessment**: MAJOR schema changes required, but most existing functionality preserved.

---

## Gap Analysis

### ✅ Already Implemented
- Group courses (scheduled) - **CourseOffering entity**
- Payment tracking via Mollie - **Payment entity with webhook integration**
- Refunds - **Supported as negative Payment records**
- Basic discounts - **Can be applied at invoice level**
- Course status management - **FR-016: status ENUM on CourseOffering**
- Student archiving - **FR-015: is_active flag**
- Notes on students - **Activity timeline (FR-018)**
- Level checks - **Booking entity with Cal.com integration**

### 🔴 Missing / Needs Enhancement

#### 1. Course Types (NEW)
- ❌ **Bespoke Group Courses**
  - Bulk quotation invoicing
  - Per-student itemization on invoice
  - On-site/online/third-location tracking
  - In-company student flag
  
- ❌ **Private Lessons**
  - Package system (10/20/30 hours)
  - Two-rate system (teacher rate vs student rate)
  - Profit margin calculation
  - Ad-hoc scheduling (not fixed schedule)
  - Specialized packages (IELTS, intro)
  - Top-up purchases

#### 2. Teacher Rates (NEW)
- ❌ Teacher rate management
  - Default rates per teacher (group vs private)
  - Ad-hoc rate overrides per course
  - Rate history tracking
  
#### 3. Financial (PARTIAL)
- ⚠️ **Part Payments** - Already supported via Mollie, needs UI enhancement
- ❌ **Discount System**
  - Automatic 5% for returning students
  - Manual tailored discounts
  - Free courses
  
- ❌ **Advance Payments / Credit System**
  - Student credit balance
  - Credit application to enrollments
  - Receipt generation (not full invoice)
  
- ❌ **Third-Party Partners**
  - Partner entity with company details
  - Partner discount rates (variable, ~10%)
  - Enrollment-level partner association
  - Partner-specific invoicing

- ⚠️ **Non-Mollie Payments** - Partially supported, needs cash account
- ✅ **Refunds** - Already supported

#### 4. Location & Costs (NEW)
- ❌ **Classroom Rates**
  - Per-room hourly rates
  - Link to course offerings
  
- ❌ **Online Session Costs**
  - Zoom/Google Meet hosting cost per session
  - Factor into cost calculations

#### 5. Notes System (PARTIAL)
- ⚠️ **Three-level notes** - Activity timeline exists but needs enhancement:
  - Student notes ✅
  - Enrollment notes ❌ (need enrollment_id in activities)
  - Course offering notes ❌ (need course_offering_id in activities)

#### 6. Automated Reminders (NEW)
- ❌ All reminder types:
  - Pre-course payment chase
  - Lead follow-up reminders
  - Teacher attendance sheet delivery
  - Introductory email tracking
  - Mid-course continuation email
  - Certificate preparation reminder
  - Manual due dates

#### 7. Course & Enrollment Changes (NEW)
- ❌ **Pre-Start Modifications**
  - Schedule changes (hours, days)
  - Automatic cost recalculation
  - Manual student amount review flag
  
- ⚠️ **Mid-Course Transfers** - Partial via enrollment status, needs workflow

#### 8. Custom Fields (NEW)
- ❌ User-defined custom fields system

---

## Proposed Database Schema Changes

### 1. New Tables

#### `course_types` (ENUM helper - optional, can use ENUM directly)
```sql
-- Course types: 'group_scheduled', 'bespoke_group', 'private_lessons'
-- Store as ENUM on course_offerings
```

#### `private_lesson_packages`
```sql
CREATE TABLE private_lesson_packages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  package_type ENUM('standard_10h', 'standard_20h', 'standard_30h', 'bespoke', 'ielts', 'intro') NOT NULL,
  total_hours DECIMAL(5,2) NOT NULL,
  hours_used DECIMAL(5,2) DEFAULT 0,
  hours_remaining DECIMAL(5,2) GENERATED ALWAYS AS (total_hours - hours_used) STORED,
  student_hourly_rate DECIMAL(10,2) NOT NULL,
  teacher_hourly_rate DECIMAL(10,2) NOT NULL,
  profit_margin DECIMAL(10,2) GENERATED ALWAYS AS (student_hourly_rate - teacher_hourly_rate) STORED,
  delivery_mode ENUM('onsite', 'online') NOT NULL,
  assigned_teacher_id BIGINT UNSIGNED NULL,
  status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
  purchased_at DATETIME NOT NULL,
  invoice_id BIGINT UNSIGNED NULL COMMENT 'Invoice for package purchase',
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_teacher_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
  INDEX idx_student (student_id),
  INDEX idx_status (status),
  INDEX idx_teacher (assigned_teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMENT ON TABLE private_lesson_packages: 'Private lesson packages do NOT expire - students schedule irregularly with teachers';
```

#### `private_lesson_sessions`
```sql
CREATE TABLE private_lesson_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_id BIGINT UNSIGNED NOT NULL,
  session_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  hours_delivered DECIMAL(4,2) NOT NULL,
  teacher_id BIGINT UNSIGNED NOT NULL,
  location VARCHAR(255) NULL,
  delivery_mode ENUM('onsite', 'online') NOT NULL,
  session_notes TEXT NULL,
  student_attended BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (package_id) REFERENCES private_lesson_packages(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_package (package_id),
  INDEX idx_date (session_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `teacher_rates`
```sql
CREATE TABLE teacher_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id BIGINT UNSIGNED NOT NULL,
  rate_type ENUM('group_course', 'private_lesson') NOT NULL,
  hourly_rate DECIMAL(10,2) NOT NULL,
  effective_from DATE NOT NULL,
  effective_to DATE NULL,
  is_default BOOLEAN DEFAULT TRUE,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_teacher (teacher_id),
  INDEX idx_effective (effective_from, effective_to),
  UNIQUE KEY unique_default_rate (teacher_id, rate_type, is_default, effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `partners`
```sql
CREATE TABLE partners (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(255) NOT NULL,
  contact_name VARCHAR(255) NULL,
  contact_email VARCHAR(255) NULL,
  contact_phone VARCHAR(48) NULL,
  discount_percentage DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  payment_terms VARCHAR(255) NULL,
  is_active BOOLEAN DEFAULT TRUE,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_active (is_active),
  UNIQUE KEY unique_company (company_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `student_credits`
```sql
CREATE TABLE student_credits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  balance DECIMAL(10,2) NOT NULL,
  source ENUM('advance_payment', 'refund', 'promotional', 'other') NOT NULL,
  status ENUM('active', 'depleted', 'expired') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME GENERATED ALWAYS AS (DATE_ADD(created_at, INTERVAL 1 YEAR)) STORED,
  notes TEXT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_student (student_id),
  INDEX idx_status (status),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMENT ON TABLE student_credits: 'Credits expire after 1 year from creation date';
```

#### `credit_redemptions`
```sql
CREATE TABLE credit_redemptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  credit_id BIGINT UNSIGNED NOT NULL,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  amount_redeemed DECIMAL(10,2) NOT NULL,
  redeemed_at DATETIME NOT NULL,
  receipt_sent BOOLEAN DEFAULT FALSE,
  receipt_sent_at DATETIME NULL,
  created_by_user_id BIGINT UNSIGNED NULL,
  FOREIGN KEY (credit_id) REFERENCES student_credits(id) ON DELETE CASCADE,
  FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_credit (credit_id),
  INDEX idx_enrollment (enrollment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `classrooms`
```sql
CREATE TABLE classrooms (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  location VARCHAR(255) NOT NULL,
  hourly_rate DECIMAL(10,2) NOT NULL,
  capacity INT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_active (is_active),
  UNIQUE KEY unique_name_location (name, location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `discount_rules`
```sql
CREATE TABLE discount_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rule_type ENUM('returning_student', 'manual', 'partner', 'free_course') NOT NULL,
  discount_percentage DECIMAL(5,2) NULL,
  discount_amount DECIMAL(10,2) NULL,
  is_automatic BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `automated_tasks`
```sql
CREATE TABLE automated_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_type ENUM(
    'payment_chase',
    'lead_followup',
    'attendance_sheet',
    'intro_email',
    'midcourse_continuation',
    'certificate_prep',
    'manual'
  ) NOT NULL,
  related_entity_type VARCHAR(255) NULL,
  related_entity_id BIGINT UNSIGNED NULL,
  assigned_to_user_id BIGINT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT NULL,
  due_date DATE NOT NULL,
  status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
  completed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_due_date (due_date),
  INDEX idx_status (status),
  INDEX idx_type (task_type),
  INDEX idx_entity (related_entity_type, related_entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `custom_fields`
```sql
CREATE TABLE custom_fields (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('student', 'course_offering', 'enrollment') NOT NULL,
  field_name VARCHAR(255) NOT NULL,
  field_type ENUM('text', 'number', 'date', 'boolean', 'select') NOT NULL,
  field_options JSON NULL, -- For select fields
  is_required BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_field (entity_type, field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `custom_field_values`
```sql
CREATE TABLE custom_field_values (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  custom_field_id BIGINT UNSIGNED NOT NULL,
  entity_type VARCHAR(255) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  field_value TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (custom_field_id) REFERENCES custom_fields(id) ON DELETE CASCADE,
  INDEX idx_entity (entity_type, entity_id),
  INDEX idx_field (custom_field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
#### `course_offerings` - ADD
```sql
ALTER TABLE course_offerings
  ADD COLUMN course_type ENUM('group_scheduled', 'bespoke_group', 'private_lessons') DEFAULT 'group_scheduled' AFTER status,
  ADD COLUMN is_bespoke BOOLEAN DEFAULT FALSE AFTER course_type,
  ADD COLUMN delivery_location ENUM('onsite', 'online', 'third_party') DEFAULT 'onsite' AFTER location,
  ADD COLUMN classroom_id BIGINT UNSIGNED NULL AFTER delivery_location,
  ADD COLUMN teacher_hourly_rate DECIMAL(10,2) NULL AFTER price,
  ADD COLUMN classroom_hourly_rate DECIMAL(10,2) NULL AFTER teacher_hourly_rate,
  ADD COLUMN online_hosting_cost DECIMAL(10,2) DEFAULT 0 AFTER classroom_hourly_rate,
  ADD COLUMN ad_hoc_teacher_rate DECIMAL(10,2) NULL AFTER online_hosting_cost,
  ADD COLUMN book_included BOOLEAN DEFAULT TRUE AFTER ad_hoc_teacher_rate,
  ADD COLUMN book_cost DECIMAL(10,2) DEFAULT 0 AFTER book_included,
  ADD COLUMN total_cost_calculated DECIMAL(10,2) GENERATED ALWAYS AS (
    (hours_total * COALESCE(ad_hoc_teacher_rate, teacher_hourly_rate, 0)) + 
    (hours_total * COALESCE(classroom_hourly_rate, 0)) + 
    online_hosting_cost
  ) STORED,
  ADD COLUMN modified_pre_start BOOLEAN DEFAULT FALSE,
  ADD COLUMN modification_notes TEXT NULL,
  ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE SET NULL,
  ADD INDEX idx_course_type (course_type),
  ADD INDEX idx_delivery (delivery_location);
```DD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE SET NULL,
  ADD INDEX idx_course_type (course_type),
  ADD INDEX idx_delivery (delivery_location);
#### `enrollments` - ADD
```sql
ALTER TABLE enrollments
  ADD COLUMN partner_id BIGINT UNSIGNED NULL AFTER status,
  ADD COLUMN discount_rule_id BIGINT UNSIGNED NULL AFTER partner_id,
  ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0 AFTER discount_rule_id,
  ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER discount_percentage,
  ADD COLUMN is_free_course BOOLEAN DEFAULT FALSE AFTER discount_amount,
  ADD COLUMN final_price DECIMAL(10,2) NULL AFTER is_free_course,
  ADD COLUMN credit_applied DECIMAL(10,2) DEFAULT 0 AFTER final_price,
  ADD COLUMN book_fee DECIMAL(10,2) DEFAULT 0 AFTER credit_applied,
  ADD COLUMN admin_overhead_percentage DECIMAL(5,2) DEFAULT 15.00 AFTER book_fee,
  ADD COLUMN admin_overhead_amount DECIMAL(10,2) GENERATED ALWAYS AS (
    COALESCE(final_price, 0) * (admin_overhead_percentage / 100)
  ) STORED,
  ADD COLUMN amount_owed DECIMAL(10,2) GENERATED ALWAYS AS (
    COALESCE(final_price, 0) + COALESCE(book_fee, 0) - COALESCE(credit_applied, 0)
  ) STORED,
  ADD COLUMN package_id BIGINT UNSIGNED NULL AFTER amount_owed,
  ADD COLUMN pre_start_modification_flag BOOLEAN DEFAULT FALSE,
  ADD COLUMN requires_manual_price_review BOOLEAN DEFAULT FALSE,
  ADD FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL,
  ADD FOREIGN KEY (discount_rule_id) REFERENCES discount_rules(id) ON DELETE SET NULL,
  ADD FOREIGN KEY (package_id) REFERENCES private_lesson_packages(id) ON DELETE SET NULL,
  ADD INDEX idx_partner (partner_id),
  ADD INDEX idx_discount (discount_rule_id);
```DD INDEX idx_partner (partner_id),
  ADD INDEX idx_discount (discount_rule_id);
```

#### `students` - ADD
```sql
ALTER TABLE students
  ADD COLUMN is_returning BOOLEAN DEFAULT FALSE,
  ADD COLUMN total_enrollments INT DEFAULT 0,
  ADD COLUMN credit_balance DECIMAL(10,2) DEFAULT 0,
  ADD COLUMN preferred_contact_method ENUM('email', 'phone', 'whatsapp') DEFAULT 'email',
  ADD INDEX idx_returning (is_returning);
```

#### `invoices` - ADD
```sql
ALTER TABLE invoices
  ADD COLUMN invoice_type ENUM('standard', 'partner_bulk', 'private_lesson', 'credit_receipt') DEFAULT 'standard',
  ADD COLUMN partner_id BIGINT UNSIGNED NULL,
  ADD COLUMN is_bulk_itemized BOOLEAN DEFAULT FALSE,
  ADD COLUMN payment_method ENUM('mollie', 'bank_transfer', 'cash', 'credit') DEFAULT 'mollie',
  ADD COLUMN cash_account_id BIGINT UNSIGNED NULL,
  ADD FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL,
  ADD INDEX idx_type (invoice_type);
```

#### `payments` - ADD
```sql
ALTER TABLE payments
  ADD COLUMN is_partial BOOLEAN DEFAULT FALSE,
  ADD COLUMN payment_source ENUM('mollie', 'bank_transfer', 'cash', 'credit_redemption') DEFAULT 'mollie',
  ADD INDEX idx_source (payment_source);
```

#### `activities` - MODIFY to support enrollment & course notes
```sql
ALTER TABLE activities
  MODIFY related_entity_type ENUM('Lead', 'Student', 'Enrollment', 'CourseOffering', 'PrivateLessonPackage') NOT NULL;
```

#### `sessions` - ADD
```sql
ALTER TABLE sessions
  ADD COLUMN classroom_id BIGINT UNSIGNED NULL,
  ADD COLUMN delivery_mode ENUM('onsite', 'online') DEFAULT 'onsite',
  ADD COLUMN hosting_cost DECIMAL(10,2) DEFAULT 0,
  ADD FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE SET NULL;
```

---

## New Functional Requirements

### FR-021: Private Lesson Package Management
System MUST support private lesson packages with:
- Predefined packages (10h, 20h, 30h) and custom packages
- Specialized packages (IELTS, intro)
- Two-rate tracking (student rate vs teacher rate)
- Profit margin calculation
- Top-up purchases when package depleted
- Ad-hoc session scheduling (not fixed calendar)
- Simultaneous enrollment in group courses + private lessons

### FR-022: Teacher Rate Management
System MUST support:
- Default group and private lesson rates per teacher
- Rate history with effective_from/effective_to dates
- Ad-hoc rate overrides for specific courses
- Rate application in cost calculations

### FR-023: Bespoke Group Course Management
System MUST support bespoke group courses with:
- Bulk quotation invoicing to company/partner
- Per-student itemization on invoice
- Custom delivery locations (on-site, third party)
- In-company student flagging
- Individual student payment tracking against bulk invoice
- Withdrawal handling (student drops, invoice adjusts)

### FR-024: Partner Management
System MUST track third-party partners with:
- Company details and contact information
- Variable discount rates per partner
- Enrollment-level partner association
- Partner-specific invoicing
- Income calculation with partner discounts applied

### FR-025: Advance Payment & Credit System
System MUST support:
- Student credit balance from advance payments
- Credit application to future enrollments
- Credit redemption logging
- Receipt generation (not full invoice) for credit usage
- Credit expiration tracking

### FR-026: Enhanced Discount System
System MUST provide:
- Automatic 5% discount for returning students (total_enrollments > 0)
- Manual tailored discounts per enrollment
- Free course option (is_free_course flag)
- Partner discount application
- Discount rule management

### FR-027: Classroom & Location Costing
System MUST track:
- Per-classroom hourly rates
- Online hosting costs (Zoom/Google Meet)
- Location assignment to sessions
- Cost calculation including classroom/hosting

### FR-028: Three-Level Notes System
System MUST support notes at:
- Student level (general notes)
- Enrollment level (course-specific notes)
- Course offering level (course-wide notes)

### FR-029: Automated Task & Reminder System
System MUST generate automated tasks for:
- Payment chase (7 days before course start if enrollment=pending)
- Lead follow-up (recurring after each contact)
- Teacher attendance sheet delivery (Friday before course start)
- Introductory email tracking
- Mid-course continuation email
- Certificate preparation reminder
- Manual due dates

### FR-030: Pre-Start Course Modification
System MUST support:
- Schedule changes before course starts (hours, days, dates)
- Automatic cost recalculation (teacher + classroom/hosting)
- Flag enrollments requiring manual price review
- Audit log of modifications

### FR-031: Mid-Course Student Transfer
System MUST support:
- Disenrollment from current course
- Enrollment in target course
- Transfer reason tracking
- Activity log with both course references
- Payment/credit handling (manual)

### FR-032: Custom Fields System
System MUST allow:
- User-defined fields for students, enrollments, course offerings
- Field types: text, number, date, boolean, select
- Required/optional configuration
- Display order management

### FR-033: Financial Reporting & Profitability
System MUST provide dashboards showing:
- Course profitability: (student payments) - (teacher cost) - (classroom cost) - (admin overhead)
- Monthly overview with oversubscribed/undersubscribed courses
- At-risk course identification
- Decision support for course viability

### FR-034: Cash Account & Non-Mollie Payments
System MUST support:
- Cash payment recording with cash account
- Bank transfer payments
- Manual payment entry
- Payment method tracking

### FR-035: Part Payment Support
System MUST support:
- Multiple partial payments per enrollment/invoice
- Payment status tracking
- Balance due calculation

---

## Implementation Priority

### Phase 1: Foundation (Immediate)
1. Teacher rates table
2. Classrooms table
3. Partners table
4. Discount rules enhancement
5. Course offerings modifications (classroom_id, rates, costs)
6. Enrollments modifications (partner, discounts, pricing)

### Phase 2: Private Lessons (High Priority)
1. Private lesson packages table
2. Private lesson sessions table
3. Package management UI
4. Session scheduling
5. Profit reporting

### Phase 3: Credits & Advanced Payments (High Priority)
1. Student credits table
2. Credit redemptions table
3. Credit application workflow
4. Receipt generation

### Phase 4: Automation (Medium Priority)
1. Automated tasks table
2. Task generation engine
3. Scheduled job for task creation
4. Dashboard widgets

### Phase 5: Custom Fields (Medium Priority)
1. Custom fields table
2. Custom field values table
3. Dynamic field rendering
4. Field management UI

### Phase 6: Enhancements (Lower Priority)
1. Pre-start modification workflow
2. Mid-course transfer workflow
3. Financial reporting dashboards
4. Cash account management

---

## Migration Strategy

### Backward Compatibility
- All new columns are NULL or have defaults
- Existing enrollments work without partner/discount/credit
- Existing course_offerings default to 'group_scheduled' type
- Activities table ENUM extended (backward compatible)

### Data Migration Steps
1. Create all new tables
2. Alter existing tables (add columns)
3. Seed discount_rules with default "5% returning student" rule
4. Create default classroom entries if known
5. Populate teacher_rates from existing data if available
6. Update is_returning flag based on enrollment count

### Testing Requirements
- Test existing group course workflow (unchanged)
- Test new private lesson package creation
- Test credit application to enrollment
- Test partner enrollment with bulk invoice
- Test automatic discount application
- Test course modification recalculation
- Test automated task generation

---

## Open Questions - RESOLVED ✅

1. **Book Costs**: ✅ RESOLVED
   - Sometimes included, sometimes not, depends on course type
   - **Implementation**: Add `book_included` BOOLEAN and `book_cost` DECIMAL fields to `course_offerings`
   - Invoice will add book as separate line item when `book_included=false`

2. **Administration Costs**: ✅ RESOLVED
   - Fixed percentage per enrollment
   - **Implementation**: Add `admin_overhead_percentage` to system settings (e.g., 15%)
   - Calculate as: `enrollment_price * (admin_overhead_percentage / 100)`

3. **Payment Terms for Partners**: ✅ RESOLVED
   - Partners receive 10% discount on course rate per student
   - Partners charge their own rates to their students (not our concern)
   - **Implementation**: Default partner discount = 10%, but configurable per partner in `partners.discount_percentage`

4. **Private Lesson Expiration**: ✅ RESOLVED
   - Packages do NOT expire (not date-bound)
   - Students schedule with teacher irregularly
   - No attendance tracking needed for private lessons
   - Teacher invoices school for hours delivered
   - School invoices student in advance for package
   - **Implementation**: Remove `expires_at` from `private_lesson_packages` schema

5. **Credit Expiration**: ✅ RESOLVED
   - Student credits expire after 1 year
   - **Implementation**: `student_credits.expires_at` = created_at + 1 year
   - Scheduled job marks credits as 'expired' when expires_at < NOW()

6. **Course Modification Recalc**: CLARIFICATION NEEDED
   - Should student amounts be auto-adjusted or always manual?
   - **Recommendation**: Always flag for manual review (safer for customer relations)

7. **Trial Session Handling**: ✅ RESOLVED
   - Use existing `enrollment.is_trial` flag
   - No separate trial session tracking needed
   - **Implementation**: Existing field sufficient

---

## Recommendations

### System Architecture
1. **Keep Mollie as primary** but support fallback payment methods
2. **Separate private lesson module** with its own workflows
3. **Partner invoicing** should generate separate invoice type
4. **Credit system** integrated but not required for basic operations
5. **Automated tasks** as separate microservice/scheduled job

### User Experience
1. **Dashboard widgets** for:
   - Pending payments (7-day warning)
   - At-risk courses (profitability)
   - Outstanding tasks
   - Credit balances
   
2. **Smart defaults**:
   - Auto-apply returning student discount
   - Auto-calculate course costs
   - Auto-generate tasks based on dates

3. **Manual overrides everywhere**:
   - Override calculated costs
   - Override discount rules
   - Override automated tasks

### Data Integrity
1. **Audit everything**: All financial changes logged
2. **Soft deletes**: Never hard-delete financial records
3. **Immutable invoices**: Once sent, create credit notes for changes
4. **Credit redemption trail**: Full audit of credit usage

---

## Next Steps

1. **User confirms**:
   - Answer open questions
   - Approve schema design
   - Set implementation priority

2. **Create migration files**:
   - One migration per new table
   - One migration for all column additions
   - Seed migration for default data

3. **Update models**:
   - Create new Eloquent models
   - Add relationships
   - Add scopes and helper methods

4. **API endpoints**:
   - CRUD for new entities
   - Special workflows (package purchase, credit application, etc.)

5. **Retool UI updates**:
   - New pages for private lessons, partners, credits
   - Enhanced enrollment form with discounts/credits
   - Dashboard widgets
   - Task management page

6. **Documentation**:
   - Update spec.md with all new requirements
   - Update data-model.md with new tables
   - Create workflow guides for new features
