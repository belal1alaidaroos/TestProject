<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Request as WorkerRequest;
use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    /**
     * Display a listing of requests for the agency
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WorkerRequest::with(['customer', 'profession', 'city', 'district', 'nationality'])
                ->where('status', 'open')
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
            if (!$request->is_active || $request->status !== 'open') {
                return response()->json([
                    'status' => 404,
                    'message' => 'Request not available'
                ], 404);
            }

            $request->load([
                'customer',
                'profession',
                'nationality',
                'city',
                'district',
                'package',
                'proposals' => function($query) {
                    $query->where('agency_id', Auth::user()->agency_id);
                }
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
}