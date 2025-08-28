<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Request as WorkerRequest;
use App\Models\Proposal;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    /**
     * Display a listing of all requests
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WorkerRequest::with(['customer', 'profession', 'city', 'district', 'nationality', 'package']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

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

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            // Search by description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('requirements', 'like', "%{$search}%");
                });
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

            $requests = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 200,
                'message' => 'Requests retrieved successfully',
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving requests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified request
     */
    public function show(WorkerRequest $request): JsonResponse
    {
        try {
            $request->load([
                'customer',
                'profession',
                'nationality',
                'city',
                'district',
                'package',
                'proposals',
                'proposals.agency',
                'proposals.worker',
                'proposals.worker.profession'
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Request details retrieved successfully',
                'data' => $request
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving request details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created request
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:users,id',
                'profession_id' => 'required|exists:professions,id',
                'nationality_id' => 'required|exists:nationalities,id',
                'city_id' => 'required|exists:cities,id',
                'district_id' => 'required|exists:districts,id',
                'package_id' => 'required|exists:packages,id',
                'description' => 'required|string|max:1000',
                'requirements' => 'required|string|max:1000',
                'start_date' => 'required|date|after:today',
                'end_date' => 'required|date|after:start_date',
                'budget_min' => 'required|numeric|min:0',
                'budget_max' => 'required|numeric|min:budget_min',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $workerRequest = WorkerRequest::create($request->all());

            $workerRequest->load(['customer', 'profession', 'nationality', 'city', 'district', 'package']);

            return response()->json([
                'status' => 201,
                'message' => 'Request created successfully',
                'data' => $workerRequest
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified request
     */
    public function update(Request $request, WorkerRequest $workerRequest): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'profession_id' => 'sometimes|required|exists:professions,id',
                'nationality_id' => 'sometimes|required|exists:nationalities,id',
                'city_id' => 'sometimes|required|exists:cities,id',
                'district_id' => 'sometimes|required|exists:districts,id',
                'package_id' => 'sometimes|required|exists:packages,id',
                'description' => 'sometimes|required|string|max:1000',
                'requirements' => 'sometimes|required|string|max:1000',
                'start_date' => 'sometimes|required|date|after:today',
                'end_date' => 'sometimes|required|date|after:start_date',
                'budget_min' => 'sometimes|required|numeric|min:0',
                'budget_max' => 'sometimes|required|numeric|min:budget_min',
                'status' => 'sometimes|required|in:open,closed,cancelled',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update request
            $workerRequest->update($request->all());

            $workerRequest->load(['customer', 'profession', 'nationality', 'city', 'district', 'package']);

            return response()->json([
                'status' => 200,
                'message' => 'Request updated successfully',
                'data' => $workerRequest
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error updating request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified request
     */
    public function destroy(WorkerRequest $workerRequest): JsonResponse
    {
        try {
            // Check if request has proposals
            if ($workerRequest->proposals()->exists()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Cannot delete request with existing proposals'
                ], 400);
            }

            $workerRequest->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Request deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting request: ' . $e->getMessage()
            ], 500);
        }
    }
}