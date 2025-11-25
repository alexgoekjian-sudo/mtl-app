<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Lead;
use App\Models\Booking;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalComWebhookController extends BaseController
{
    /**
     * Handle Cal.com webhook for bookings (created, cancelled, rescheduled)
     */
    public function handle(Request $request)
    {
        // Log everything for debugging
        Log::info('Cal.com webhook - RAW REQUEST', [
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'raw_content' => $request->getContent(),
            'all_data' => $request->all()
        ]);

        $payload = $request->all();

        // Verify webhook signature if secret is configured
        $signatureValid = $this->verifySignature($request);
        Log::info('Cal.com signature check', [
            'valid' => $signatureValid,
            'secret_configured' => !empty(env('CALCOM_WEBHOOK_SECRET')),
            'signature_header' => $request->header('X-Cal-Signature-256')
        ]);

        if (!$signatureValid) {
            Log::warning('Cal.com webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Extract booking ID and event type
        $triggerEvent = $payload['triggerEvent'] ?? 'BOOKING_CREATED';
        
        // Cal.com nests data in 'payload' object
        $bookingData = $payload['payload'] ?? [];
        $bookingId = $bookingData['uid'] ?? $bookingData['bookingId'] ?? $payload['uid'] ?? $payload['id'] ?? null;
        
        if (!$bookingId) {
            Log::warning('Cal.com webhook missing booking ID', ['payload_structure' => array_keys($payload)]);
            return response()->json(['error' => 'Missing booking ID'], 400);
        }

        try {
            // Check if already processed (idempotency)
            $existingEvent = WebhookEvent::where('provider', 'cal.com')
                ->where('external_id', $bookingId)
                ->where('event_type', $triggerEvent)
                ->first();

            if ($existingEvent && $existingEvent->status === 'processed') {
                Log::info('Cal.com webhook already processed', ['booking_id' => $bookingId, 'event' => $triggerEvent]);
                return response()->json(['status' => 'already_processed']);
            }

            // Store webhook event for tracking
            $webhookEvent = WebhookEvent::firstOrCreate(
                [
                    'provider' => 'cal.com',
                    'external_id' => $bookingId,
                    'event_type' => $triggerEvent
                ],
                [
                    'payload' => $payload,
                    'status' => 'pending'
                ]
            );

            // Route to appropriate handler based on event type
            switch ($triggerEvent) {
                case 'BOOKING_CREATED':
                    $this->processBooking($payload, $webhookEvent);
                    break;
                
                case 'BOOKING_CANCELLED':
                    $this->processCancellation($payload, $webhookEvent);
                    break;
                
                case 'BOOKING_RESCHEDULED':
                    $this->processReschedule($payload, $webhookEvent);
                    break;
                
                default:
                    Log::warning('Unknown Cal.com event type', ['event' => $triggerEvent]);
                    return response()->json(['status' => 'ignored', 'reason' => 'Unknown event type']);
            }

            // Mark as processed
            $webhookEvent->markProcessed();

            Log::info('Cal.com webhook processed successfully', ['booking_id' => $bookingId, 'event' => $triggerEvent]);
            
            return response()->json(['status' => 'processed']);

        } catch (\Exception $e) {
            Log::error('Cal.com webhook processing error', [
                'booking_id' => $bookingId,
                'event' => $triggerEvent,
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
     * Process Cal.com booking and create lead + booking record
     */
    protected function processBooking($payload, $webhookEvent)
    {
        // Cal.com wraps everything in a 'payload' object
        $data = $payload['payload'] ?? $payload;
        
        // Extract attendee information from responses or attendees array
        $responses = $data['responses'] ?? [];
        $attendees = $data['attendees'][0] ?? [];
        
        // Try to get name from multiple possible locations
        $nameData = $responses['name']['value'] ?? $attendees['name'] ?? null;
        
        // Parse name (could be object with firstName/lastName or string)
        if (is_array($nameData)) {
            $firstName = $nameData['firstName'] ?? null;
            $lastName = $nameData['lastName'] ?? null;
        } else {
            $nameParts = $this->parseName($nameData);
            $firstName = $nameParts['first_name'];
            $lastName = $nameParts['last_name'];
        }
        
        // Get contact info
        $email = $responses['email']['value'] ?? $attendees['email'] ?? $data['organizer']['email'] ?? null;
        $phone = $responses['attendeePhoneNumber']['value'] ?? 
                 $attendees['phoneNumber'] ?? 
                 $responses['PhoneNew']['value'] ?? 
                 null;
        
        $notes = $responses['notes']['value'] ?? $data['additionalNotes'] ?? null;
        
        if (!$firstName || !$email) {
            throw new \Exception('Missing required fields: name or email');
        }

        // Create or update lead
        $lead = Lead::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'reference' => 'level_check',
                'source_detail' => 'website_direct',
                'activity_notes' => $notes ? "Booking notes: {$notes}" : null
            ]
        );

        // Create booking record
        $booking = Booking::updateOrCreate(
            ['external_booking_id' => $data['uid'] ?? $data['bookingId']],
            [
                'lead_id' => $lead->id,
                'booking_provider' => 'cal.com',
                'booking_type' => 'level_check',
                'scheduled_at' => $data['startTime'] ?? null,
                'status' => $this->mapCalComStatus($data['status'] ?? 'ACCEPTED'),
                'webhook_payload' => $payload
            ]
        );

        // Log activity on lead
        $lead->addActivity(
            'level_check',
            'Level Check Booked',
            "Scheduled for " . ($booking->scheduled_at ? date('Y-m-d H:i', strtotime($booking->scheduled_at)) : 'TBD') .
            ($notes ? "\nNotes: {$notes}" : '')
        );

        Log::info('Cal.com booking processed', [
            'lead_id' => $lead->id,
            'booking_id' => $booking->id,
            'email' => $email
        ]);
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
            'last_name' => $parts[1] ?? null
        ];
    }

    /**
     * Map Cal.com status to our booking status
     */
    protected function mapCalComStatus($calComStatus)
    {
        $statusMap = [
            'ACCEPTED' => 'scheduled',
            'PENDING' => 'scheduled',
            'CANCELLED' => 'cancelled',
            'REJECTED' => 'cancelled'
        ];

        return $statusMap[$calComStatus] ?? 'scheduled';
    }

    /**
     * Process booking cancellation
     */
    protected function processCancellation($payload, $webhookEvent)
    {
        $data = $payload['payload'] ?? $payload;
        $externalBookingId = $data['uid'] ?? $data['bookingId'] ?? $payload['uid'];
        
        $booking = Booking::where('external_booking_id', $externalBookingId)->first();
        
        if (!$booking) {
            throw new \Exception("Booking not found for cancellation: {$externalBookingId}");
        }

        // Update booking status
        $booking->update([
            'status' => 'cancelled',
            'webhook_payload' => $payload
        ]);

        // Log activity on lead
        $booking->lead->addActivity(
            'level_check',
            'Level Check Cancelled',
            "Booking cancelled" . 
            (isset($data['cancellationReason']) ? ": {$data['cancellationReason']}" : '')
        );

        Log::info('Cal.com booking cancelled', [
            'booking_id' => $booking->id,
            'lead_id' => $booking->lead_id
        ]);
    }

    /**
     * Process booking reschedule
     */
    protected function processReschedule($payload, $webhookEvent)
    {
        $data = $payload['payload'] ?? $payload;
        $externalBookingId = $data['uid'] ?? $data['bookingId'] ?? $payload['uid'];
        
        $booking = Booking::where('external_booking_id', $externalBookingId)->first();
        
        if (!$booking) {
            throw new \Exception("Booking not found for reschedule: {$externalBookingId}");
        }

        $oldDate = $booking->scheduled_at;
        $newDate = $data['startTime'] ?? null;

        // Update booking with new time
        $booking->update([
            'scheduled_at' => $newDate,
            'status' => 'scheduled',
            'webhook_payload' => $payload
        ]);

        // Log activity on lead
        $booking->lead->addActivity(
            'level_check',
            'Level Check Rescheduled',
            "Rescheduled from " . ($oldDate ? $oldDate->format('Y-m-d H:i') : 'TBD') .
            " to " . ($newDate ? date('Y-m-d H:i', strtotime($newDate)) : 'TBD')
        );

        Log::info('Cal.com booking rescheduled', [
            'booking_id' => $booking->id,
            'lead_id' => $booking->lead_id,
            'old_date' => $oldDate,
            'new_date' => $newDate
        ]);
    }

    /**
     * Verify Cal.com webhook signature
     */
    protected function verifySignature(Request $request)
    {
        $secret = env('CALCOM_WEBHOOK_SECRET');
        
        // If no secret configured, skip verification
        if (!$secret) {
            return true;
        }

        $signature = $request->header('X-Cal-Signature-256');
        
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
