<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ReleaseExpiredReservations;
use App\Jobs\CancelExpiredAwaitingPayment;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Release expired reservations every 5 minutes
        $schedule->job(new ReleaseExpiredReservations())
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->onOneServer();

        // Cancel expired awaiting payment contracts every 10 minutes
        $schedule->job(new CancelExpiredAwaitingPayment())
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->onOneServer();

        // Daily cleanup tasks
        $schedule->call(function () {
            // Clean up expired OTP codes (older than 1 hour)
            \DB::table('auth_otps')
                ->where('created_at', '<', now()->subHour())
                ->delete();

            // Clean up old audit logs (older than 1 year)
            \DB::table('audit_logs')
                ->where('created_at', '<', now()->subYear())
                ->delete();

            // Clean up old notifications (older than 6 months)
            \DB::table('notifications')
                ->where('created_at', '<', now()->subMonths(6))
                ->delete();
        })->daily();

        // Weekly reports
        $schedule->call(function () {
            // Generate weekly statistics report
            $this->generateWeeklyReport();
        })->weekly();

        // Monthly maintenance
        $schedule->call(function () {
            // Optimize database tables
            \DB::statement('OPTIMIZE TABLE workers, contracts, payments, audit_logs');
            
            // Generate monthly statistics
            $this->generateMonthlyReport();
        })->monthly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Generate weekly report
     */
    private function generateWeeklyReport(): void
    {
        $startDate = now()->startOfWeek();
        $endDate = now()->endOfWeek();

        $stats = [
            'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
            'new_contracts' => \App\Models\Contract::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_revenue' => \App\Models\Contract::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'Active')
                ->sum('total_amount'),
            'new_customers' => \App\Models\AppUser::where('user_type', 'Customer')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'new_proposals' => \App\Models\SupplierProposal::whereBetween('created_at', [$startDate, $endDate])->count(),
        ];

        // Log the weekly report
        \Log::info('Weekly Report Generated', $stats);

        // TODO: Send report to admin email
    }

    /**
     * Generate monthly report
     */
    private function generateMonthlyReport(): void
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $stats = [
            'period' => $startDate->format('Y-m'),
            'new_contracts' => \App\Models\Contract::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_revenue' => \App\Models\Contract::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'Active')
                ->sum('total_amount'),
            'new_customers' => \App\Models\AppUser::where('user_type', 'Customer')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'new_agencies' => \App\Models\AppUser::where('user_type', 'Agency')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'new_proposals' => \App\Models\SupplierProposal::whereBetween('created_at', [$startDate, $endDate])->count(),
            'approved_proposals' => \App\Models\SupplierProposal::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'Approved')
                ->count(),
        ];

        // Log the monthly report
        \Log::info('Monthly Report Generated', $stats);

        // TODO: Send report to admin email
    }
}