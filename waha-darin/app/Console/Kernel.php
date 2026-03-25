<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Sends reminder emails 1 day before end_date.
        $schedule->command('borrow:return-reminders')->daily();

        // Sends overdue return reminders every 3 days (until return shipment number is provided).
        $schedule->command('borrow:overdue-return-reminders')->daily();

        // Expire subscriptions that passed their end date.
        // Borrowing is still blocked immediately by middleware date-check;
        // this command is mainly for status + notification.
        $schedule->command('subscriptions:expire')->weekly();

        // Keep borrow return statuses in sync.
        if (app()->environment('local')) {
            // For development: run frequently so changes are visible quickly.
            $schedule->command('borrow:update-return-statuses')->everyMinute();
        } else {
            $schedule->command('borrow:update-return-statuses')->hourly();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
