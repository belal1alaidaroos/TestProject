<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Proposal;
use App\Models\Request as WorkerRequest;
use App\Models\Worker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProposalController extends Controller
{
    /**
     * Display a listing of agency's proposals
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Proposal::with(['request', 'request.customer', 'worker', 'worker.profession'])
                ->where('agency_id', Auth::user()->agency_id);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('request_id')) {
                $query->where('request_id', $request->request_id);
            }

            if ($request->has('worker_id')) {
                $query->where('worker_id', $request->worker_id);
            }

            // Date range filter
            if ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->end_date);
            }

            // Sort by
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $proposals = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 200,
                'message' => 'Proposals retrieved successfully',
                'data' => $proposals
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving proposals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created proposal
     */
    public function store(Request $request, WorkerRequest $workerRequest): JsonResponse
    {
        try {
            // Check if request is still open
            if ($workerRequest->status !== 'open' || !$workerRequest->is_active) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Request is not open for proposals'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'worker_id' => 'required|exists:workers,id',
                'proposed_rate' => 'required|numeric|min:0.01',
                'start_date' => 'required|date|after:today',
                'end_date' => 'required|date|after:start_date',
                'cover_letter' => 'required|string|max:1000',
                'additional_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if worker is available
            $worker = Worker::find($request->worker_id);
            if (!$worker->is_active || $worker->status !== 'available') {
                return response()->json([
                    'status' => 400,
                    'message' => 'Worker is not available'
                ], 400);
            }

            // Check if worker belongs to the agency
            if ($worker->agency_id !== Auth::user()->agency_id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Worker does not belong to your agency'
                ], 403);
            }

            // Check if agency already submitted a proposal for this request
            $existingProposal = Proposal::where('request_id', $workerRequest->id)
                ->where('agency_id', Auth::user()->agency_id)
                ->first();

            if ($existingProposal) {
                return response()->json([
                    'status' => 400,
                    'message' => 'You have already submitted a proposal for this request'
                ], 400);
            }

            // Create proposal
            $proposal = Proposal::create([
                'request_id' => $workerRequest->id,
                'agency_id' => Auth::user()->agency_id,
                'worker_id' => $request->worker_id,
                'proposed_rate' => $request->proposed_rate,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'cover_letter' => $request->cover_letter,
                'additional_notes' => $request->additional_notes,
                'status' => 'pending'
            ]);

            $proposal->load(['request', 'worker', 'agency']);

            return response()->json([
                'status' => 201,
                'message' => 'Proposal submitted successfully',
                'data' => $proposal
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error submitting proposal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified proposal
     */
    public function show(Proposal $proposal): JsonResponse
    {
        try {
            // Ensure agency can only view their own proposals
            if ($proposal->agency_id !== Auth::user()->agency_id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to proposal'
                ], 403);
            }

            $proposal->load([
                'request',
                'request.customer',
                'request.profession',
                'worker',
                'worker.profession',
                'worker.nationality',
                'agency'
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Proposal details retrieved successfully',
                'data' => $proposal
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving proposal details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified proposal
     */
    public function update(Request $request, Proposal $proposal): JsonResponse
    {
        try {
            // Ensure agency can only update their own proposals
            if ($proposal->agency_id !== Auth::user()->agency_id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to proposal'
                ], 403);
            }

            // Check if proposal can be updated
            if (!in_array($proposal->status, ['pending', 'draft'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Proposal cannot be updated in its current state'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'proposed_rate' => 'sometimes|required|numeric|min:0.01',
                'start_date' => 'sometimes|required|date|after:today',
                'end_date' => 'sometimes|required|date|after:start_date',
                'cover_letter' => 'sometimes|required|string|max:1000',
                'additional_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update proposal
            $proposal->update($request->only([
                'proposed_rate',
                'start_date',
                'end_date',
                'cover_letter',
                'additional_notes'
            ]));

            $proposal->load(['request', 'worker', 'agency']);

            return response()->json([
                'status' => 200,
                'message' => 'Proposal updated successfully',
                'data' => $proposal
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error updating proposal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified proposal
     */
    public function destroy(Proposal $proposal): JsonResponse
    {
        try {
            // Ensure agency can only delete their own proposals
            if ($proposal->agency_id !== Auth::user()->agency_id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized access to proposal'
                ], 403);
            }

            // Check if proposal can be deleted
            if (!in_array($proposal->status, ['pending', 'draft'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Proposal cannot be deleted in its current state'
                ], 400);
            }

            $proposal->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Proposal deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting proposal: ' . $e->getMessage()
            ], 500);
        }
    }
}