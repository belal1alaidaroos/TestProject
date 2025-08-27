<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\PaymentSession;
use App\Repositories\PaymentSessionRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayPassService
{
    public function __construct(
        private PaymentSessionRepository $paymentSessionRepository
    ) {}

    /**
     * Create a new payment session for paypass authentication
     */
    public function createPaymentSession(string $contractId, string $phone): array
    {
        // Find the contract
        $contract = Contract::findOrFail($contractId);
        
        // Create payment session
        $session = $this->paymentSessionRepository->create([
            'contract_id' => $contractId,
            'phone' => $phone,
            'session_token' => Str::random(32),
            'expires_at' => now()->addSeconds(config('system.payment.paypass.timeout', 300)),
            'status' => 'pending',
            'payment_method' => 'paypass',
        ]);

        // Generate OTP for paypass
        $otp = $this->generatePayPassOtp($phone);
        
        // Store OTP hash in session
        $this->paymentSessionRepository->update($session->id, [
            'otp_hash' => bcrypt($otp),
            'otp_attempts' => 0,
        ]);

        Log::info('PayPass payment session created', [
            'contract_id' => $contractId,
            'phone' => $phone,
            'session_id' => $session->id,
        ]);

        return [
            'session_id' => $session->id,
            'session_token' => $session->session_token,
            'expires_in' => config('system.payment.paypass.timeout', 300),
            'dev_otp' => config('system.development.payment_bypass_enabled', false) ? $otp : null,
        ];
    }

    /**
     * Verify paypass OTP and complete payment
     */
    public function verifyPayPassOtp(string $sessionId, string $otp): array
    {
        $session = $this->paymentSessionRepository->find($sessionId);
        
        if (!$session) {
            throw new \Exception('Invalid payment session');
        }

        if ($session->status !== 'pending') {
            throw new \Exception('Payment session is not pending');
        }

        if ($session->expires_at < now()) {
            throw new \Exception('Payment session has expired');
        }

        // Check for bypass code in development mode
        $devMode = config('system.development.payment_bypass_enabled', false);
        $bypassCode = config('system.development.paypass_bypass_code', '8523');
        
        if ($devMode && $otp === $bypassCode) {
            return $this->completePaymentSession($session);
        }

        // Check OTP attempts
        if ($session->otp_attempts >= config('system.payment.paypass.max_attempts', 3)) {
            throw new \Exception('Maximum OTP attempts exceeded');
        }

        // Verify OTP
        if (!password_verify($otp, $session->otp_hash)) {
            $this->paymentSessionRepository->incrementOtpAttempts($sessionId);
            throw new \Exception('Invalid OTP code');
        }

        // Complete payment session
        return $this->completePaymentSession($session);
    }

    /**
     * Complete the payment session
     */
    private function completePaymentSession(PaymentSession $session): array
    {
        // Update session status
        $this->paymentSessionRepository->update($session->id, [
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update contract status
        $contract = Contract::find($session->contract_id);
        $contract->update([
            'status' => 'Active',
            'payment_confirmed_at' => now(),
        ]);

        Log::info('PayPass payment completed', [
            'session_id' => $session->id,
            'contract_id' => $session->contract_id,
            'phone' => $session->phone,
        ]);

        return [
            'success' => true,
            'message' => 'Payment completed successfully',
            'contract_id' => $session->contract_id,
            'session_id' => $session->id,
        ];
    }

    /**
     * Generate OTP for paypass
     */
    private function generatePayPassOtp(string $phone): string
    {
        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // In development mode, you can use a fixed OTP for testing
        if (config('system.development.payment_bypass_enabled', false)) {
            $otp = config('system.development.paypass_bypass_code', '8523');
        }

        // TODO: Send SMS with OTP
        Log::info('PayPass OTP generated', [
            'phone' => $phone,
            'otp' => $otp,
        ]);

        return $otp;
    }

    /**
     * Get payment session status
     */
    public function getPaymentSessionStatus(string $sessionId): array
    {
        $session = $this->paymentSessionRepository->find($sessionId);
        
        if (!$session) {
            throw new \Exception('Payment session not found');
        }

        return [
            'session_id' => $session->id,
            'status' => $session->status,
            'expires_at' => $session->expires_at,
            'remaining_time' => max(0, now()->diffInSeconds($session->expires_at)),
        ];
    }

    /**
     * Cancel payment session
     */
    public function cancelPaymentSession(string $sessionId): array
    {
        $session = $this->paymentSessionRepository->find($sessionId);
        
        if (!$session) {
            throw new \Exception('Payment session not found');
        }

        if ($session->status !== 'pending') {
            throw new \Exception('Cannot cancel non-pending session');
        }

        $this->paymentSessionRepository->update($session->id, [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Log::info('PayPass payment session cancelled', [
            'session_id' => $sessionId,
        ]);

        return [
            'success' => true,
            'message' => 'Payment session cancelled',
        ];
    }
}