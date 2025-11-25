<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Enrollment;
use App\Models\CourseOffering;
use Carbon\Carbon;

class UpdateEnrollmentStatuses extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'enrollments:update-statuses';

    /**
     * The console command description.
     */
    protected $description = 'Auto-update enrollment statuses based on course dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating enrollment statuses...');

        $updated = 0;

        // Find active enrollments where the course has ended
        $enrollmentsToComplete = Enrollment::where('status', 'active')
            ->whereHas('courseOffering', function($query) {
                $query->where('end_date', '<', Carbon::now());
            })
            ->get();

        foreach ($enrollmentsToComplete as $enrollment) {
            $enrollment->update(['status' => 'completed']);
            
            $enrollment->activities()->create([
                'activity_type' => 'enrollment',
                'subject' => 'Enrollment Auto-Completed',
                'body' => 'Course ended on ' . $enrollment->courseOffering->end_date->format('Y-m-d')
            ]);

            $updated++;
            $this->line("Completed enrollment #{$enrollment->id} for {$enrollment->student->first_name} {$enrollment->student->last_name}");
        }

        // Find registered enrollments where the course has started
        $enrollmentsToActivate = Enrollment::where('status', 'registered')
            ->whereHas('courseOffering', function($query) {
                $query->where('start_date', '<=', Carbon::now());
            })
            ->get();

        foreach ($enrollmentsToActivate as $enrollment) {
            $enrollment->update(['status' => 'active']);
            
            $enrollment->activities()->create([
                'activity_type' => 'enrollment',
                'subject' => 'Enrollment Auto-Activated',
                'body' => 'Course started on ' . $enrollment->courseOffering->start_date->format('Y-m-d')
            ]);

            $updated++;
            $this->line("Activated enrollment #{$enrollment->id} for {$enrollment->student->first_name} {$enrollment->student->last_name}");
        }

        $this->info("Successfully updated {$updated} enrollments.");
        return 0;
    }
}
