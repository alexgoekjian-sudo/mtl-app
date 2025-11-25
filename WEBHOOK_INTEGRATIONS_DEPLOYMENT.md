# Webhook Integrations - Deployment Summary

## Overview

Two new webhook integrations have been added to automatically create leads from:
1. **Cal.com bookings** (level checks)
2. **WordPress Contact Form 7 submissions**

---

## Files Created

### Controllers
1. `app/Http/Controllers/CalComWebhookController.php` - Handles Cal.com webhooks
2. `app/Http/Controllers/ContactFormWebhookController.php` - Handles CF7 webhooks

### Routes
- Updated `routes/web.php` with new webhook endpoints:
  - `POST /webhooks/calcom`
  - `POST /webhooks/contact-form`

### Documentation
1. `CALCOM_INTEGRATION.md` - Complete Cal.com setup guide
2. `WORDPRESS_CF7_INTEGRATION.md` - Complete WordPress CF7 setup guide

---

## Database Schema - Already Complete ✅

No database changes needed! Existing tables support the functionality:

- ✅ `leads` table - Has `reference` and `source_detail` fields
- ✅ `bookings` table - Stores Cal.com booking data
- ✅ `webhook_events` table - Tracks all webhook processing
- ✅ `activities` table - Logs booking/submission activities

---

## Deployment Steps

### 1. Upload New Files

```bash
# Upload controllers
scp app/Http/Controllers/CalComWebhookController.php u5021d9810@web0091:/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/app/Http/Controllers/

scp app/Http/Controllers/ContactFormWebhookController.php u5021d9810@web0091:/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/app/Http/Controllers/

# Upload updated routes
scp routes/web.php u5021d9810@web0091:/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/routes/
```

### 2. Regenerate Autoload

```bash
ssh u5021d9810@web0091 "cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app && composer dump-autoload"
```

### 3. Test Endpoints

**Test Cal.com webhook:**
```bash
curl -X POST https://mixtreelangdb.nl/webhooks/calcom \
  -H "Content-Type: application/json" \
  -d '{
    "uid": "test-123",
    "payload": {
      "startTime": "2025-12-01T10:00:00Z",
      "status": "ACCEPTED",
      "responses": {
        "name": "Test User",
        "email": "test@example.com",
        "phone": "+31612345678"
      }
    }
  }'
```

Expected: `{"status":"processed"}`

**Test Contact Form webhook:**
```bash
curl -X POST https://mixtreelangdb.nl/webhooks/contact-form \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+31612345678",
    "message": "I want to enroll in B1 course"
  }'
```

Expected: `{"status":"processed"}`

### 4. Verify Database

**Check leads created:**
```sql
SELECT * FROM leads ORDER BY created_at DESC LIMIT 5;
```

**Check webhook events:**
```sql
SELECT * FROM webhook_events ORDER BY created_at DESC LIMIT 5;
```

**Check bookings (Cal.com):**
```sql
SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5;
```

**Check activities:**
```sql
SELECT * FROM activities ORDER BY created_at DESC LIMIT 5;
```

---

## Configure External Services

### Cal.com Setup

1. **Log into Cal.com** → Settings → Developer → Webhooks
2. **Add Webhook**:
   - URL: `https://mixtreelangdb.nl/webhooks/calcom`
   - Event: `BOOKING_CREATED`
   - Format: JSON
3. **Test** with a real booking

**Detailed Guide**: See `CALCOM_INTEGRATION.md`

### WordPress Contact Form 7 Setup

**Option 1: CF7 Webhook Plugin (Recommended)**

1. Install "CF7 to Webhook" plugin
2. Configure webhook URL: `https://mixtreelangdb.nl/webhooks/contact-form`
3. Test form submission

**Option 2: Custom Code**

Add to `functions.php` (see `WORDPRESS_CF7_INTEGRATION.md` for code)

**Detailed Guide**: See `WORDPRESS_CF7_INTEGRATION.md`

---

## How It Works

### Cal.com Flow

```
User books level check on Cal.com
    ↓
Cal.com sends POST to /webhooks/calcom
    ↓
System checks idempotency (avoid duplicates)
    ↓
Lead created/updated (reference: 'level_check')
    ↓
Booking record created
    ↓
Activity logged on lead
    ↓
Webhook event recorded
```

**Result**: Lead with booking in database, ready for teacher assignment

### Contact Form 7 Flow

```
User submits form on WordPress site
    ↓
WordPress sends POST to /webhooks/contact-form
    ↓
System checks idempotency
    ↓
Lead created/updated (reference: 'online_form')
    ↓
Form message saved as activity
    ↓
Webhook event recorded
```

**Result**: Lead in database with form message in activity log

---

## Field Mapping

### Cal.com → Lead/Booking

| Cal.com Field | Database Field | Table |
|---------------|----------------|-------|
| `responses.name` | `first_name` + `last_name` | `leads` |
| `responses.email` | `email` | `leads` |
| `responses.phone` | `phone` | `leads` |
| `responses.notes` | `activity_notes` | `leads` |
| | `reference = 'level_check'` | `leads` |
| | `source_detail = 'website_direct'` | `leads` |
| `uid` | `external_booking_id` | `bookings` |
| `payload.startTime` | `scheduled_at` | `bookings` |
| `payload.status` | `status` | `bookings` |
| Full payload | `webhook_payload` (JSON) | `bookings` |

### Contact Form 7 → Lead

| Form Field | Database Field |
|------------|----------------|
| `first_name` / `first-name` | `first_name` |
| `last_name` / `last-name` | `last_name` |
| `email` / `your-email` | `email` |
| `phone` / `your-phone` | `phone` |
| `message` / `your-message` | Saved as activity |
| | `reference = 'online_form'` |
| | `source_detail = auto-detected or 'website_direct'` |

---

## Monitoring

### Check Recent Webhook Activity

```sql
-- All webhook events
SELECT provider, event_type, status, created_at 
FROM webhook_events 
ORDER BY created_at DESC 
LIMIT 20;

-- Failed webhooks
SELECT provider, external_id, error_message, created_at 
FROM webhook_events 
WHERE status = 'failed' 
ORDER BY created_at DESC;

-- Webhooks by provider
SELECT 
  provider,
  COUNT(*) as total,
  SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as successful,
  SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
FROM webhook_events
GROUP BY provider;
```

### Check Recent Leads

```sql
-- Leads from Cal.com
SELECT id, first_name, last_name, email, created_at 
FROM leads 
WHERE reference = 'level_check' 
ORDER BY created_at DESC 
LIMIT 10;

-- Leads from Contact Forms
SELECT id, first_name, last_name, email, created_at 
FROM leads 
WHERE reference = 'online_form' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Check Bookings

```sql
SELECT 
  b.scheduled_at,
  l.first_name,
  l.last_name,
  l.email,
  b.status
FROM bookings b
JOIN leads l ON b.lead_id = l.id
WHERE b.booking_type = 'level_check'
ORDER BY b.scheduled_at DESC
LIMIT 10;
```

---

## Retool Updates

### New Queries to Add

**1. Upcoming Level Checks**
```sql
SELECT 
  b.id,
  b.scheduled_at,
  CONCAT(l.first_name, ' ', l.last_name) as name,
  l.email,
  l.phone,
  b.status,
  b.assigned_teacher_id
FROM bookings b
JOIN leads l ON b.lead_id = l.id
WHERE b.booking_type = 'level_check'
  AND b.status = 'scheduled'
  AND b.scheduled_at >= NOW()
ORDER BY b.scheduled_at ASC;
```

**2. Recent Form Submissions**
```sql
SELECT 
  l.id,
  l.first_name,
  l.last_name,
  l.email,
  l.phone,
  a.body as message,
  l.created_at
FROM leads l
LEFT JOIN activities a ON a.related_entity_id = l.id 
  AND a.related_entity_type = 'App\\Models\\Lead'
  AND a.subject = 'Contact Form Submission'
WHERE l.reference = 'online_form'
ORDER BY l.created_at DESC
LIMIT 20;
```

**3. Webhook Health Dashboard**
```sql
SELECT * FROM webhook_events ORDER BY created_at DESC LIMIT 50;
```

---

## Testing Checklist

### Cal.com Integration
- [ ] Controllers uploaded to server
- [ ] Routes updated
- [ ] Composer autoload regenerated
- [ ] Webhook URL configured in Cal.com
- [ ] Test booking created through Cal.com
- [ ] Lead created in database with `reference = 'level_check'`
- [ ] Booking record created
- [ ] Activity logged
- [ ] `webhook_events` shows status = 'processed'

### Contact Form 7 Integration
- [ ] Controllers uploaded to server
- [ ] Routes updated
- [ ] WordPress webhook plugin installed OR custom code added
- [ ] Webhook URL configured
- [ ] Test form submitted
- [ ] Lead created with `reference = 'online_form'`
- [ ] Form message saved as activity
- [ ] `webhook_events` shows status = 'processed'

---

## Troubleshooting

### Webhook not working

1. **Check endpoint is accessible:**
   ```bash
   curl -I https://mixtreelangdb.nl/webhooks/calcom
   ```
   Should return 405 (Method Not Allowed) - this is correct for GET requests

2. **Check Laravel logs:**
   ```bash
   tail -f /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/storage/logs/lumen.log
   ```

3. **Check webhook_events table:**
   ```sql
   SELECT * FROM webhook_events WHERE status = 'failed' ORDER BY created_at DESC;
   ```

4. **Test manually with curl** (see test commands above)

### Lead not created

1. Check `webhook_events.error_message` for details
2. Verify required fields present (name/email)
3. Check field names match expected values

### Duplicate leads

- Expected behavior: Email is unique identifier
- Same email = lead updated, not duplicated
- Different lead sources will both update same lead if email matches

---

## Security Notes

- **No Authentication**: Webhook endpoints are intentionally unprotected
- **Idempotency**: Uses `external_id` to prevent duplicate processing
- **Input Validation**: All inputs validated before database insertion
- **SQL Injection Protection**: Uses Eloquent ORM with parameter binding
- **Rate Limiting**: Consider adding in production (optional)

---

## Next Steps

1. **Deploy**: Upload files and test endpoints
2. **Configure**: Set up webhooks in Cal.com and WordPress
3. **Test**: Make real bookings/submissions
4. **Monitor**: Check `webhook_events` table regularly
5. **Retool**: Add booking management and form submission views
6. **Train Team**: Show how to view and manage incoming leads

---

## Support

For issues:
1. Check `webhook_events` table for error messages
2. Review Laravel logs: `storage/logs/lumen.log`
3. Test with curl commands provided above
4. Verify external service (Cal.com/WordPress) configuration
