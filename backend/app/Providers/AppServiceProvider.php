<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register JWT Auth
        $this->app->register(\Tymon\JWTAuth\Providers\LaravelServiceProvider::class);
        
        // Register Spatie Permission
        $this->app->register(\Spatie\Permission\PermissionServiceProvider::class);
        
        // Register Activity Log
        $this->app->register(\Spatie\Activitylog\ActivitylogServiceProvider::class);
        
        // Register Laravel Excel
        $this->app->register(\Maatwebsite\Excel\ExcelServiceProvider::class);
        
        // Register CORS
        $this->app->register(\Barryvdh\Cors\ServiceProvider::class);
        
        // Register Telescope in development
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for SQL Server
        Schema::defaultStringLength(191);
        
        // Custom validation rules
        $this->registerCustomValidators();
        
        // Database query logging in development
        if ($this->app->environment('local')) {
            $this->enableQueryLogging();
        }
        
        // Set timezone
        date_default_timezone_set(config('app.timezone'));
    }
    
    /**
     * Register custom validation rules
     */
    private function registerCustomValidators(): void
    {
        // Phone number validation for Saudi Arabia
        Validator::extend('saudi_phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^(05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/', $value);
        }, 'The :attribute must be a valid Saudi phone number.');
        
        // UUID validation
        Validator::extend('uuid', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
        }, 'The :attribute must be a valid UUID.');
        
        // National ID validation for Saudi Arabia
        Validator::extend('saudi_national_id', function ($attribute, $value, $parameters, $validator) {
            if (!preg_match('/^[12]\d{9}$/', $value)) {
                return false;
            }
            
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $digit = (int) $value[$i];
                if ($i % 2 == 0) {
                    $sum += $digit;
                } else {
                    $doubled = $digit * 2;
                    $sum += ($doubled > 9) ? ($doubled - 9) : $doubled;
                }
            }
            
            $checkDigit = (10 - ($sum % 10)) % 10;
            return $checkDigit == (int) $value[9];
        }, 'The :attribute must be a valid Saudi National ID.');
    }
    
    /**
     * Enable database query logging in development
     */
    private function enableQueryLogging(): void
    {
        DB::listen(function ($query) {
            $sql = $query->sql;
            $bindings = $query->bindings;
            $time = $query->time;
            
            // Log slow queries
            if ($time > 100) {
                Log::warning("Slow query detected: {$sql}", [
                    'bindings' => $bindings,
                    'time' => $time,
                    'connection' => $query->connection->getName(),
                ]);
            }
            
            // Log all queries in development
            if (config('app.debug')) {
                Log::info("Query executed: {$sql}", [
                    'bindings' => $bindings,
                    'time' => $time,
                ]);
            }
        });
    }
}