<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ReleaseExpiredReservations;
use App\Jobs\CancelExpiredAwaitingPayment;
use App\Jobs\SendContractReminders;
use App\Jobs\SyncWithERP;
use App\Jobs\GenerateReports;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Release expired reservations (every minute)
        $schedule->job(new ReleaseExpiredReservations)
                ->everyMinute()
                ->withoutOverlapping()
                ->onOneServer();
        
        // Cancel expired awaiting payment contracts (every 2 minutes)
        $schedule->job(new CancelExpiredAwaitingPayment)
                ->everyTwoMinutes()
                ->withoutOverlapping()
                ->onOneServer();
        
        // Send contract reminders (daily at 9 AM)
        $schedule->job(new SendContractReminders)
                ->dailyAt('09:00')
                ->withoutOverlapping()
                ->onOneServer();
        
        // Sync with ERP (every hour)
        $schedule->job(new SyncWithERP)
                ->hourly()
                ->withoutOverlapping()
                ->onOneServer();
        
        // Generate daily reports (daily at 6 AM)
        $schedule->job(new GenerateReports)
                ->dailyAt('06:00')
                ->withoutOverlapping()
                ->onOneServer();
        
        // Clean up old audit logs (weekly on Sunday at 2 AM)
        $schedule->command('activitylog:clean')
                ->weekly()
                ->sundays()
                ->at('02:00');
        
        // Clean up old failed jobs (daily at 3 AM)
        $schedule->command('queue:prune-failed')
                ->daily()
                ->at('03:00');
        
        // Backup database (daily at 1 AM)
        $schedule->command('backup:clean')->daily()->at('01:00');
        $schedule->command('backup:run')->daily()->at('01:30');
        
        // Health check (every 5 minutes)
        $schedule->command('health:check')
                ->everyFiveMinutes()
                ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}