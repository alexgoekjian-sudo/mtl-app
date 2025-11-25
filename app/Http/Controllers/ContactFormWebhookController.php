<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Lead;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactFormWebhookController extends BaseController
{
    /**
     * Handle Contact Form 7 webhook submissions
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        
        Log::info('Contact Form webhook received', ['payload' => $payload]);

        // Generate unique ID for idempotency (use timestamp + email)
        $uniqueId = md5(($payload['email'] ?? '') . ($payload['_wpcf7_unit_tag'] ?? time()));

        try {
            // Check if already processed (idempotency)
            $existingEvent = WebhookEvent::where('provider', 'contact_form_7')
                ->where('external_id', $uniqueId)
                ->first();

            if ($existingEvent && $existingEvent->status === 'processed') {
                Log::info('Contact Form webhook already processed', ['id' => $uniqueId]);
                return response()->json(['status' => 'already_processed']);
            }

            // Store webhook event for tracking
            $webhookEvent = WebhookEvent::firstOrCreate(
                [
                    'provider' => 'contact_form_7',
                    'external_id' => $uniqueId
                ],
                [
                    'event_type' => 'form_submission',
                    'payload' => $payload,
                    'status' => 'pending'
                ]
            );

            // Process the form submission
            $this->processFormSubmission($payload, $webhookEvent);

            // Mark as processed
            $webhookEvent->markProcessed();

            Log::info('Contact Form webhook processed successfully', ['id' => $uniqueId]);
            
            return response()->json(['status' => 'processed']);

        } catch (\Exception $e) {
            Log::error('Contact Form webhook processing error', [
                'id' => $uniqueId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($webhookEvent)) {
                $webhookEvent->markFailed($e->getMessage());
            }

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Process Contact Form 7 submission and create lead
     */
    protected function processFormSubmission($payload, $webhookEvent)
    {
        // Extract form fields (CF7 sends field names as keys)
        $firstName = $this->extractField($payload, ['first_name', 'first-name', 'firstname', 'your-name']);
        $lastName = $this->extractField($payload, ['last_name', 'last-name', 'lastname', 'surname']);
        $email = $this->extractField($payload, ['email', 'your-email', 'e-mail']);
        $phone = $this->extractField($payload, ['phone', 'tel', 'telephone', 'your-phone', 'phone-number']);
        $message = $this->extractField($payload, ['message', 'your-message', 'question', 'comment', 'comments']);

        // If name comes as single field, split it
        if (!$firstName && !$lastName) {
            $fullName = $this->extractField($payload, ['name', 'your-name', 'full-name']);
            if ($fullName) {
                $nameParts = $this->parseName($fullName);
                $firstName = $nameParts['first_name'];
                $lastName = $nameParts['last_name'];
            }
        }

        // Validate required fields
        if (!$firstName || !$email) {
            throw new \Exception('Missing required fields: first_name or email');
        }

        // Determine source detail from form or referrer
        $sourceDetail = $this->determineSourceDetail($payload);

        // Create or update lead
        $lead = Lead::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName ?? '',
                'phone' => $phone,
                'reference' => 'online_form',
                'source_detail' => $sourceDetail,
                'activity_notes' => $message ? "Contact form message: {$message}" : null
            ]
        );

        // Log activity on lead
        $activityBody = "Contact form submission received";
        if ($message) {
            $activityBody .= "\n\nMessage: {$message}";
        }
        
        $lead->addActivity(
            'note',
            'Contact Form Submission',
            $activityBody
        );

        Log::info('Contact form submission processed', [
            'lead_id' => $lead->id,
            'email' => $email
        ]);
    }

    /**
     * Extract field from payload (tries multiple possible field names)
     */
    protected function extractField($payload, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            if (isset($payload[$name]) && !empty($payload[$name])) {
                return is_array($payload[$name]) ? $payload[$name][0] : $payload[$name];
            }
        }
        return null;
    }

    /**
     * Parse full name into first and last name
     */
    protected function parseName($fullName)
    {
        if (!$fullName) {
            return ['first_name' => null, 'last_name' => null];
        }

        $parts = explode(' ', trim($fullName), 2);
        
        return [
            'first_name' => $parts[0] ?? null,
            'last_name' => $parts[1] ?? ''
        ];
    }

    /**
     * Determine marketing source from form data or referrer
     */
    protected function determineSourceDetail($payload)
    {
        // Check if form includes source field
        $source = $this->extractField($payload, ['source', 'utm_source', 'referrer']);
        
        if (!$source) {
            return 'website_direct';
        }

        // Map common sources
        $sourceMap = [
            'google' => 'google',
            'facebook' => 'facebook',
            'fb' => 'facebook',
            'instagram' => 'instagram',
            'ig' => 'instagram',
            'linkedin' => 'linkedin',
            'ai' => 'ai'
        ];

        $sourceLower = strtolower($source);
        
        foreach ($sourceMap as $key => $value) {
            if (strpos($sourceLower, $key) !== false) {
                return $value;
            }
        }

        return 'other';
    }
}
