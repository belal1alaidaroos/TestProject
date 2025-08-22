<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS service providers (internal or external)
    |
    */
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'internal'),
        'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '+966'),
        
        // External SMS provider configuration
        'api_key' => env('SMS_API_KEY'),
        'endpoint' => env('SMS_ENDPOINT'),
        'status_endpoint' => env('SMS_STATUS_ENDPOINT'),
        'sender_id' => env('SMS_SENDER_ID', 'SMS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ERP Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ERP integration (SAP B1, external, or disabled)
    |
    */
    'erp' => [
        'enabled' => env('ERP_ENABLED', false),
        'provider' => env('ERP_PROVIDER', 'disabled'),
        'api_key' => env('ERP_API_KEY'),
        'endpoint' => env('ERP_ENDPOINT'),
        'timeout' => env('ERP_TIMEOUT', 60),
    ],

];