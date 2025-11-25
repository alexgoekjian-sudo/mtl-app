# Cal.com Integration Guide

This guide explains how to automatically create leads and booking records when someone books a level check through Cal.com.

---

## Overview

When someone books a level check on Cal.com:
1. **Lead Created**: Contact information saved as a lead
2. **Booking Record**: Booking details stored with Cal.com data
3. **Activity Logged**: Booking event recorded on lead timeline
4. **Auto-Assignment**: Ready for teacher assignment

---

## Setup Instructions

### Step 1: Configure Webhook in Cal.com

1. **Log into Cal.com** → Go to Settings
2. **Navigate to**: Developer → Webhooks
3. **Click**: Add Webhook
4. **Configure**:
   - **Subscriber URL**: `https://mixtreelangdb.nl/webhooks/calcom`
   - **Trigger Events**: Select `BOOKING_CREATED`
   - **Optionally add**: `BOOKING_RESCHEDULED`, `BOOKING_CANCELLED`
   - **Save**

### Step 2: Test the Integration

1. **Create a test booking** on your Cal.com booking page
2. **Check the database**:
   ```sql
   SELECT * FROM leads WHERE reference = 'level_check' ORDER BY created_at DESC LIMIT 5;
   SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5;
   ```
3. **Verify**:
   - Lead created with correct name, email, phone
   - Booking record created with scheduled time
   - Activity logged on lead

---

## Cal.com Webhook Payload

Cal.com sends this data structure (sample):

```json
{
  "triggerEvent": "BOOKING_CREATED",
  "createdAt": "2025-11-21T14:00:00.000Z",
  "payload": {
    "type": "BOOKING_CREATED",
    "title": "Level Check - 30 Min",
    "description": "English level assessment",
    "startTime": "2025-11-25T10:00:00.000Z",
    "endTime": "2025-11-25T10:30:00.000Z",
    "organizer": {
      "id": 123,
      "name": "Your School",
      "email": "info@mixtreelangdb.nl",
      "timeZone": "Europe/Amsterdam"
    },
    "responses": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+31612345678",
      "notes": "I want to improve my English for work"
    },
    "location": "Google Meet",
    "status": "ACCEPTED",
    "smsReminderNumber": null
  },
  "uid": "abc123xyz456",
  "id": "abc123xyz456"
}
```

---

## What Happens When Booking is Made

### 1. Webhook Received
- Cal.com sends POST request to `/webhooks/calcom`
- Payload validated and logged

### 2. Idempotency Check
- System checks if booking already processed (using `uid`)
- Prevents duplicate leads/bookings

### 3. Lead Created/Updated
```php
Lead {
  first_name: "John",
  last_name: "Doe",
  email: "john@example.com",
  phone: "+31612345678",
  reference: "level_check",
  source_detail: "website_direct",
  activity_notes: "Booking notes: I want to improve my English for work"
}
```

### 4. Booking Record Created
```php
Booking {
  lead_id: 123,
  booking_provider: "cal.com",
  external_booking_id: "abc123xyz456",
  booking_type: "level_check",
  scheduled_at: "2025-11-25 10:00:00",
  status: "scheduled",
  webhook_payload: {...} // Full Cal.com data
}
```

### 5. Activity Logged
```
Activity Type: level_check
Subject: Level Check Booked
Body: Scheduled for 2025-11-25 10:00
      Notes: I want to improve my English for work
```

---

## Field Mapping

| Cal.com Field | MTL App Field | Notes |
|---------------|---------------|-------|
| `responses.name` | `first_name` + `last_name` | Auto-split on space |
| `responses.email` | `email` | Unique identifier |
| `responses.phone` | `phone` | |
| `responses.notes` | `activity_notes` | Saved as activity |
| `payload.startTime` | `scheduled_at` | Booking datetime |
| `payload.status` | `status` | Mapped to our statuses |
| `uid` | `external_booking_id` | Cal.com booking ID |

---

## Booking Status Mapping

| Cal.com Status | MTL App Status |
|----------------|----------------|
| `ACCEPTED` | `scheduled` |
| `PENDING` | `scheduled` |
| `CANCELLED` | `cancelled` |
| `REJECTED` | `cancelled` |

---

## Handling Rescheduled/Cancelled Bookings

To handle booking changes, add these events in Cal.com webhook settings:

### BOOKING_RESCHEDULED
When customer reschedules, Cal.com sends updated `startTime`. The webhook will:
1. Update existing booking record
2. Log activity: "Level Check Rescheduled"

### BOOKING_CANCELLED
When booking is cancelled, Cal.com sends `CANCELLED` status. The webhook will:
1. Update booking status to `cancelled`
2. Log activity: "Level Check Cancelled"

**Current Implementation**: The webhook handles `BOOKING_CREATED` events. To add reschedule/cancellation support, update the controller to handle these event types.

---

## Monitoring & Troubleshooting

### Check Webhook Events

**View Recent Cal.com Webhooks:**
```sql
SELECT * FROM webhook_events 
WHERE provider = 'cal.com' 
ORDER BY created_at DESC 
LIMIT 10;
```

**Check Processing Status:**
```sql
SELECT external_id, event_type, status, error_message, created_at 
FROM webhook_events 
WHERE provider = 'cal.com' AND status = 'failed'
ORDER BY created_at DESC;
```

### View Created Bookings

**Recent Level Check Bookings:**
```sql
SELECT 
  b.id,
  b.scheduled_at,
  b.status,
  l.first_name,
  l.last_name,
  l.email,
  l.phone
FROM bookings b
JOIN leads l ON b.lead_id = l.id
WHERE b.booking_type = 'level_check'
ORDER BY b.created_at DESC
LIMIT 10;
```

**Upcoming Level Checks:**
```sql
SELECT 
  b.scheduled_at,
  l.first_name,
  l.last_name,
  l.email,
  l.phone
FROM bookings b
JOIN leads l ON b.lead_id = l.id
WHERE b.booking_type = 'level_check'
  AND b.status = 'scheduled'
  AND b.scheduled_at >= NOW()
ORDER BY b.scheduled_at ASC;
```

### Check Laravel Logs

```bash
tail -f /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/storage/logs/lumen.log | grep -i cal
```

### Test Webhook Manually

```bash
curl -X POST https://mixtreelangdb.nl/webhooks/calcom \
  -H "Content-Type: application/json" \
  -d '{
    "triggerEvent": "BOOKING_CREATED",
    "uid": "test-booking-123",
    "payload": {
      "startTime": "2025-11-25T10:00:00.000Z",
      "endTime": "2025-11-25T10:30:00.000Z",
      "status": "ACCEPTED",
      "responses": {
        "name": "Test User",
        "email": "test@example.com",
        "phone": "+31612345678",
        "notes": "Test booking"
      }
    }
  }'
```

Expected response:
```json
{"status": "processed"}
```

---

## Common Issues

### Issue: Webhook not receiving data
**Check**:
1. Webhook URL in Cal.com settings is correct
2. Cal.com can reach your server (not blocked by firewall)
3. SSL certificate is valid

**Test**: Use Cal.com's "Test Webhook" button

### Issue: Name not splitting correctly
**Problem**: Names like "Mary Jane Smith" split incorrectly

**Solution**: Controller splits on first space only:
- First name: "Mary"
- Last name: "Jane Smith"

If you need different logic, update the `parseName()` method.

### Issue: Duplicate leads created
**Check**: Email address is unique identifier

**Expected Behavior**: 
- Same email booking twice = lead updated, new booking created
- Different email = new lead + new booking

### Issue: Missing phone number
**Note**: Phone is optional in Cal.com. If attendee doesn't provide phone, lead created without phone number.

---

## Retool Integration

### Create Booking Management View

**Query for Retool:**
```sql
SELECT 
  b.id,
  b.scheduled_at,
  b.status,
  l.first_name || ' ' || l.last_name as attendee_name,
  l.email,
  l.phone,
  b.created_at as booked_at
FROM bookings b
JOIN leads l ON b.lead_id = l.id
WHERE b.booking_type = 'level_check'
  AND b.status = 'scheduled'
  AND b.scheduled_at >= CURDATE()
ORDER BY b.scheduled_at ASC;
```

### Add Teacher Assignment

Update booking with assigned teacher:
```sql
UPDATE bookings 
SET assigned_teacher_id = {{ teacherDropdown.value }},
    status = 'completed'
WHERE id = {{ bookingTable.selectedRow.id }};
```

---

## Database Views for Cal.com Bookings

### Upcoming Level Checks View

```sql
CREATE VIEW upcoming_level_checks AS
SELECT 
  b.id as booking_id,
  b.scheduled_at,
  b.status,
  l.id as lead_id,
  CONCAT(l.first_name, ' ', l.last_name) as attendee_name,
  l.email,
  l.phone,
  b.assigned_teacher_id,
  u.name as assigned_teacher,
  DATEDIFF(b.scheduled_at, NOW()) as days_until
FROM bookings b
JOIN leads l ON b.lead_id = l.id
LEFT JOIN users u ON b.assigned_teacher_id = u.id
WHERE b.booking_type = 'level_check'
  AND b.status = 'scheduled'
  AND b.scheduled_at >= NOW()
ORDER BY b.scheduled_at ASC;
```

Run this SQL to create the view, then use in Retool:
```sql
SELECT * FROM upcoming_level_checks;
```

---

## Webhook Security (Optional)

Cal.com supports webhook secrets for verification. To add security:

### Step 1: Generate Secret in Cal.com

When creating webhook in Cal.com, note the "Secret" value.

### Step 2: Add to .env

```env
CALCOM_WEBHOOK_SECRET=your_secret_here
```

### Step 3: Update Controller

Add signature verification in `CalComWebhookController`:

```php
protected function verifySignature(Request $request)
{
    $secret = env('CALCOM_WEBHOOK_SECRET');
    $signature = $request->header('X-Cal-Signature-256');
    
    $payload = $request->getContent();
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    
    if (!hash_equals($expectedSignature, $signature)) {
        throw new \Exception('Invalid webhook signature');
    }
}
```

Call this in the `handle()` method before processing.

---

## Advanced: Custom Booking Types

If you have multiple Cal.com event types (not just level checks):

### Step 1: Identify Event Type

Cal.com sends `title` in payload. Map to booking types:

```php
protected function determineBookingType($payload)
{
    $title = strtolower($payload['payload']['title'] ?? '');
    
    if (strpos($title, 'level check') !== false) {
        return 'level_check';
    } elseif (strpos($title, 'consultation') !== false) {
        return 'consultation';
    } elseif (strpos($title, 'trial') !== false) {
        return 'trial_class';
    }
    
    return 'level_check'; // default
}
```

### Step 2: Update Database

Ensure `bookings.booking_type` ENUM includes new types.

---

## Testing Checklist

- [ ] Webhook URL configured in Cal.com
- [ ] Trigger event `BOOKING_CREATED` enabled
- [ ] Test booking made through Cal.com
- [ ] Lead created with correct data
- [ ] Booking record created
- [ ] Activity logged on lead
- [ ] `webhook_events` shows successful processing
- [ ] Check Laravel logs for errors
- [ ] Test with duplicate email (should update lead)
- [ ] Test with missing phone (should handle gracefully)

---

## Deployment Steps

1. **Upload Files**:
   - `app/Http/Controllers/CalComWebhookController.php`
   - Updated `routes/web.php`

2. **Test Endpoint**:
   ```bash
   curl -X POST https://mixtreelangdb.nl/webhooks/calcom \
     -H "Content-Type: application/json" \
     -d '{"uid":"test-123","payload":{"responses":{"name":"Test User","email":"test@test.com"},"startTime":"2025-12-01T10:00:00Z","status":"ACCEPTED"}}'
   ```

3. **Configure Cal.com**:
   - Add webhook URL
   - Enable `BOOKING_CREATED` event
   - Test with real booking

4. **Monitor**:
   - Check `webhook_events` table
   - Verify leads being created
   - Review Laravel logs

---

## Support

If you encounter issues:
1. Check `webhook_events` table: `SELECT * FROM webhook_events WHERE provider = 'cal.com' AND status = 'failed';`
2. Review Laravel logs for error details
3. Test webhook manually with curl command above
4. Verify Cal.com webhook configuration
