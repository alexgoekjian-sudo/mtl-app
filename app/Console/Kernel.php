<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\UpdateEnrollmentStatuses::class,
        Commands\CreatePaymentReminders::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Update enrollment statuses daily at 2 AM
        $schedule->command('enrollments:update-statuses')
            ->dailyAt('02:00')
            ->withoutOverlapping();

        // Create payment reminders daily at 9 AM
        $schedule->command('enrollments:create-payment-reminders')
            ->dailyAt('09:00')
            ->withoutOverlapping();
    }
}
