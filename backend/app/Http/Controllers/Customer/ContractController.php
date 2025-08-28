<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    /**
     * Display a listing of customer's contracts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Contract::with(['worker', 'worker.profession', 'reservation'])
                ->where('customer_id', Auth::id());

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('worker_id')) {
                $query->where('worker_id', $request->worker_id);
            }

            // Date range filter
            if ($request->has('start_date')) {
                $query->where('start_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('end_date', '<=', $request->end_date);
            }

            // Sort by
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $contracts = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 200,
                'message' => 'Contracts retrieved successfully',
                'data' => $contracts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving contracts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified contract
     */
    public function show(Contract $contract): JsonResponse
    {
        try {
            // Ensure customer can only view their own contracts
            if ($contract->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to contract'
                ], 403);
            }

            $contract->load([
                'worker',
                'worker.profession',
                'worker.nationality',
                'worker.city',
                'worker.district',
                'worker.package',
                'reservation',
                'invoices',
                'payments'
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Contract details retrieved successfully',
                'data' => $contract
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving contract details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoice for contract
     */
    public function getInvoice(Contract $contract): JsonResponse
    {
        try {
            // Ensure customer can only access their own contracts
            if ($contract->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to contract'
                ], 403);
            }

            $invoice = Invoice::where('contract_id', $contract->id)->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Invoice not found for this contract'
                ], 404);
            }

            $invoice->load(['contract', 'contract.worker']);

            return response()->json([
                'status' => 200,
                'message' => 'Invoice retrieved successfully',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare payment for contract
     */
    public function preparePayment(Request $request, Contract $contract): JsonResponse
    {
        try {
            // Ensure customer can only access their own contracts
            if ($contract->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to contract'
                ], 403);
            }

            // Check if contract is in valid state for payment
            if ($contract->status !== 'active') {
                return response()->json([
                    'status' => 400,
                    'message' => 'Contract must be active to prepare payment'
                ], 400);
            }

            $invoice = Invoice::where('contract_id', $contract->id)->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Invoice not found for this contract'
                ], 404);
            }

            // Check if payment is already in progress
            $existingPayment = Payment::where('contract_id', $contract->id)
                ->whereIn('status', ['pending', 'processing'])
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Payment is already in progress for this contract'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'payment_method' => 'required|in:paypass,bank_transfer,cash',
                'amount' => 'required|numeric|min:0.01|max:' . $invoice->total_amount
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create payment record
            $payment = Payment::create([
                'contract_id' => $contract->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $contract->customer_id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'payment_date' => now()
            ]);

            $payment->load(['contract', 'invoice']);

            return response()->json([
                'status' => 201,
                'message' => 'Payment prepared successfully',
                'data' => $payment
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error preparing payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm payment for contract
     */
    public function confirmPayment(Request $request, Contract $contract): JsonResponse
    {
        try {
            // Ensure customer can only access their own contracts
            if ($contract->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to contract'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|exists:payments,id',
                'transaction_id' => 'required|string|max:255',
                'payment_proof' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $payment = Payment::where('id', $request->payment_id)
                ->where('contract_id', $contract->id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Payment not found'
                ], 404);
            }

            if ($payment->status !== 'pending') {
                return response()->json([
                    'status' => 400,
                    'message' => 'Payment is not in pending status'
                ], 400);
            }

            // Update payment status
            $payment->update([
                'status' => 'processing',
                'transaction_id' => $request->transaction_id,
                'payment_proof' => $request->payment_proof,
                'processed_at' => now()
            ]);

            $payment->load(['contract', 'invoice']);

            return response()->json([
                'status' => 200,
                'message' => 'Payment confirmed successfully',
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error confirming payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel contract
     */
    public function cancel(Request $request, Contract $contract): JsonResponse
    {
        try {
            // Ensure customer can only access their own contracts
            if ($contract->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to contract'
                ], 403);
            }

            // Check if contract can be cancelled
            if (!in_array($contract->status, ['pending', 'active'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Contract cannot be cancelled in its current state'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'cancellation_reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Cancel contract
                $contract->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $request->cancellation_reason,
                    'cancelled_at' => now()
                ]);

                // Update reservation status
                if ($contract->reservation) {
                    $contract->reservation->update(['status' => 'cancelled']);
                }

                // Update worker status back to available
                $contract->worker->update(['status' => 'available']);

                // Cancel any pending payments
                Payment::where('contract_id', $contract->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now()
                    ]);

                DB::commit();

                return response()->json([
                    'status' => 200,
                    'message' => 'Contract cancelled successfully',
                    'data' => $contract
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error cancelling contract: ' . $e->getMessage()
            ], 500);
        }
    }
}