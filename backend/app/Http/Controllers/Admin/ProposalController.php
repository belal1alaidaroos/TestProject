<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Proposal;
use App\Models\Request as WorkerRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProposalController extends Controller
{
    /**
     * Display a listing of proposals for a specific request
     */
    public function index(WorkerRequest $request): JsonResponse
    {
        try {
            $proposals = $request->proposals()
                ->with(['agency', 'worker', 'worker.profession', 'worker.nationality'])
                ->orderBy('created_at', 'desc')
                ->get();

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
     * Approve a proposal
     */
    public function approve(Request $request, Proposal $proposal): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'approval_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if proposal can be approved
            if ($proposal->status !== 'pending') {
                return response()->json([
                    'status' => 400,
                    'message' => 'Proposal is not in pending status'
                ], 400);
            }

            // Check if request is still open
            if ($proposal->request->status !== 'open') {
                return response()->json([
                    'status' => 400,
                    'message' => 'Request is not open for approval'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Approve the selected proposal
                $proposal->update([
                    'status' => 'approved',
                    'approval_notes' => $request->approval_notes,
                    'approved_at' => now()
                ]);

                // Reject all other proposals for this request
                Proposal::where('request_id', $proposal->request_id)
                    ->where('id', '!=', $proposal->id)
                    ->update([
                        'status' => 'rejected',
                        'rejection_notes' => 'Another proposal was selected',
                        'rejected_at' => now()
                    ]);

                // Close the request
                $proposal->request->update([
                    'status' => 'closed',
                    'selected_proposal_id' => $proposal->id,
                    'closed_at' => now()
                ]);

                // Update worker status
                $proposal->worker->update(['status' => 'assigned']);

                DB::commit();

                $proposal->load(['request', 'agency', 'worker']);

                return response()->json([
                    'status' => 200,
                    'message' => 'Proposal approved successfully',
                    'data' => $proposal
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error approving proposal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a proposal
     */
    public function reject(Request $request, Proposal $proposal): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rejection_notes' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if proposal can be rejected
            if ($proposal->status !== 'pending') {
                return response()->json([
                    'status' => 400,
                    'message' => 'Proposal is not in pending status'
                ], 400);
            }

            // Reject the proposal
            $proposal->update([
                'status' => 'rejected',
                'rejection_notes' => $request->rejection_notes,
                'rejected_at' => now()
            ]);

            $proposal->load(['request', 'agency', 'worker']);

            return response()->json([
                'status' => 200,
                'message' => 'Proposal rejected successfully',
                'data' => $proposal
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error rejecting proposal: ' . $e->getMessage()
            ], 500);
        }
    }
}