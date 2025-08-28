<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Reservation;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    /**
     * Display a listing of customer's reservations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Reservation::with(['worker', 'worker.profession', 'worker.nationality'])
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

            $reservations = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 200,
                'message' => 'Reservations retrieved successfully',
                'data' => $reservations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving reservations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified reservation
     */
    public function show(Reservation $reservation): JsonResponse
    {
        try {
            // Ensure customer can only view their own reservations
            if ($reservation->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to reservation'
                ], 403);
            }

            $reservation->load([
                'worker',
                'worker.profession',
                'worker.nationality',
                'worker.city',
                'worker.district',
                'worker.package',
                'worker.documents',
                'contract'
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Reservation details retrieved successfully',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving reservation details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a contract from reservation
     */
    public function createContract(Request $request, Reservation $reservation): JsonResponse
    {
        try {
            // Ensure customer can only create contracts for their own reservations
            if ($reservation->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to reservation'
                ], 403);
            }

            // Check if reservation is in valid state
            if ($reservation->status !== 'confirmed') {
                return response()->json([
                    'status' => 400,
                    'message' => 'Reservation must be confirmed to create a contract'
                ], 400);
            }

            // Check if contract already exists
            if ($reservation->contract) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Contract already exists for this reservation'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'terms_conditions' => 'required|boolean|accepted',
                'additional_notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create contract
            $contract = Contract::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'worker_id' => $reservation->worker_id,
                'start_date' => $reservation->start_date,
                'end_date' => $reservation->end_date,
                'total_amount' => $reservation->total_amount,
                'status' => 'pending',
                'terms_conditions' => $request->terms_conditions,
                'additional_notes' => $request->additional_notes,
                'contract_date' => now()
            ]);

            // Update reservation status
            $reservation->update(['status' => 'contracted']);

            $contract->load(['reservation', 'worker', 'customer']);

            return response()->json([
                'status' => 201,
                'message' => 'Contract created successfully',
                'data' => $contract
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a reservation
     */
    public function cancel(Request $request, Reservation $reservation): JsonResponse
    {
        try {
            // Ensure customer can only cancel their own reservations
            if ($reservation->customer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to reservation'
                ], 403);
            }

            // Check if reservation can be cancelled
            if (!in_array($reservation->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Reservation cannot be cancelled in its current state'
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

            // Cancel reservation
            $reservation->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_at' => now()
            ]);

            // Update worker status back to available
            $reservation->worker->update(['status' => 'available']);

            return response()->json([
                'status' => 200,
                'message' => 'Reservation cancelled successfully',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error cancelling reservation: ' . $e->getMessage()
            ], 500);
        }
    }
}