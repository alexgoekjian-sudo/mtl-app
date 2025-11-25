<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Payment;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MollieWebhookController extends BaseController
{
    /**
     * Handle Mollie payment webhook
     */
    public function handle(Request $request)
    {
        // Mollie sends payment ID in the request
        $paymentId = $request->input('id');
        
        if (!$paymentId) {
            Log::warning('Mollie webhook received without payment ID');
            return response()->json(['error' => 'Missing payment ID'], 400);
        }

        try {
            // Find the payment in our database
            $payment = Payment::where('external_reference', $paymentId)->first();
            
            if (!$payment) {
                Log::warning("Mollie webhook: Payment not found", ['payment_id' => $paymentId]);
                return response()->json(['error' => 'Payment not found'], 404);
            }

            // Get payment status from Mollie
            // NOTE: In production, you should verify the payment status by calling Mollie API
            // For now, we'll trust the webhook and check our payment status
            
            if ($payment->status === 'completed') {
                $this->handlePaidPayment($payment);
            } elseif ($payment->status === 'failed') {
                $this->handleFailedPayment($payment);
            }

            Log::info('Mollie webhook processed successfully', [
                'payment_id' => $paymentId,
                'status' => $payment->status
            ]);

            return response()->json(['status' => 'processed']);
            
        } catch (\Exception $e) {
            Log::error('Mollie webhook processing error', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle a successful payment
     */
    protected function handlePaidPayment(Payment $payment)
    {
        // Get student from invoice
        if (!$payment->invoice || !$payment->invoice->student_id) {
            Log::warning('Payment has no associated student', ['payment_id' => $payment->id]);
            return;
        }

        $studentId = $payment->invoice->student_id;
        
        // Auto-activate all pending enrollments for this student
        $pendingEnrollments = Enrollment::where('student_id', $studentId)
            ->where('status', 'pending')
            ->get();
        
        foreach ($pendingEnrollments as $enrollment) {
            $enrollment->activate('Payment confirmed via Mollie');
            
            Log::info('Enrollment auto-activated via payment', [
                'enrollment_id' => $enrollment->id,
                'payment_id' => $payment->id,
                'student_id' => $studentId
            ]);
        }

        // Log activity on student
        $student = \App\Models\Student::find($studentId);
        if ($student) {
            $student->addActivity(
                'payment',
                'Payment Received',
                "Payment of €{$payment->amount} received via Mollie (ID: {$payment->external_reference})"
            );
        }
    }

    /**
     * Handle a failed payment
     */
    protected function handleFailedPayment(Payment $payment)
    {
        // Get student from invoice
        if (!$payment->invoice || !$payment->invoice->student_id) {
            Log::warning('Payment has no associated student', ['payment_id' => $payment->id]);
            return;
        }

        $studentId = $payment->invoice->student_id;
        
        Log::warning('Payment failed or canceled', [
            'payment_id' => $payment->id,
            'student_id' => $studentId,
            'status' => $payment->status
        ]);

        // Log activity
        $student = \App\Models\Student::find($studentId);
        if ($student) {
            $student->addActivity(
                'payment',
                'Payment Failed',
                "Payment of €{$payment->amount} failed or was canceled (ID: {$payment->external_reference})"
            );
        }

        // TODO: Send notification to admin about failed payment
        // TODO: Send reminder email to student
    }
}
