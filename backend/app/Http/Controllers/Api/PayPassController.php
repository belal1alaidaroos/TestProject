<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePayPassSessionRequest;
use App\Http\Requests\Payment\VerifyPayPassOtpRequest;
use App\Services\PayPassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPassController extends Controller
{
    public function __construct(
        private PayPassService $payPassService
    ) {}

    /**
     * Create a new paypass payment session
     */
    public function createSession(CreatePayPassSessionRequest $request): JsonResponse
    {
        try {
            $result = $this->payPassService->createPaymentSession(
                $request->contract_id,
                $request->phone
            );
            
            return response()->json([
                'success' => true,
                'message' => 'PayPass session created successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create PayPass session', [
                'contract_id' => $request->contract_id,
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Verify paypass OTP and complete payment
     */
    public function verifyOtp(VerifyPayPassOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->payPassService->verifyPayPassOtp(
                $request->session_id,
                $request->otp
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to verify PayPass OTP', [
                'session_id' => $request->session_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get payment session status
     */
    public function getSessionStatus(string $sessionId): JsonResponse
    {
        try {
            $result = $this->payPassService->getPaymentSessionStatus($sessionId);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get PayPass session status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel payment session
     */
    public function cancelSession(string $sessionId): JsonResponse
    {
        try {
            $result = $this->payPassService->cancelPaymentSession($sessionId);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment session cancelled successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel PayPass session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}