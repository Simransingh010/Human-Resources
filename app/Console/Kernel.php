<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register your custom Artisan commands here.
     */
    protected $commands = [
        \App\Console\Commands\EsslMonthlySync::class,
        \App\Console\Commands\GeocodePunchDetails::class,
        // Notification processor
        \App\Console\Commands\ProcessNotificationQueue::class,
        // Birthday email sender
        \App\Console\Commands\SendBirthdayEmails::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // run every 5 minutes, no overlap
        $schedule->command('punches:geocode')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Process notification queue every 5 minutes without overlap
        $schedule->command('notifications:process --limit=100')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Send birthday emails daily at 9 AM
        $schedule->command('birthdays:send-emails')
            ->dailyAt('11:15')
            ->withoutOverlapping()
            ->runInBackground();

        // ESSL monthly device logs sync: run every 10 minutes without overlap
        $schedule->command('attendance:essl-monthly-sync --firm=29 --limit=10000')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
