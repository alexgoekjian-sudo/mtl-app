# WordPress Contact Form 7 to Database Integration Guide

This guide explains how to automatically send Contact Form 7 submissions from your WordPress site to the MTL App database as leads.

---

## Overview

When someone fills out a Contact Form 7 (CF7) on your WordPress site, the data will be automatically sent to your MTL App database and created as a Lead with:
- **Reference**: `online_form`
- **Source Detail**: Auto-detected from form or set to `website_direct`
- **Activity Log**: Contact form message saved as an activity

---

## Method 1: Using CF7 Webhook Extension (Recommended)

### Step 1: Install Plugin

Install **"Contact Form 7 Webhook"** or **"CF7 to Webhook"** plugin:

```
WordPress Admin → Plugins → Add New → Search "Contact Form 7 Webhook"
```

**Recommended Plugin**: [CF7 to Webhook by RedNao](https://wordpress.org/plugins/cf7-to-webhook/)

### Step 2: Configure Webhook in CF7 Form

1. Go to **Contact → Contact Forms**
2. Edit your contact form
3. Go to the **Additional Settings** tab or **Webhook** tab (depending on plugin)
4. Add webhook configuration:

```
webhook_url: https://mixtreelangdb.nl/webhooks/contact-form
webhook_method: POST
webhook_format: JSON
```

### Step 3: Test the Integration

1. Fill out the contact form on your website
2. Check the MTL App database:
   ```sql
   SELECT * FROM leads ORDER BY created_at DESC LIMIT 5;
   ```
3. Verify the lead was created with `reference = 'online_form'`

---

## Method 2: Using CF7 Flamingo + Custom Code (Alternative)

If you can't install a webhook plugin, use this custom WordPress code:

### Step 1: Install Flamingo (for logging)

```
WordPress Admin → Plugins → Add New → Search "Flamingo"
```

### Step 2: Add Custom Code to functions.php

Add this to your theme's `functions.php` or create a custom plugin:

```php
<?php
/**
 * Send Contact Form 7 submissions to MTL App database
 */
add_action('wpcf7_mail_sent', 'send_cf7_to_mtl_app');

function send_cf7_to_mtl_app($contact_form) {
    // Get submission data
    $submission = WPCF7_Submission::get_instance();
    
    if (!$submission) {
        return;
    }
    
    $posted_data = $submission->get_posted_data();
    
    // Extract form fields (adjust field names to match your form)
    $data = array(
        'first_name' => isset($posted_data['first-name']) ? $posted_data['first-name'] : '',
        'last_name' => isset($posted_data['last-name']) ? $posted_data['last-name'] : '',
        'email' => isset($posted_data['your-email']) ? $posted_data['your-email'] : '',
        'phone' => isset($posted_data['your-phone']) ? $posted_data['your-phone'] : '',
        'message' => isset($posted_data['your-message']) ? $posted_data['your-message'] : '',
        '_wpcf7_unit_tag' => $contact_form->id(),
        'form_title' => $contact_form->title(),
        'submitted_at' => current_time('mysql')
    );
    
    // Send to MTL App webhook
    $response = wp_remote_post('https://mixtreelangdb.nl/webhooks/contact-form', array(
        'method' => 'POST',
        'timeout' => 15,
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($data),
    ));
    
    // Log the response (optional - for debugging)
    if (is_wp_error($response)) {
        error_log('MTL App webhook error: ' . $response->get_error_message());
    } else {
        error_log('MTL App webhook success: ' . wp_remote_retrieve_response_code($response));
    }
}
```

### Step 3: Update Field Names

**Important**: Update the field names in the code above to match your actual CF7 form fields.

To find your field names:
1. Edit your contact form in WordPress
2. Look at the form tags, e.g., `[text* your-name]` → field name is `your-name`
3. Update the array keys in the code accordingly

---

## Method 3: Using Zapier/Make (No Code Required)

### Using Zapier

1. **Create a Zap**:
   - Trigger: Contact Form 7 (requires Zapier for WordPress plugin)
   - Action: Webhooks by Zapier → POST request

2. **Configure Webhook**:
   - URL: `https://mixtreelangdb.nl/webhooks/contact-form`
   - Method: POST
   - Data Format: JSON
   - Payload:
     ```json
     {
       "first_name": "{{First Name}}",
       "last_name": "{{Last Name}}",
       "email": "{{Email}}",
       "phone": "{{Phone}}",
       "message": "{{Message}}"
     }
     ```

3. **Test & Enable**

### Using Make (Integromat)

1. **Create a Scenario**:
   - Trigger: Webhook (instant) - set up a webhook to receive CF7 data
   - Module: HTTP → Make a request

2. **Configure HTTP Request**:
   - URL: `https://mixtreelangdb.nl/webhooks/contact-form`
   - Method: POST
   - Headers: `Content-Type: application/json`
   - Body: Map the fields from CF7

---

## Supported Field Names

The webhook controller automatically recognizes these field name variations:

| Data | Recognized Field Names |
|------|------------------------|
| **First Name** | `first_name`, `first-name`, `firstname`, `your-name` |
| **Last Name** | `last_name`, `last-name`, `lastname`, `surname` |
| **Email** | `email`, `your-email`, `e-mail` |
| **Phone** | `phone`, `tel`, `telephone`, `your-phone`, `phone-number` |
| **Message** | `message`, `your-message`, `question`, `comment`, `comments` |
| **Full Name** | `name`, `your-name`, `full-name` (auto-splits into first/last) |

---

## Expected Webhook Payload Format

The endpoint expects JSON with these fields:

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "message": "I would like to enroll in your B1 course"
}
```

**Minimum required**: `first_name` OR `name`, and `email`

---

## What Happens When Form is Submitted

1. **Webhook Received**: Data posted to `/webhooks/contact-form`
2. **Idempotency Check**: Prevents duplicate lead creation
3. **Lead Created/Updated**:
   - Email used as unique identifier
   - `reference` set to `online_form`
   - `source_detail` auto-detected or set to `website_direct`
4. **Activity Logged**: Form message saved as activity on lead
5. **Webhook Event Recorded**: Stored in `webhook_events` table for tracking

---

## Monitoring & Troubleshooting

### Check if Webhook is Working

**View Recent Webhook Events:**
```sql
SELECT * FROM webhook_events 
WHERE provider = 'contact_form_7' 
ORDER BY created_at DESC 
LIMIT 10;
```

**View Created Leads:**
```sql
SELECT id, first_name, last_name, email, reference, created_at 
FROM leads 
WHERE reference = 'online_form' 
ORDER BY created_at DESC 
LIMIT 10;
```

**View Lead Activities:**
```sql
SELECT a.*, l.first_name, l.last_name 
FROM activities a
JOIN leads l ON a.related_entity_id = l.id
WHERE a.related_entity_type = 'App\\Models\\Lead'
  AND a.subject = 'Contact Form Submission'
ORDER BY a.created_at DESC
LIMIT 10;
```

### Check Webhook Logs

View Laravel logs on the server:
```bash
tail -f /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/storage/logs/lumen.log
```

### Common Issues

**Issue**: Lead not created
- **Check**: Webhook event status in `webhook_events` table
- **Fix**: Look at `error_message` column for details

**Issue**: Missing fields
- **Check**: Field names in your CF7 form
- **Fix**: Update field names to match supported variations (see table above)

**Issue**: Duplicate leads
- **Note**: System uses email as unique identifier - if same email submits form twice, lead is updated, not duplicated

---

## Advanced: Auto-Detect Marketing Source

To automatically track which marketing channel brought the lead, add a hidden field to your CF7 form:

### Step 1: Add Hidden Field to CF7

Add to your Contact Form 7:
```
[hidden source id:utm_source default:get]
```

### Step 2: Use UTM Parameters in Marketing URLs

Use these URL formats in your marketing campaigns:

- Google Ads: `https://yoursite.com/contact?utm_source=google`
- Facebook: `https://yoursite.com/contact?utm_source=facebook`
- Instagram: `https://yoursite.com/contact?utm_source=instagram`
- LinkedIn: `https://yoursite.com/contact?utm_source=linkedin`

The webhook will automatically map these to the correct `source_detail` value.

---

## Testing Checklist

- [ ] Webhook URL configured in WordPress
- [ ] Test form submission from website
- [ ] Verify lead created in database
- [ ] Check `webhook_events` table for successful processing
- [ ] Verify activity logged on lead
- [ ] Test with duplicate email (should update, not create new)
- [ ] Test with missing fields (should handle gracefully)
- [ ] Check Laravel logs for any errors

---

## Security Notes

- **No Authentication Required**: The webhook endpoint is intentionally unprotected so WordPress can POST to it
- **Idempotency**: System prevents duplicate processing of the same submission
- **Rate Limiting**: Consider adding rate limiting in production (optional)
- **Data Validation**: All inputs are validated before creating leads
- **SQL Injection Protection**: Uses Eloquent ORM with parameter binding

---

## Next Steps

1. **Upload Controllers**:
   - Upload `CalComWebhookController.php` to `app/Http/Controllers/`
   - Upload `ContactFormWebhookController.php` to `app/Http/Controllers/`
   - Upload updated `routes/web.php`

2. **Configure WordPress**:
   - Choose integration method (webhook plugin recommended)
   - Set webhook URL: `https://mixtreelangdb.nl/webhooks/contact-form`
   - Test with a form submission

3. **Monitor**:
   - Check `webhook_events` table for processing status
   - Verify leads are being created with correct data
   - Review activity logs on leads

---

## Support

If you encounter issues:
1. Check `webhook_events` table for error messages
2. Review Laravel logs: `/storage/logs/lumen.log`
3. Test webhook manually with curl:
   ```bash
   curl -X POST https://mixtreelangdb.nl/webhooks/contact-form \
     -H "Content-Type: application/json" \
     -d '{"first_name":"Test","last_name":"User","email":"test@example.com","phone":"123456789","message":"Test message"}'
   ```
