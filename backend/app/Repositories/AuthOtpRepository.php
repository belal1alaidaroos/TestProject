<?php

namespace App\Repositories;

use App\Models\AuthOtp;
use Illuminate\Database\Eloquent\Collection;

class AuthOtpRepository
{
    public function create(array $data): AuthOtp
    {
        return AuthOtp::create($data);
    }

    public function findByPhone(string $phone): ?AuthOtp
    {
        return AuthOtp::where('phone', $phone)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function getLatestByPhone(string $phone): ?AuthOtp
    {
        return AuthOtp::where('phone', $phone)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function getRecentByPhone(string $phone, int $minutes = 5): Collection
    {
        return AuthOtp::where('phone', $phone)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->get();
    }

    public function markAsUsed(string $id): bool
    {
        $otp = AuthOtp::find($id);
        if (!$otp) {
            return false;
        }
        
        return $otp->update(['is_used' => true]);
    }

    public function incrementAttempts(string $id): bool
    {
        $otp = AuthOtp::find($id);
        if (!$otp) {
            return false;
        }
        
        return $otp->increment('attempts');
    }

    public function deleteExpired(): int
    {
        return AuthOtp::where('expires_at', '<', now())->delete();
    }
}