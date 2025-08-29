<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\AuthOtp;
use App\Models\Customer;
use App\Repositories\AppUserRepository;
use App\Repositories\AuthOtpRepository;
use App\Repositories\CustomerRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private AppUserRepository $userRepository,
        private AuthOtpRepository $otpRepository,
        private CustomerRepository $customerRepository
    ) {}

    public function requestOtp(string $phone): array
    {
        $devMode = config('system.development.otp_bypass_enabled', false) 
            || app()->environment('local') 
            || config('services.sms.provider', 'internal') === 'internal';

        // Rate limiting check
        $recentOtps = $this->otpRepository->getRecentByPhone($phone, 1);
        if ($recentOtps->count() > 0) {
            $lastOtp = $recentOtps->first();
            $timeDiff = now()->diffInSeconds($lastOtp->created_at);

            $cooldownSeconds = (int) config('system.otp.cooldown_seconds', 60);
            if ($timeDiff < $cooldownSeconds) {
                throw new \Exception('Please wait before requesting another OTP');
            }
        }

        // Generate OTP
        $otpLength = (int) config('system.otp.length', 4);
        $otp = str_pad((string) random_int(0, pow(10, $otpLength) - 1), $otpLength, '0', STR_PAD_LEFT);
        $otpHash = Hash::make($otp);

        // Expiry seconds (cast to integer)
        $expirySeconds = (int) config('system.otp.expiry', 300);

        // Store OTP
        $this->otpRepository->create([
            'phone'       => $phone,
            'code_hash'   => $otpHash,
            'expires_at'  => now()->addSeconds($expirySeconds),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);

        // Send SMS (stub implementation)
        $this->sendSms($phone, "Your OTP is: {$otp}");

        Log::info('OTP requested', ['phone' => $phone]);

        $response = [
            'message'    => 'OTP sent successfully',
            'expires_in' => $expirySeconds,
        ];

        // In development/internal mode, include OTP in response to ease testing
        if ($devMode) {
            $response['dev_otp'] = $otp;
        }

        return $response;
    }

    public function verifyOtp(string $phone, string $code): array
    {
        $devMode = config('system.development.otp_bypass_enabled', false) 
            || app()->environment('local') 
            || config('services.sms.provider', 'internal') === 'internal';
        $bypassCode = config('system.development.otp_bypass_code', '0000');

        // If dev bypass is enabled and code matches, skip OTP validation
        if ($devMode && $code === $bypassCode) {
            $user = $this->userRepository->findByPhone($phone);
            if (!$user) {
                $user = $this->createCustomerUser($phone);
            }

            if (!$user->phone_verified_at) {
                $this->userRepository->update($user->id, [
                    'phone_verified_at' => now()
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info('OTP verification bypassed in dev mode', [
                'phone' => $phone,
                'user_id' => $user->id
            ]);

            return [
                'user' => $user->load(['customer', 'roles']),
                'token' => $token,
                'token_type' => 'Bearer',
            ];
        }

        $otp = $this->otpRepository->getLatestByPhone($phone);
        
        if (!$otp) {
            throw new \Exception('Invalid OTP');
        }

        if ($otp->is_used) {
            throw new \Exception('OTP already used');
        }

        if ($otp->expires_at < now()) {
            throw new \Exception('OTP has expired');
        }

        if ($otp->attempts >= config('system.otp.max_attempts', 3)) {
            throw new \Exception('Maximum attempts exceeded');
        }

        // Verify OTP
        if (!Hash::check($code, $otp->code_hash)) {
            $this->otpRepository->incrementAttempts($otp->id);
            throw new \Exception('Invalid OTP code');
        }

        // Mark OTP as used
        $this->otpRepository->markAsUsed($otp->id);

        // Find or create user
        $user = $this->userRepository->findByPhone($phone);
        
        if (!$user) {
            $user = $this->createCustomerUser($phone);
        }

        // Update phone verification
        if (!$user->phone_verified_at) {
            $this->userRepository->update($user->id, [
                'phone_verified_at' => now()
            ]);
        }

        // Generate token
        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('OTP verified successfully', [
            'phone' => $phone,
            'user_id' => $user->id
        ]);

        return [
            'user' => $user->load(['customer', 'roles']),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function emailLogin(string $email, string $password, string $portalType): array
    {
        // Find user by email
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            throw new \Exception('Invalid credentials');
        }

        // Check if user is active
        if (!$user->is_active) {
            throw new \Exception('Account is deactivated');
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        // Check portal access
        if (!$this->canAccessPortal($user, $portalType)) {
            throw new \Exception("Access denied to {$portalType} portal");
        }

        // Generate token
        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('Email login successful', [
            'email' => $email,
            'user_id' => $user->id,
            'portal_type' => $portalType
        ]);

        return [
            'user' => $user->load(['customer', 'agency', 'roles']),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function customerSignup(array $data): array
    {
        // Check if user already exists
        if ($this->userRepository->findByEmail($data['email'])) {
            throw new \Exception('Email already registered');
        }

        if ($this->userRepository->findByPhone($data['phone'])) {
            throw new \Exception('Mobile number already registered');
        }

        // Create user
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'user_type' => 'Customer',
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Create customer record
        $this->customerRepository->create([
            'app_user_id' => $user->id,
            'company_name_en' => $data['company_name_en'],
            'company_name_ar' => $data['company_name_ar'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'commercial_license' => $data['commercial_license'] ?? null,
            'contact_person' => $data['contact_person'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'status' => 'active',
        ]);

        // Generate token
        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('Customer signup successful', [
            'email' => $data['email'],
            'phone' => $data['phone'],
            'user_id' => $user->id
        ]);

        return [
            'user' => $user->load(['customer', 'roles']),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function socialLogin(string $provider, string $providerUserId, string $email, string $name, string $portalType, ?string $phone = null): array
    {
        // Check if user already exists by email
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            // Create new user for social login
            $userData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'user_type' => $portalType,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(16)), // Random password for social users
            ];

            if ($phone) {
                $userData['phone_verified_at'] = now();
            }

            $user = $this->userRepository->create($userData);

            // If it's a customer, create customer record
            if ($portalType === 'Customer' && $phone) {
                $this->customerRepository->create([
                    'app_user_id' => $user->id,
                    'company_name_en' => $name,
                    'contact_person' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'status' => 'active',
                ]);
            }
        }

        // Check portal access
        if (!$this->canAccessPortal($user, $portalType)) {
            throw new \Exception("Access denied to {$portalType} portal");
        }

        // Generate token
        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('Social login successful', [
            'provider' => $provider,
            'email' => $email,
            'user_id' => $user->id,
            'portal_type' => $portalType
        ]);

        return [
            'user' => $user->load(['customer', 'agency', 'roles']),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function checkPortalAccess(string $portalType): array
    {
        $response = [
            'portal_type' => $portalType,
            'requires_signup' => false,
            'available_methods' => [],
            'mobile_required' => false,
        ];

        switch ($portalType) {
            case 'Customer':
                $response['available_methods'] = ['email_password', 'social_media', 'mobile_otp'];
                $response['mobile_required'] = true;
                $response['social_providers'] = ['google', 'facebook', 'apple', 'linkedin'];
                break;
                
            case 'Agency':
            case 'Admin':
            case 'Internal':
                $response['available_methods'] = ['email_password'];
                $response['mobile_required'] = false;
                break;
                
            default:
                throw new \Exception('Invalid portal type');
        }

        return $response;
    }

    private function canAccessPortal(AppUser $user, string $portalType): bool
    {
        switch ($portalType) {
            case 'Customer':
                return $user->user_type === 'Customer';
            case 'Agency':
                return $user->user_type === 'Agency';
            case 'Admin':
            case 'Internal':
                return in_array($user->user_type, ['Internal', 'Admin']);
            default:
                return false;
        }
    }

    private function createCustomerUser(string $phone): AppUser
    {
        $user = $this->userRepository->create([
            'name' => 'Customer ' . substr($phone, -4),
            'email' => $phone . '@temp.local',
            'phone' => $phone,
            'user_type' => 'Customer',
            'is_active' => true,
        ]);

        // Create customer record
        $this->customerRepository->create([
            'app_user_id' => $user->id,
            'contact_person' => $user->name,
            'phone' => $phone,
            'email' => $user->email,
            'status' => 'active',
        ]);

        return $user;
    }

    private function sendSms(string $phone, string $message): void
    {
        // TODO: Replace with actual SMS service integration
        Log::info('SMS sent (stub)', [
            'phone' => $phone,
            'message' => $message
        ]);
    }

    public function logout(AppUser $user): void
    {
        $user->tokens()->delete();
        
        Log::info('User logged out', [
            'user_id' => $user->id
        ]);
    }
}