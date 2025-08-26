<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'reservation_timeout_create',
                'value' => '300',
                'type' => 'integer',
                'description' => 'Reservation timeout for contract creation (seconds)',
                'is_public' => false,
            ],
            [
                'key' => 'reservation_timeout_pay',
                'value' => '600',
                'type' => 'integer',
                'description' => 'Reservation timeout for payment (seconds)',
                'is_public' => false,
            ],
            [
                'key' => 'otp_expiry',
                'value' => '300',
                'type' => 'integer',
                'description' => 'OTP expiry time (seconds)',
                'is_public' => false,
            ],
            [
                'key' => 'otp_max_attempts',
                'value' => '3',
                'type' => 'integer',
                'description' => 'Maximum OTP attempts',
                'is_public' => false,
            ],
            [
                'key' => 'otp_rate_limit',
                'value' => '3',
                'type' => 'integer',
                'description' => 'OTP rate limit per minute',
                'is_public' => false,
            ],
            [
                'key' => 'sms_provider',
                'value' => 'internal',
                'type' => 'string',
                'description' => 'SMS provider (internal/external)',
                'is_public' => false,
            ],
            [
                'key' => 'erp_provider',
                'value' => 'internal',
                'type' => 'string',
                'description' => 'ERP provider (internal/external)',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->insert(array_merge($setting, [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}