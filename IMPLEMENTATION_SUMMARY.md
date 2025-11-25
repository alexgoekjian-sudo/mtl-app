# Implementation Summary: Enhanced Lead & Enrollment Management

**Date**: November 21, 2025  
**Status**: Database migrations created, specifications updated, ready for implementation

---

## What Was Implemented

### 1. ✅ Specification Updates (spec.md)

Added comprehensive section "Additional Requirements (Added: November 21, 2025)" covering:

- **FR-015**: Lead Source Tracking Enhancement
  - `reference` column: lead type (online_form, level_check, phone_call, walk_in, referral, other)
  - `source_detail` column: marketing attribution (google, facebook, instagram, ai, linkedin, etc.)

- **FR-016 & FR-017**: Enrollment Workflow & Payment Status
  - New `pending` status for enrollments awaiting payment
  - Automatic progression: pending → registered (on payment) → active (on course start)
  - Manual `payment_override` with required reason field
  - Trial enrollment tracking (`is_trial` flag)
  - Course transfer capability with audit trail

- **FR-018**: Activity Timeline
  - New `activities` table replacing text-based `activity_notes`
  - Polymorphic design (relates to Lead, Student, Enrollment, etc.)
  - Activity types: note, call, email, meeting, level_check, payment, enrollment, other
  - Activities transfer from lead to student on conversion

- **FR-019**: Course History View
  - Display current courses vs. course history on student profile
  - Historical enrollments flagged with `is_historical`
  - Import metadata stored in JSON field

- **FR-020**: Payment Reminders & Enrollment Tracking
  - Auto-create tasks for pending payments within 7 days of course start
  - Dashboard "Pending Payments" widget
  - "At Risk" flags for payment overrides without payment
  - Trial conversion follow-up tasks

### 2. ✅ Database Migrations Created

#### Migration 1: `20251121_000001_add_lead_source_tracking.php`
```sql
ALTER TABLE leads 
  ADD COLUMN reference ENUM('online_form', 'level_check', 'phone_call', 'walk_in', 'referral', 'other'),
  ADD COLUMN source_detail ENUM('google', 'facebook', 'instagram', 'ai', 'linkedin', 'referral_name', 'website_direct', 'other'),
  ADD INDEX idx_lead_reference (reference),
  ADD INDEX idx_lead_source_detail (source_detail);
```

**Purpose**: Enable detailed lead source tracking for marketing analytics

#### Migration 2: `20251121_000002_enhance_enrollment_workflow.php`
```sql
-- Modify enrollment status ENUM
ALTER TABLE enrollments 
  MODIFY COLUMN status ENUM('pending', 'registered', 'active', 'cancelled', 'completed') DEFAULT 'pending';

-- Add payment override and transfer tracking
ALTER TABLE enrollments
  ADD COLUMN payment_override_reason TEXT,
  ADD COLUMN transferred_from_enrollment_id BIGINT UNSIGNED,
  ADD COLUMN transferred_to_enrollment_id BIGINT UNSIGNED,
  ADD INDEX idx_enrollment_status (status);
```

**Purpose**: Support pending enrollments, payment overrides, and course transfers

**Note**: `is_historical` and `historical_metadata` columns already added by previous migration `20251119_000001_add_historical_course_fields.php`

#### Migration 3: `20251121_000003_create_activities_table.php`
```sql
CREATE TABLE activities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  related_entity_type VARCHAR(255) NOT NULL,  -- 'Lead', 'Student', 'Enrollment'
  related_entity_id BIGINT UNSIGNED NOT NULL,
  activity_type ENUM('note', 'call', 'email', 'meeting', 'level_check', 'payment', 'enrollment', 'other'),
  subject VARCHAR(255),
  body TEXT,
  created_by_user_id BIGINT UNSIGNED,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  INDEX idx_activity_entity (related_entity_type, related_entity_id),
  INDEX idx_activity_type (activity_type),
  INDEX idx_activity_created_at (created_at),
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

**Purpose**: Replace text-based notes with structured timeline of interactions

#### Migration 4: `20251121_000004_migrate_notes_to_activities.php`

**Purpose**: Migrate existing `leads.activity_notes` and `students.profile_notes` to new `activities` table

**Process**:
1. Read all leads with non-empty `activity_notes`
2. Insert into `activities` as type='note', subject='Historical Notes'
3. Read all students with non-empty `profile_notes`
4. Insert into `activities` as type='note', subject='Historical Profile Notes'

**Note**: Original columns kept for backward compatibility (can be deprecated after UI update)

---

## Database Schema Changes Summary

### Tables Modified
1. **leads**: +2 columns (reference, source_detail), +2 indexes
2. **enrollments**: Modified status ENUM, +3 columns (payment_override_reason, transferred_from/to), +1 index, +2 foreign keys
3. **course_offerings**: Already has `is_historical` from previous migration

### Tables Created
4. **activities**: New table for timestamped activity timeline

---

## Migration Execution Order

Run migrations in this sequence:

```bash
# 1. Lead source tracking
php artisan migrate --path=database/migrations/20251121_000001_add_lead_source_tracking.php

# 2. Enrollment workflow enhancements
php artisan migrate --path=database/migrations/20251121_000002_enhance_enrollment_workflow.php

# 3. Create activities table
php artisan migrate --path=database/migrations/20251121_000003_create_activities_table.php

# 4. Migrate existing notes to activities
php artisan migrate --path=database/migrations/20251121_000004_migrate_notes_to_activities.php
```

Or run all at once:
```bash
php artisan migrate
```

---

## Application Code Changes Needed

### 1. Models to Create/Update

#### Lead Model
```php
// app/Models/Lead.php
protected $fillable = [..., 'reference', 'source_detail'];

protected $casts = [
    'reference' => 'string',
    'source_detail' => 'string',
];

public function activities()
{
    return $this->morphMany(Activity::class, 'related_entity');
}
```

#### Student Model
```php
// app/Models/Student.php
public function activities()
{
    return $this->morphMany(Activity::class, 'related_entity');
}

public function currentEnrollments()
{
    return $this->hasMany(Enrollment::class)
        ->whereIn('status', ['pending', 'registered', 'active'])
        ->orderBy('enrolled_at', 'desc');
}

public function courseHistory()
{
    return $this->hasMany(Enrollment::class)
        ->where(function($q) {
            $q->whereIn('status', ['completed', 'cancelled'])
              ->orWhere('is_historical', true);
        })
        ->orderBy('enrolled_at', 'desc');
}
```

#### Enrollment Model
```php
// app/Models/Enrollment.php
protected $fillable = [..., 'payment_override_reason', 'transferred_from_enrollment_id', 'transferred_to_enrollment_id'];

protected $casts = [
    'is_historical' => 'boolean',
    'historical_metadata' => 'array',
];

public function transferredFrom()
{
    return $this->belongsTo(Enrollment::class, 'transferred_from_enrollment_id');
}

public function transferredTo()
{
    return $this->belongsTo(Enrollment::class, 'transferred_to_enrollment_id');
}

public function scopePending($query)
{
    return $query->where('status', 'pending');
}

public function scopeActive($query)
{
    return $query->whereIn('status', ['registered', 'active']);
}
```

#### Activity Model (NEW)
```php
// app/Models/Activity.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'related_entity_type',
        'related_entity_id',
        'activity_type',
        'subject',
        'body',
        'created_by_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the owning entity (Lead, Student, Enrollment, etc.)
     */
    public function relatedEntity()
    {
        return $this->morphTo('related_entity');
    }

    /**
     * Get the user who created the activity
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Scope: Filter by entity type
     */
    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('related_entity_type', $entityType)
                     ->where('related_entity_id', $entityId);
    }

    /**
     * Scope: Filter by activity type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope: Order by newest first
     */
    public function scopeNewest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
```

### 2. Controllers to Update

#### LeadController
```php
// app/Http/Controllers/LeadController.php

// In store() method:
$lead = Lead::create([
    'reference' => $request->reference,  // NEW
    'source_detail' => $request->source_detail,  // NEW
    'first_name' => $request->first_name,
    // ... other fields
]);

// Create initial activity
Activity::create([
    'related_entity_type' => 'Lead',
    'related_entity_id' => $lead->id,
    'activity_type' => 'note',
    'subject' => 'Lead Created',
    'body' => "Lead created via {$request->reference}",
    'created_by_user_id' => auth()->id(),
]);
```

#### EnrollmentController
```php
// app/Http/Controllers/EnrollmentController.php

// In store() method:
$enrollment = Enrollment::create([
    'student_id' => $request->student_id,
    'course_offering_id' => $request->course_offering_id,
    'status' => 'pending',  // NEW DEFAULT
    'is_trial' => $request->is_trial ?? false,
    'enrolled_at' => now(),
]);

// Create invoice and send payment link
$invoice = $this->createInvoice($enrollment);
$this->sendPaymentLink($student, $invoice);

// Manual payment override
public function grantPaymentOverride(Request $request, Enrollment $enrollment)
{
    $request->validate([
        'reason' => 'required|string|min:10'
    ]);

    $enrollment->update([
        'payment_override_reason' => $request->reason,
        'status' => 'registered',
    ]);

    Activity::create([
        'related_entity_type' => 'Enrollment',
        'related_entity_id' => $enrollment->id,
        'activity_type' => 'payment',
        'subject' => 'Payment Override Granted',
        'body' => "Reason: {$request->reason}",
        'created_by_user_id' => auth()->id(),
    ]);

    return response()->json(['message' => 'Payment override granted']);
}

// Course transfer
public function transfer(Request $request, Enrollment $enrollment)
{
    $targetCourse = CourseOffering::findOrFail($request->target_course_id);

    // Create new enrollment
    $newEnrollment = Enrollment::create([
        'student_id' => $enrollment->student_id,
        'course_offering_id' => $targetCourse->id,
        'status' => $enrollment->status,  // Keep same status
        'transferred_from_enrollment_id' => $enrollment->id,
        'enrolled_at' => now(),
    ]);

    // Update old enrollment
    $enrollment->update([
        'status' => 'cancelled',
        'transferred_to_enrollment_id' => $newEnrollment->id,
        'dropped_at' => now(),
    ]);

    // Log activity
    Activity::create([
        'related_entity_type' => 'Student',
        'related_entity_id' => $enrollment->student_id,
        'activity_type' => 'enrollment',
        'subject' => 'Course Transfer',
        'body' => "Transferred from {$enrollment->courseOffering->course_full_name} to {$targetCourse->course_full_name}. Reason: {$request->reason}",
        'created_by_user_id' => auth()->id(),
    ]);

    return response()->json(['new_enrollment' => $newEnrollment]);
}
```

#### WebhookController (Mollie)
```php
// app/Http/Controllers/MollieWebhookController.php

public function handle(Request $request)
{
    $paymentId = $request->id;
    $molliePayment = $this->mollie->payments->get($paymentId);

    if ($molliePayment->isPaid()) {
        $invoice = Invoice::where('external_payment_refs', 'LIKE', "%{$paymentId}%")->first();

        if ($invoice) {
            // Record payment
            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $molliePayment->amount->value,
                'status' => 'completed',
                'method' => 'mollie',
                'external_reference' => $paymentId,
                'recorded_at' => now(),
            ]);

            // Update invoice
            $invoice->update(['status' => 'paid']);

            // AUTO-UPDATE ENROLLMENT: pending → registered
            $enrollment = Enrollment::where('student_id', $invoice->student_id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($enrollment) {
                $enrollment->update(['status' => 'registered']);

                // Log activity
                Activity::create([
                    'related_entity_type' => 'Enrollment',
                    'related_entity_id' => $enrollment->id,
                    'activity_type' => 'payment',
                    'subject' => 'Payment Received',
                    'body' => "Payment confirmed via Mollie. Invoice #{$invoice->invoice_number}. Amount: €{$molliePayment->amount->value}",
                    'created_by_user_id' => null,  // Automatic system action
                ]);
            }
        }
    }

    return response('OK', 200);
}
```

### 3. Scheduled Jobs/Commands

#### Auto-Update Enrollment Status
```php
// app/Console/Commands/UpdateEnrollmentStatuses.php
<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\Activity;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateEnrollmentStatuses extends Command
{
    protected $signature = 'enrollments:update-statuses';
    protected $description = 'Update enrollment statuses (registered → active when course starts)';

    public function handle()
    {
        // Find enrollments that should be activated
        $enrollments = Enrollment::where('status', 'registered')
            ->whereHas('courseOffering', function($q) {
                $q->where('start_date', '<=', Carbon::today());
            })
            ->get();

        foreach ($enrollments as $enrollment) {
            $enrollment->update(['status' => 'active']);

            Activity::create([
                'related_entity_type' => 'Enrollment',
                'related_entity_id' => $enrollment->id,
                'activity_type' => 'enrollment',
                'subject' => 'Course Started',
                'body' => "Course {$enrollment->courseOffering->course_full_name} started. Enrollment status: active",
                'created_by_user_id' => null,
            ]);

            $this->info("Activated enrollment #{$enrollment->id}");
        }

        $this->info("Processed {$enrollments->count()} enrollments");
    }
}

// Register in app/Console/Kernel.php:
protected function schedule(Schedule $schedule)
{
    $schedule->command('enrollments:update-statuses')->daily();
}
```

#### Payment Reminder Tasks
```php
// app/Console/Commands/CreatePaymentReminders.php
<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\Task;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CreatePaymentReminders extends Command
{
    protected $signature = 'payments:create-reminders';
    protected $description = 'Create reminder tasks for pending payments';

    public function handle()
    {
        $threshold = Carbon::today()->addDays(7);

        $pendingEnrollments = Enrollment::where('status', 'pending')
            ->whereHas('courseOffering', function($q) use ($threshold) {
                $q->where('start_date', '<=', $threshold)
                  ->where('start_date', '>=', Carbon::today());
            })
            ->with(['student', 'courseOffering'])
            ->get();

        foreach ($pendingEnrollments as $enrollment) {
            // Check if task already exists
            $existingTask = Task::where('related_entity_type', 'Enrollment')
                ->where('related_entity_id', $enrollment->id)
                ->where('status', 'pending')
                ->where('title', 'LIKE', 'Payment Pending:%')
                ->first();

            if ($existingTask) {
                continue;  // Skip, already created
            }

            $daysUntilStart = Carbon::today()->diffInDays($enrollment->courseOffering->start_date);
            $invoice = $enrollment->student->invoices()->where('status', '!=', 'paid')->latest()->first();

            Task::create([
                'title' => "Payment Pending: {$enrollment->student->first_name} {$enrollment->student->last_name} - {$enrollment->courseOffering->course_full_name}",
                'body' => "Student: {$enrollment->student->first_name} {$enrollment->student->last_name}\n" .
                          "Course: {$enrollment->courseOffering->course_full_name}\n" .
                          "Amount Due: €" . ($invoice->total ?? 'N/A') . "\n" .
                          "Start Date: {$enrollment->courseOffering->start_date} (in {$daysUntilStart} days)\n" .
                          "Invoice: #" . ($invoice->invoice_number ?? 'N/A'),
                'related_entity_type' => 'Enrollment',
                'related_entity_id' => $enrollment->id,
                'due_at' => Carbon::parse($enrollment->courseOffering->start_date)->subDays(2),
                'status' => 'pending',
                'priority' => $daysUntilStart <= 3 ? 'high' : 'medium',
                'created_by_user_id' => null,  // System-generated
            ]);

            $this->info("Created reminder for enrollment #{$enrollment->id}");
        }

        $this->info("Processed {$pendingEnrollments->count()} pending enrollments");
    }
}

// Register in app/Console/Kernel.php:
protected function schedule(Schedule $schedule)
{
    $schedule->command('payments:create-reminders')->daily();
}
```

---

## UI Changes Needed

### 1. Lead Capture Form
- Add dropdown: "Lead Type" (reference)
- Add dropdown: "Marketing Source" (source_detail)

### 2. Student Profile Page
- Add "Activity Timeline" section with filtered tabs (All, Notes, Calls, Emails, Meetings, etc.)
- Add "Current Courses" section
- Add "Course History" section with badge for historical imports

### 3. Enrollment Management
- Add "Grant Payment Override" button (opens modal with reason field)
- Add "Transfer to Different Course" button
- Show payment status badge (Pending, Registered, Active, etc.)

### 4. Dashboard Widgets
- Add "Pending Payments" widget showing enrollments needing payment within 7 days
- Add "At Risk Enrollments" showing payment overrides without payment after course start

---

## Testing Checklist

### Unit Tests
- [ ] Lead source tracking fields save correctly
- [ ] Enrollment status transitions (pending → registered → active)
- [ ] Payment override stores reason and updates status
- [ ] Course transfer creates new enrollment and cancels old one
- [ ] Activities polymorphic relation works for Lead, Student, Enrollment
- [ ] Historical enrollment queries work correctly

### Integration Tests
- [ ] Mollie webhook updates enrollment status from pending to registered
- [ ] Scheduled job activates enrollments when course starts
- [ ] Payment reminder tasks created for pending enrollments within 7 days
- [ ] Lead-to-student conversion transfers all activities

### E2E Tests
- [ ] Create lead with source tracking → verify saved
- [ ] Create enrollment → verify status=pending → pay → verify status=registered
- [ ] Grant payment override → verify status updated and logged
- [ ] Add activity to lead → convert to student → verify activity transferred
- [ ] View student profile → verify current courses and history displayed correctly

---

## Deployment Steps

1. **Backup database**
   ```bash
   php artisan db:backup  # or manual mysqldump
   ```

2. **Run migrations**
   ```bash
   php artisan migrate
   ```

3. **Verify migrations**
   ```bash
   php artisan tinker
   >>> Schema::hasColumn('leads', 'reference')  // should return true
   >>> Schema::hasColumn('enrollments', 'payment_override_reason')  // should return true
   >>> Schema::hasTable('activities')  // should return true
   ```

4. **Update application code** (models, controllers, views)

5. **Clear caches**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

6. **Test in staging** before production

7. **Register scheduled commands**
   - Verify cron job runs `php artisan schedule:run` every minute

---

## Documentation Created

1. ✅ **REQUIREMENTS_ANALYSIS.md** - Comprehensive analysis of all 5 requirements
2. ✅ **specs/001-title-english-language/spec.md** - Updated with FR-015 through FR-020
3. ✅ **This file** - Implementation summary and code examples

---

## Next Steps

1. Review migrations and spec updates with team
2. Run migrations on development database
3. Create Laravel models (Activity, update Lead/Student/Enrollment)
4. Update controllers with new workflow logic
5. Build UI components (activity timeline, course history, payment override)
6. Implement scheduled jobs (status updates, payment reminders)
7. Write tests
8. Deploy to staging for UAT
9. Prepare historical data import CSV from Trello

---

## Questions to Address Before Going Live

1. **Lead Source Dropdowns**: Should we prepopulate any existing `source` data into `reference` or `source_detail`?
2. **Enrollment Status**: Should existing enrollments default to `registered` or `active` based on course start date?
3. **Activities Migration**: After migrating notes to activities, when can we deprecate `activity_notes` and `profile_notes` columns?
4. **Payment Reminders**: Who should be assigned these tasks? (coordinator, admin, specific user role?)
5. **Historical Data**: Is the Trello CSV ready for import? Need sample to finalize import script.

