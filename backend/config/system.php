<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains system-wide configuration settings for the application
    | including OTP settings, payment configurations, and other system defaults.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for One-Time Password functionality
    |
    */
    'otp' => [
        'expiry' => env('OTP_EXPIRY_SECONDS', 300), // 5 minutes
        'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),
        'cooldown_seconds' => env('OTP_COOLDOWN_SECONDS', 60), // 1 minute
        'length' => env('OTP_LENGTH', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment processing and paypass functionality
    |
    */
    'payment' => [
        'session_timeout' => env('PAYMENT_SESSION_TIMEOUT', 600), // 10 minutes
        'methods' => [
            'card' => true,
            'bank_transfer' => true,
            'paypass' => true, // Enable paypass functionality
        ],
        'paypass' => [
            'enabled' => env('PAYPASS_ENABLED', true),
            'timeout' => env('PAYPASS_TIMEOUT', 300), // 5 minutes
            'max_attempts' => env('PAYPASS_MAX_ATTEMPTS', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for development and testing environments
    |
    */
    'development' => [
        'otp_bypass_enabled' => env('OTP_BYPASS_ENABLED', true),
        'otp_bypass_code' => env('OTP_BYPASS_CODE', '8523'),
        'payment_bypass_enabled' => env('PAYMENT_BYPASS_ENABLED', true),
        'paypass_bypass_code' => env('PAYPASS_BYPASS_CODE', '8523'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related system settings
    |
    */
    'security' => [
        'session_lifetime' => env('SESSION_LIFETIME', 120), // 2 hours
        'max_login_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('LOCKOUT_DURATION', 900), // 15 minutes
    ],

];