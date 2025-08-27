<?php

namespace App\Repositories;

use App\Models\PaymentSession;
use Illuminate\Database\Eloquent\Collection;

class PaymentSessionRepository
{
    /**
     * Create a new payment session
     */
    public function create(array $data): PaymentSession
    {
        return PaymentSession::create($data);
    }

    /**
     * Find a payment session by ID
     */
    public function find(string $id): ?PaymentSession
    {
        return PaymentSession::find($id);
    }

    /**
     * Find a payment session by session token
     */
    public function findByToken(string $token): ?PaymentSession
    {
        return PaymentSession::where('session_token', $token)->first();
    }

    /**
     * Find active payment sessions for a contract
     */
    public function findActiveByContract(string $contractId): Collection
    {
        return PaymentSession::where('contract_id', $contractId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();
    }

    /**
     * Update a payment session
     */
    public function update(string $id, array $data): bool
    {
        $session = $this->find($id);
        if (!$session) {
            return false;
        }

        return $session->update($data);
    }

    /**
     * Increment OTP attempts for a payment session
     */
    public function incrementOtpAttempts(string $id): bool
    {
        $session = $this->find($id);
        if (!$session) {
            return false;
        }

        return $session->increment('otp_attempts');
    }

    /**
     * Delete expired payment sessions
     */
    public function deleteExpired(): int
    {
        return PaymentSession::where('expires_at', '<=', now())
            ->where('status', 'pending')
            ->delete();
    }

    /**
     * Get payment sessions by status
     */
    public function getByStatus(string $status): Collection
    {
        return PaymentSession::where('status', $status)->get();
    }

    /**
     * Get payment sessions by payment method
     */
    public function getByPaymentMethod(string $method): Collection
    {
        return PaymentSession::where('payment_method', $method)->get();
    }

    /**
     * Get payment sessions for a specific phone number
     */
    public function getByPhone(string $phone): Collection
    {
        return PaymentSession::where('phone', $phone)->get();
    }

    /**
     * Clean up old completed sessions
     */
    public function cleanupOldSessions(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return PaymentSession::where('status', 'completed')
            ->where('completed_at', '<', $cutoffDate)
            ->delete();
    }
}