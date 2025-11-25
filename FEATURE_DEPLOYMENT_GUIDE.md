# New Features Deployment Guide

## Files to Upload to Server

### New Files (created):
1. `app/Models/Activity.php`
2. `app/Http/Controllers/ActivityController.php`
3. `app/Http/Controllers/MollieWebhookController.php`
4. `app/Console/Commands/UpdateEnrollmentStatuses.php`
5. `app/Console/Commands/CreatePaymentReminders.php`
6. `app/Console/Kernel.php`

### Modified Files:
1. `app/Models/Lead.php`
2. `app/Models/Student.php`
3. `app/Models/Enrollment.php`
4. `app/Http/Controllers/LeadController.php`
5. `app/Http/Controllers/EnrollmentController.php`
6. `routes/web.php`

## Deployment Commands (Run on Server)

```bash
# SSH into server
ssh u5021d9810@web0091

# Navigate to app directory
cd domains/mixtreelangdb.nl/mtl_app

# Upload files using SCP or SFTP (from local machine)
# Example for each file:
# scp app/Models/Activity.php u5021d9810@web0091:domains/mixtreelangdb.nl/mtl_app/app/Models/
# scp app/Http/Controllers/ActivityController.php u5021d9810@web0091:domains/mixtreelangdb.nl/mtl_app/app/Http/Controllers/
# etc...

# After uploading, regenerate autoload
composer dump-autoload

# Test scheduled commands manually
/opt/alt/php82/usr/bin/php artisan enrollments:update-statuses
/opt/alt/php82/usr/bin/php artisan enrollments:create-payment-reminders

# Set up cron job for scheduled tasks (add to crontab)
crontab -e
# Add this line:
# * * * * * cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app && /opt/alt/php82/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## Verification Steps

### 1. Test Activity API Endpoints
```bash
# Create a test activity (replace TOKEN with your API token)
curl -X POST https://mixtreelangdb.nl/api/activities \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "entity_type": "Lead",
    "entity_id": 1,
    "activity_type": "note",
    "subject": "Test Note",
    "body": "This is a test activity"
  }'

# Get activities for a lead
curl -X GET "https://mixtreelangdb.nl/api/activities?entity_type=Lead&entity_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. Test Enrollment Workflow
```bash
# Create pending enrollment
curl -X POST https://mixtreelangdb.nl/api/enrollments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 1,
    "course_offering_id": 1,
    "status": "pending"
  }'

# Manually activate enrollment with override
curl -X POST https://mixtreelangdb.nl/api/enrollments/1/activate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Payment confirmed via bank transfer"
  }'
```

### 3. Test Mollie Webhook (Simulated)
```bash
# Simulate Mollie webhook call (from server or use Postman)
curl -X POST https://mixtreelangdb.nl/webhooks/mollie \
  -H "Content-Type: application/json" \
  -d '{
    "id": "tr_xxxxx"
  }'
```

### 4. Verify Database Changes
```sql
-- Check activities table
SELECT * FROM activities ORDER BY created_at DESC LIMIT 10;

-- Check enrollments with new fields
SELECT id, student_id, status, payment_override_reason 
FROM enrollments 
WHERE status = 'pending' OR payment_override_reason IS NOT NULL;

-- Check lead source tracking
SELECT id, first_name, last_name, reference, source_detail 
FROM leads 
WHERE reference IS NOT NULL;
```

## Configuration Notes

### Mollie Webhook URL
Configure in Mollie dashboard:
- Webhook URL: `https://mixtreelangdb.nl/webhooks/mollie`
- This endpoint is **unprotected** (no auth token required) as Mollie needs to call it

### Scheduled Tasks
Two commands run automatically via Laravel scheduler:
1. `enrollments:update-statuses` - Daily at 2:00 AM
2. `enrollments:create-payment-reminders` - Daily at 9:00 AM

**Important**: You must add the cron job to run Laravel's scheduler:
```
* * * * * cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app && /opt/alt/php82/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## New Features Summary

### 1. Lead Source Tracking
- Added `reference` (lead type) and `source_detail` (marketing source) ENUMs
- Available in Lead creation/update APIs
- Use for analytics and marketing attribution

### 2. Enrollment Workflow Enhancement
- New `pending` status for enrollments awaiting payment
- Auto-activation when Mollie payment confirmed
- Manual activation with `payment_override_reason` for exceptions
- Transfer tracking between course enrollments

### 3. Activity Timeline
- Polymorphic activities table for leads, students, enrollments
- Activity types: note, call, email, meeting, level_check, payment, enrollment, other
- Replaces text-based `activity_notes` and `profile_notes`
- Full CRUD API at `/api/activities`

### 4. Automated Workflows
- Auto-complete enrollments when course ends
- Auto-activate registered enrollments when course starts
- Create payment reminder tasks for pending enrollments after 7 days

## Testing Checklist

- [ ] Upload all files successfully
- [ ] Run `composer dump-autoload`
- [ ] Test Activity API endpoints
- [ ] Test enrollment creation with pending status
- [ ] Test manual enrollment activation
- [ ] Configure Mollie webhook URL
- [ ] Add cron job for scheduler
- [ ] Run scheduled commands manually to verify
- [ ] Check database for migrated activities
- [ ] Verify lead source dropdowns work in UI
- [ ] Test payment webhook (if possible with test payment)
