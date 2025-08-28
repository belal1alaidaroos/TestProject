<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Worker;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WorkerController extends Controller
{
    /**
     * Display a listing of available workers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Worker::with(['profession', 'nationality', 'city', 'district'])
                ->where('status', 'available')
                ->where('is_active', true);

            // Apply filters
            if ($request->has('profession_id')) {
                $query->where('profession_id', $request->profession_id);
            }

            if ($request->has('city_id')) {
                $query->where('city_id', $request->city_id);
            }

            if ($request->has('district_id')) {
                $query->where('district_id', $request->district_id);
            }

            if ($request->has('nationality_id')) {
                $query->where('nationality_id', $request->nationality_id);
            }

            if ($request->has('package_id')) {
                $query->where('package_id', $request->package_id);
            }

            // Search by name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('full_name', 'like', "%{$search}%");
                });
            }

            // Sort by
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $workers = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 200,
                'message' => 'Workers retrieved successfully',
                'data' => $workers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving workers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified worker
     */
    public function show(Worker $worker): JsonResponse
    {
        try {
            if (!$worker->is_active || $worker->status !== 'available') {
                return response()->json([
                    'status' => 404,
                    'message' => 'Worker not available'
                ], 404);
            }

            $worker->load(['profession', 'nationality', 'city', 'district', 'package', 'documents']);

            return response()->json([
                'status' => 200,
                'message' => 'Worker details retrieved successfully',
                'data' => $worker
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving worker details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reserve a worker
     */
    public function reserve(Request $request, Worker $worker): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date|after:today',
                'end_date' => 'required|date|after:start_date',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if worker is available
            if (!$worker->is_active || $worker->status !== 'available') {
                return response()->json([
                    'status' => 400,
                    'message' => 'Worker is not available for reservation'
                ], 400);
            }

            // Check if worker is already reserved for the requested dates
            $existingReservation = Reservation::where('worker_id', $worker->id)
                ->where('status', '!=', 'cancelled')
                ->where(function($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                          ->orWhere(function($q) use ($request) {
                              $q->where('start_date', '<=', $request->start_date)
                                ->where('end_date', '>=', $request->end_date);
                          });
                })
                ->first();

            if ($existingReservation) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Worker is already reserved for the requested dates'
                ], 400);
            }

            // Create reservation
            $reservation = Reservation::create([
                'customer_id' => Auth::id(),
                'worker_id' => $worker->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'notes' => $request->notes,
                'status' => 'pending',
                'total_amount' => $worker->package->price ?? 0
            ]);

            // Update worker status
            $worker->update(['status' => 'reserved']);

            $reservation->load(['worker', 'customer']);

            return response()->json([
                'status' => 201,
                'message' => 'Worker reserved successfully',
                'data' => $reservation
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error reserving worker: ' . $e->getMessage()
            ], 500);
        }
    }
}