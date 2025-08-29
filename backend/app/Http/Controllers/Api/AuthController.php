<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\EmailLoginRequest;
use App\Http\Requests\Auth\CustomerSignupRequest;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->requestOtp($request->phone);
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'expires_in' => $result['expires_in']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('OTP request failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->verifyOtp($request->phone, $request->code);
            
            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                    'token_type' => $result['token_type']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('OTP verification failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function emailLogin(EmailLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->emailLogin(
                $request->email,
                $request->password,
                $request->portal_type
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                    'token_type' => $result['token_type']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Email login failed', [
                'email' => $request->email,
                'portal_type' => $request->portal_type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function customerSignup(CustomerSignupRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->customerSignup($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Customer registration successful',
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                    'token_type' => $result['token_type']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Customer signup failed', [
                'email' => $request->email,
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->socialLogin(
                $request->provider,
                $request->provider_user_id,
                $request->email,
                $request->name,
                $request->portal_type
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Social login successful',
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                    'token_type' => $result['token_type']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Social login failed', [
                'provider' => $request->provider,
                'email' => $request->email,
                'portal_type' => $request->portal_type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function checkPortalAccess(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->checkPortalAccess($request->portal_type);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Portal access check failed', [
                'portal_type' => $request->portal_type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['customer', 'agency', 'roles']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user
            ]
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load(['customer', 'agency', 'roles']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user
            ]
        ]);
    }
}