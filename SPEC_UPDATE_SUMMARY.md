# Specification Update Summary
**Date**: November 25, 2025

## What Was Done

### 1. Analysis Documents Created ✅
- **`SPEC_UPDATE_ANALYSIS.md`**: Comprehensive gap analysis identifying what exists vs what's needed
- **`specs/001-title-english-language/spec-update-november-2025.md`**: Full specification of all new features
- **`specs/001-title-english-language/data-model-extended.md`**: Complete database schema documentation with all new tables and modifications

### 2. Requirements Clarified ✅
All open questions were answered:
- ✅ Book costs: Sometimes included, sometimes separate (depends on course type)
- ✅ Admin overhead: 15% per enrollment (configurable)
- ✅ Partner terms: 10% discount on course rate per student
- ✅ Private lesson expiration: Packages do NOT expire
- ✅ Credit expiration: 1 year from creation
- ✅ Trial sessions: Use existing `enrollment.is_trial` flag

### 3. New Features Documented ✅

**11 New Tables**:
1. `private_lesson_packages` - Hour packages for one-on-one instruction
2. `private_lesson_sessions` - Individual sessions against packages
3. `teacher_rates` - Rate management with history
4. `partners` - Third-party referral organizations
5. `student_credits` - Advance payment credit system
6. `credit_redemptions` - Credit usage audit trail
7. `classrooms` - Physical/online room rental costs
8. `discount_rules` - Automated and manual discounts
9. `automated_tasks` - System-generated reminders
10. `custom_fields` - User-defined field definitions
11. `custom_field_values` - Custom field data storage (EAV)

**7 Tables Modified**:
1. `course_offerings` - Added course types, costs, books, modification tracking
2. `enrollments` - Added partner, discounts, credits, books, admin overhead
3. `students` - Added returning flag, enrollment count, credit balance
4. `invoices` - Added invoice types, partner invoicing
5. `payments` - Added partial payment tracking, payment sources
6. `activities` - Extended for enrollment and course notes
7. `sessions` - Added classroom and delivery mode

**16 New Functional Requirements**:
- FR-021 through FR-035 covering all new features
- FR-004 updated for course types
- FR-009 updated for partner invoicing  
- FR-010 enhanced for part payments
- FR-015 extended for three-level notes

---

## Next Steps

### Immediate Actions Required

#### 1. Review Documentation ✅ COMPLETE
User has reviewed and approved the specification.

#### 2. Create Database Migrations 🔜 NEXT STEP
Need to create migration files for:

**Phase 1** (Foundation):
- `20251125_000001_create_partners_table.php`
- `20251125_000002_create_classrooms_table.php`
- `20251125_000003_create_discount_rules_table.php`
- `20251125_000004_create_teacher_rates_table.php`
- `20251125_000005_alter_course_offerings_extended.php`
- `20251125_000006_alter_enrollments_extended.php`
- `20251125_000007_alter_students_extended.php`
- `20251125_000008_alter_invoices_extended.php`
- `20251125_000009_alter_payments_extended.php`
- `20251125_000010_alter_sessions_extended.php`
- `20251125_000011_seed_discount_rules.php`

**Phase 2** (Private Lessons):
- `20251125_000012_create_private_lesson_packages_table.php`
- `20251125_000013_create_private_lesson_sessions_table.php`

**Phase 3** (Credits):
- `20251125_000014_create_student_credits_table.php`
- `20251125_000015_create_credit_redemptions_table.php`

**Phase 4** (Automation):
- `20251125_000016_create_automated_tasks_table.php`
- `20251125_000017_alter_activities_extended_enum.php`

**Phase 5** (Custom Fields):
- `20251125_000018_create_custom_fields_table.php`
- `20251125_000019_create_custom_field_values_table.php`

#### 3. Create Eloquent Models 🔜
New models needed:
- `PrivateLessonPackage.php`
- `PrivateLessonSession.php`
- `TeacherRate.php`
- `Partner.php`
- `StudentCredit.php`
- `CreditRedemption.php`
- `Classroom.php`
- `DiscountRule.php`
- `AutomatedTask.php`
- `CustomField.php`
- `CustomFieldValue.php`

Update existing models:
- `CourseOffering.php` - Add relationships and scopes
- `Enrollment.php` - Add relationships and calculated fields
- `Student.php` - Add relationships and scopes
- `Invoice.php` - Add invoice type handling
- `Payment.php` - Add payment source tracking
- `Activity.php` - Update entity type enum

#### 4. Create API Endpoints 🔜
New controller endpoints needed:
- `PrivateLessonPackageController` (CRUD + session management)
- `TeacherRateController` (CRUD + history)
- `PartnerController` (CRUD)
- `StudentCreditController` (create, apply, view balance)
- `ClassroomController` (CRUD)
- `DiscountRuleController` (CRUD)
- `AutomatedTaskController` (list, complete, create manual)
- `CustomFieldController` (CRUD for definitions and values)

Enhanced controllers:
- `EnrollmentController` - Add partner, discount, credit application
- `CourseOfferingController` - Add course type handling, cost calc
- `InvoiceController` - Add partner bulk invoicing
- `StudentController` - Add credit balance, package history

#### 5. Update Retool UI 🔜
New pages needed:
- Private Lessons management
- Teacher Rates configuration
- Partner management
- Student Credits & Redemptions
- Classroom management
- Discount Rules admin
- Task/Reminder dashboard
- Custom Fields configuration

Enhanced pages:
- Student Profile - Add packages, credits, three-level notes
- Course Management - Add course types, costs, books
- Enrollment Form - Add partner, discount, credit selection
- Invoice creation - Add partner bulk, itemization
- Dashboard - Add profitability widgets

#### 6. Implement Business Logic 🔜
Key workflows to build:
- Automatic returning student discount (5%)
- Credit application and redemption
- Course cost recalculation on modification
- Student transfer between courses
- Partner bulk invoicing
- Private lesson package purchase and depletion
- Automated task generation (scheduled job)
- Credit expiration (scheduled job)

#### 7. Testing 🔜
Test scenarios:
- Create private lesson package → Book sessions → Deplete package → Top up
- Create student credit → Apply to enrollment → Check balance → Expire after 1 year
- Enroll student through partner → Generate bulk invoice → Student withdraws
- Modify course before start → Verify cost recalc → Review enrollment prices
- Enroll returning student → Verify 5% discount automatic
- Transfer student mid-course → Verify both enrollments linked
- Generate automated tasks → Complete task → Verify dashboard
- Create custom fields → Add values to entities → Verify display

---

## Implementation Priority

### Phase 1: Foundation (Week 1) ⭐ CRITICAL
**Goal**: Core infrastructure for all features

**Tasks**:
1. Create migrations for partners, classrooms, teacher_rates, discount_rules
2. Alter course_offerings, enrollments, students, invoices, payments
3. Create Eloquent models for new tables
4. Update existing models with new relationships
5. Seed discount rules (5% returning student)
6. Basic CRUD API endpoints

**Deliverables**:
- Database supports all new structures
- Models have relationships defined
- Basic API endpoints functional
- Can create partners, classrooms, teacher rates manually

**Risk**: High - All subsequent phases depend on this

---

### Phase 2: Private Lessons (Week 2) ⭐ HIGH
**Goal**: Full private lesson package system

**Tasks**:
1. Create private_lesson_packages and sessions tables
2. Create models with relationships
3. Build package purchase workflow
4. Build session booking/delivery tracking
5. Profit margin calculations
6. Retool UI for package management

**Deliverables**:
- Can sell packages to students
- Can record sessions against packages
- Teachers can track hours delivered
- Profit margin visible

**Risk**: Medium - Complex two-rate system

---

### Phase 3: Credits & Advanced Payments (Week 2-3) ⭐ HIGH  
**Goal**: Student credit system operational

**Tasks**:
1. Create student_credits and credit_redemptions tables
2. Create models and redemption workflow
3. Build credit application to enrollment
4. Receipt generation
5. Credit expiration scheduled job
6. Retool UI for credit management

**Deliverables**:
- Can record advance payments as credits
- Can apply credits to enrollments
- Credits expire after 1 year automatically
- Students see credit balance on profile

**Risk**: Medium - Financial accuracy critical

---

### Phase 4: Automation (Week 3) ⚡ MEDIUM
**Goal**: Automated task generation working

**Tasks**:
1. Create automated_tasks table
2. Build task generation logic
3. Create scheduled job (daily run)
4. Dashboard widget for pending tasks
5. Email delivery for attendance sheets
6. Task management UI in Retool

**Deliverables**:
- Payment chase tasks generated 7 days before course
- Attendance sheet sent to teachers on Friday before start
- Mid-course continuation reminders
- Certificate prep reminders
- Dashboard shows pending tasks

**Risk**: Low - Nice-to-have, not blocking

---

### Phase 5: Custom Fields (Week 4) ⚡ LOW
**Goal**: User-defined fields available

**Tasks**:
1. Create custom_fields and custom_field_values tables
2. Build dynamic field rendering
3. Admin UI for field definition
4. Entity forms include custom fields
5. API returns custom fields with entities

**Deliverables**:
- Admin can create custom fields
- Fields appear on student/enrollment/course forms
- Values stored and retrieved correctly

**Risk**: Low - Optional feature

---

### Phase 6: Enhancements & Reporting (Week 4) ⚡ MEDIUM
**Goal**: Financial reporting and workflows operational

**Tasks**:
1. Course modification workflow with recalc
2. Student transfer workflow
3. Partner bulk invoicing
4. Profitability dashboard
5. Book cost handling
6. Three-level notes UI

**Deliverables**:
- Can modify courses and recalculate costs
- Can transfer students between courses
- Can generate partner bulk invoices
- Dashboard shows course profitability
- Books added to invoices when not included

**Risk**: Medium - Complex business logic

---

## Success Criteria

### Must Have (MVP)
- ✅ All Phase 1 complete (foundation)
- ✅ Private lessons functional (Phase 2)
- ✅ Credits functional (Phase 3)
- ✅ Partner enrollments working
- ✅ Automatic returning student discount
- ✅ Book costs on invoices

### Should Have
- ⚡ Automated tasks (Phase 4)
- ⚡ Course modification workflow
- ⚡ Student transfers
- ⚡ Profitability dashboard
- ⚡ Three-level notes UI

### Nice to Have
- 🌟 Custom fields (Phase 5)
- 🌟 Advanced reporting
- 🌟 Email automation for all reminders

---

## Risks & Mitigation

### High Risk Items

**1. Database Migration Complexity**
- Risk: 19 migration files, complex dependencies
- Mitigation: Test migrations in order, create rollback plan, use direct SQL fallback if needed

**2. Backward Compatibility**
- Risk: Existing course/enrollment records break
- Mitigation: All new columns NULL or have defaults, seed data for rules

**3. Financial Calculation Accuracy**
- Risk: Wrong profit margins, incorrect credit balances
- Mitigation: Extensive unit tests, manual QA of calculations, use GENERATED columns in MySQL

**4. Performance with Calculated Fields**
- Risk: Complex queries slow dashboard
- Mitigation: Cache calculated values, use indexed queries, consider materialized views

### Medium Risk Items

**1. Teacher Rate History**
- Risk: Wrong rate applied to historical courses
- Mitigation: effective_from/effective_to logic with unit tests

**2. Credit Expiration**
- Risk: Credits not expired correctly
- Mitigation: Scheduled job with logging, admin can manually expire

**3. Automated Task Duplicates**
- Risk: Same task created multiple times
- Mitigation: Idempotent creation logic with unique checks

---

## Timeline Estimate

**Total Duration**: 4-6 weeks

| Phase | Duration | Dependencies |
|-------|----------|-------------|
| Phase 1 (Foundation) | 5-7 days | None |
| Phase 2 (Private Lessons) | 3-5 days | Phase 1 |
| Phase 3 (Credits) | 3-5 days | Phase 1 |
| Phase 4 (Automation) | 3-4 days | Phase 1 |
| Phase 5 (Custom Fields) | 2-3 days | Phase 1 |
| Phase 6 (Reporting) | 5-7 days | Phases 1-3 |
| **Testing & QA** | 5-7 days | All phases |
| **Buffer** | 5 days | - |

**Critical Path**: Phase 1 → Phase 2 → Phase 3 → Phase 6 → Testing

---

## Communication Plan

### Stakeholder Updates
- Weekly status reports on phase completion
- Demo sessions after each phase
- User acceptance testing before production deployment

### Documentation
- ✅ Specification complete
- ✅ Data model documented
- 🔜 API documentation (Swagger/Postman)
- 🔜 Retool user guides
- 🔜 Admin training materials

---

## Decision Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2025-11-25 | Book costs configurable per course | Different course types have different policies |
| 2025-11-25 | Admin overhead = 15% per enrollment | Simplifies profitability calc, can be overridden |
| 2025-11-25 | Partner discount = 10% default | Industry standard, configurable per partner |
| 2025-11-25 | Private lesson packages don't expire | Students schedule irregularly, pressure-free learning |
| 2025-11-25 | Student credits expire after 1 year | Protects school from indefinite liabilities |
| 2025-11-25 | Use existing is_trial for trial sessions | Simpler than separate trial_session flag |
| 2025-11-25 | EAV pattern for custom fields | Flexible without schema changes |
| 2025-11-25 | GENERATED columns for calculations | Database-level data integrity, reduces bugs |
| 2025-11-25 | Three-level notes via activities table | Reuse existing infrastructure, polymorphic design |
| 2025-11-25 | Automated tasks via scheduled job | Separation of concerns, easier to test |

---

## Ready to Proceed? ✅

The specification is complete and approved. The next concrete action is:

**CREATE DATABASE MIGRATIONS** (Phase 1)

Would you like me to:
1. Create all 11 migration files for Phase 1?
2. Start with just the new tables (partners, classrooms, teacher_rates, discount_rules)?
3. Review the specification documents first?

Please confirm and I'll proceed with creating the migration files.
