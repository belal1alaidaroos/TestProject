<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ReserveWorkerRequest;
use App\Http\Requests\Customer\CreateContractRequest;
use App\Services\WorkerService;
use App\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerPortalController extends Controller
{
    public function __construct(
        private WorkerService $workerService,
        private ContractService $contractService
    ) {}

    public function getWorkers(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['nationality_id', 'profession_id', 'min_age', 'max_age', 'experience_years']);
            $perPage = $request->get('per_page', 15);
            
            $workers = $this->workerService->getAvailableWorkers($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $workers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get workers', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workers'
            ], 500);
        }
    }

    public function reserveWorker(ReserveWorkerRequest $request): JsonResponse
    {
        try {
            $customerId = $request->user()->customer->id;
            $reservation = $this->workerService->reserveWorker($request->worker_id, $customerId);
            
            return response()->json([
                'success' => true,
                'message' => 'Worker reserved successfully',
                'data' => [
                    'reservation' => $reservation,
                    'expires_at' => $reservation->expires_at,
                    'ttl_seconds' => now()->diffInSeconds($reservation->expires_at)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reserve worker', [
                'worker_id' => $request->worker_id,
                'customer_id' => $request->user()->customer->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function createContract(CreateContractRequest $request): JsonResponse
    {
        try {
            $contract = $this->workerService->createContractFromReservation(
                $request->reservation_id,
                $request->validated()
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Contract created successfully',
                'data' => [
                    'contract' => $contract,
                    'expires_at' => $contract->reservation->expires_at,
                    'ttl_seconds' => now()->diffInSeconds($contract->reservation->expires_at)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create contract', [
                'reservation_id' => $request->reservation_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getContractInvoice(string $contractId): JsonResponse
    {
        try {
            $invoice = $this->contractService->getInvoice($contractId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'invoice' => $invoice
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get contract invoice', [
                'contract_id' => $contractId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice'
            ], 500);
        }
    }

    public function preparePayment(string $contractId): JsonResponse
    {
        try {
            $result = $this->contractService->preparePayment($contractId);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment prepared successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to prepare payment', [
                'contract_id' => $contractId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function confirmPayment(string $contractId): JsonResponse
    {
        try {
            $contract = $this->workerService->activateContract($contractId);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed and contract activated',
                'data' => [
                    'contract' => $contract
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to confirm payment', [
                'contract_id' => $contractId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function cancelReservation(string $reservationId): JsonResponse
    {
        try {
            $this->workerService->cancelReservation($reservationId);
            
            return response()->json([
                'success' => true,
                'message' => 'Reservation cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel reservation', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function cancelContract(string $contractId): JsonResponse
    {
        try {
            $this->contractService->cancelContract($contractId);
            
            return response()->json([
                'success' => true,
                'message' => 'Contract cancelled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel contract', [
                'contract_id' => $contractId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}