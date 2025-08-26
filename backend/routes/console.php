<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Custom artisan commands for the Employee Portal

Artisan::command('portal:seed-demo', function () {
    $this->info('Seeding demo data for Employee Portal...');
    
    // Run seeders
    $this->call('db:seed', ['--class' => 'DemoDataSeeder']);
    
    $this->info('Demo data seeded successfully!');
})->purpose('Seed demo data for development/testing');

Artisan::command('portal:cleanup', function () {
    $this->info('Cleaning up expired data...');
    
    // Clean up expired OTP codes
    $expiredOtps = \App\Models\AuthOtp::where('expires_at', '<', now())->delete();
    $this->info("Deleted {$expiredOtps} expired OTP codes");
    
    // Clean up old audit logs (older than 1 year)
    $oldLogs = \Spatie\Activitylog\Models\Activity::where('created_at', '<', now()->subYear())->delete();
    $this->info("Deleted {$oldLogs} old audit logs");
    
    // Clean up old notifications (older than 6 months)
    $oldNotifications = \App\Models\Notification::where('created_at', '<', now()->subMonths(6))->delete();
    $this->info("Deleted {$oldNotifications} old notifications");
    
    $this->info('Cleanup completed successfully!');
})->purpose('Clean up expired and old data');

Artisan::command('portal:stats', function () {
    $this->info('Employee Portal Statistics:');
    $this->info('========================');
    
    $stats = [
        'Total Users' => \App\Models\AppUser::count(),
        'Customers' => \App\Models\AppUser::where('user_type', 'Customer')->count(),
        'Agencies' => \App\Models\AppUser::where('user_type', 'Agency')->count(),
        'Internal Users' => \App\Models\AppUser::where('user_type', 'Internal')->count(),
        'Total Workers' => \App\Models\Worker::count(),
        'Available Workers' => \App\Models\Worker::where('status', 'Ready')->count(),
        'Reserved Workers' => \App\Models\Worker::whereIn('status', ['ReservedAwaitingContract', 'ReservedAwaitingPayment'])->count(),
        'Active Contracts' => \App\Models\Contract::where('status', 'Active')->count(),
        'Total Requests' => \App\Models\RecruitmentRequest::count(),
        'Open Requests' => \App\Models\RecruitmentRequest::where('status', 'Open')->count(),
        'Total Proposals' => \App\Models\SupplierProposal::count(),
        'Pending Proposals' => \App\Models\SupplierProposal::where('status', 'Submitted')->count(),
    ];
    
    foreach ($stats as $label => $count) {
        $this->line("{$label}: {$count}");
    }
    
    $this->info('========================');
})->purpose('Display portal statistics');

Artisan::command('portal:test-otp {phone}', function ($phone) {
    $this->info("Testing OTP for phone: {$phone}");
    
    try {
        // Generate test OTP
        $otp = \App\Services\OtpService::generateOtp($phone);
        
        $this->info("Test OTP generated: {$otp}");
        $this->info("OTP expires at: " . now()->addMinutes(config('auth.otp.expiry', 5))->format('Y-m-d H:i:s'));
        
    } catch (\Exception $e) {
        $this->error("Error generating OTP: " . $e->getMessage());
    }
})->purpose('Generate test OTP for development');

Artisan::command('portal:sync-erp', function () {
    $this->info('Syncing with ERP system...');
    
    try {
        $syncService = new \App\Services\ERPSyncService();
        $result = $syncService->syncAll();
        
        $this->info("ERP sync completed successfully!");
        $this->info("Contracts synced: {$result['contracts']}");
        $this->info("Invoices synced: {$result['invoices']}");
        $this->info("Payments synced: {$result['payments']}");
        
    } catch (\Exception $e) {
        $this->error("ERP sync failed: " . $e->getMessage());
    }
})->purpose('Sync data with ERP system');

Artisan::command('portal:backup-db', function () {
    $this->info('Creating database backup...');
    
    try {
        $backupService = new \App\Services\BackupService();
        $backupPath = $backupService->createBackup();
        
        $this->info("Database backup created successfully!");
        $this->info("Backup path: {$backupPath}");
        
    } catch (\Exception $e) {
        $this->error("Database backup failed: " . $e->getMessage());
    }
})->purpose('Create database backup');

Artisan::command('portal:health-check', function () {
    $this->info('Performing health check...');
    
    $checks = [
        'Database Connection' => function() {
            try {
                \DB::connection()->getPdo();
                return ['status' => 'OK', 'message' => 'Connected'];
            } catch (\Exception $e) {
                return ['status' => 'ERROR', 'message' => $e->getMessage()];
            }
        },
        'Redis Connection' => function() {
            try {
                \Redis::connection()->ping();
                return ['status' => 'OK', 'message' => 'Connected'];
            } catch (\Exception $e) {
                return ['status' => 'ERROR', 'message' => $e->getMessage()];
            }
        },
        'Storage Writable' => function() {
            try {
                $testFile = storage_path('test.txt');
                file_put_contents($testFile, 'test');
                unlink($testFile);
                return ['status' => 'OK', 'message' => 'Writable'];
            } catch (\Exception $e) {
                return ['status' => 'ERROR', 'message' => $e->getMessage()];
            }
        },
        'Queue Connection' => function() {
            try {
                \Queue::connection()->getPdo();
                return ['status' => 'OK', 'message' => 'Connected'];
            } catch (\Exception $e) {
                return ['status' => 'ERROR', 'message' => $e->getMessage()];
            }
        }
    ];
    
    $allOk = true;
    
    foreach ($checks as $checkName => $checkFunction) {
        $result = $checkFunction();
        $status = $result['status'];
        $message = $result['message'];
        
        if ($status === 'OK') {
            $this->info("✓ {$checkName}: {$message}");
        } else {
            $this->error("✗ {$checkName}: {$message}");
            $allOk = false;
        }
    }
    
    if ($allOk) {
        $this->info('All health checks passed!');
    } else {
        $this->error('Some health checks failed!');
        exit(1);
    }
})->purpose('Perform system health check');
