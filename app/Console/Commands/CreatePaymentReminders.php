<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Task;
use Carbon\Carbon;

class CreatePaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'enrollments:create-payment-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Create tasks for pending enrollments without payment after 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for pending enrollments without payment...');

        $remindersCreated = 0;
        $warningsDays = 7; // Days after enrollment before creating reminder

        // Find pending enrollments older than 7 days
        $pendingEnrollments = Enrollment::where('status', 'pending')
            ->where('enrolled_at', '<', Carbon::now()->subDays($warningsDays))
            ->with(['student', 'courseOffering'])
            ->get();

        foreach ($pendingEnrollments as $enrollment) {
            // Check if student has any paid payments for this course
            $hasPaidPayment = Payment::where('student_id', $enrollment->student_id)
                ->where('status', 'paid')
                ->where('created_at', '>=', $enrollment->enrolled_at)
                ->exists();

            if ($hasPaidPayment) {
                // Payment exists but enrollment wasn't activated - log warning
                $this->warn("Enrollment #{$enrollment->id} has payment but is still pending - may need manual review");
                continue;
            }

            // Check if reminder task already exists
            $existingTask = Task::where('student_id', $enrollment->student_id)
                ->where('title', 'LIKE', '%Payment Reminder%')
                ->where('title', 'LIKE', "%Enrollment #{$enrollment->id}%")
                ->where('status', '!=', 'completed')
                ->first();

            if ($existingTask) {
                // Task already exists, skip
                continue;
            }

            // Create a reminder task
            $task = Task::create([
                'student_id' => $enrollment->student_id,
                'title' => "Payment Reminder - Enrollment #{$enrollment->id}",
                'description' => sprintf(
                    "Student %s %s enrolled in %s on %s but has not paid yet. Follow up on payment status.",
                    $enrollment->student->first_name,
                    $enrollment->student->last_name,
                    $enrollment->courseOffering->course_name ?? 'Course',
                    $enrollment->enrolled_at->format('Y-m-d')
                ),
                'status' => 'pending',
                'priority' => 'high',
                'due_date' => Carbon::now()->addDays(3)
            ]);

            // Log activity
            $enrollment->activities()->create([
                'activity_type' => 'other',
                'subject' => 'Payment Reminder Created',
                'body' => "Automatic payment reminder task created after {$warningsDays} days without payment"
            ]);

            $remindersCreated++;
            $this->line("Created reminder for enrollment #{$enrollment->id} - {$enrollment->student->first_name} {$enrollment->student->last_name}");
        }

        $this->info("Successfully created {$remindersCreated} payment reminder tasks.");
        return 0;
    }
}
