<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\AutoPublishActivities::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Auto-publish command
        $schedule->command('activities:auto-publish')->everyMinute();

        // Close expired activities
        $schedule->command('activities:close-expired')->everyMinute();

        // Weekly guardian attendance digest
        // Runs every minute, command decides WHEN to send
        $schedule->command('attendance:send-guardian-emails')
            ->everyMinute()
            ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
