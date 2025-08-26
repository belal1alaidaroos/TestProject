<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'internal');
        $this->config = config('services.sms', []);
    }

    /**
     * Send OTP SMS
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        try {
            $message = "Your verification code is: {$otp}. Valid for 10 minutes.";
            
            return $this->sendSms($phone, $message);
        } catch (\Exception $e) {
            Log::error('Failed to send OTP SMS', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send notification SMS
     */
    public function sendNotification(string $phone, string $message): bool
    {
        try {
            return $this->sendSms($phone, $message);
        } catch (\Exception $e) {
            Log::error('Failed to send notification SMS', [
                'phone' => $phone,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send SMS using configured provider
     */
    protected function sendSms(string $phone, string $message): bool
    {
        switch ($this->provider) {
            case 'internal':
                return $this->sendInternalSms($phone, $message);
            
            case 'external':
                return $this->sendExternalSms($phone, $message);
            
            default:
                Log::warning('Unknown SMS provider', ['provider' => $this->provider]);
                return false;
        }
    }

    /**
     * Internal SMS provider (stub for development)
     */
    protected function sendInternalSms(string $phone, string $message): bool
    {
        // In development/testing, just log the SMS
        Log::info('Internal SMS sent', [
            'phone' => $phone,
            'message' => $message,
            'provider' => 'internal',
        ]);

        // Simulate success
        return true;
    }

    /**
     * External SMS provider
     */
    protected function sendExternalSms(string $phone, string $message): bool
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ])
                ->post($this->config['endpoint'], [
                    'to' => $phone,
                    'message' => $message,
                    'from' => $this->config['sender_id'] ?? 'SMS',
                ]);

            if ($response->successful()) {
                Log::info('External SMS sent successfully', [
                    'phone' => $phone,
                    'response' => $response->json(),
                ]);
                
                return true;
            } else {
                Log::error('External SMS failed', [
                    'phone' => $phone,
                    'response' => $response->body(),
                    'status' => $response->status(),
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('External SMS exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Validate phone number format
     */
    public function validatePhone(string $phone): bool
    {
        // Basic phone validation - can be enhanced based on requirements
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Check if it's a valid international format
        return preg_match('/^\+[1-9]\d{1,14}$/', $phone) || 
               preg_match('/^[1-9]\d{9,14}$/', $phone);
    }

    /**
     * Format phone number to international format
     */
    public function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If it doesn't start with +, assume it's a local number
        if (!str_starts_with($phone, '+')) {
            // Add country code if not present (default to +966 for Saudi Arabia)
            $countryCode = $this->config['default_country_code'] ?? '+966';
            $phone = $countryCode . $phone;
        }
        
        return $phone;
    }

    /**
     * Get SMS delivery status (if supported by provider)
     */
    public function getDeliveryStatus(string $messageId): ?array
    {
        if ($this->provider !== 'external') {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ])
                ->get($this->config['status_endpoint'] . '/' . $messageId);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get SMS delivery status', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}