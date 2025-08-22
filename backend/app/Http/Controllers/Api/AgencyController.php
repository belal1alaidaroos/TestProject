<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use App\Models\SupplierProposal;
use App\Services\AgencyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AgencyController extends Controller
{
    protected AgencyService $agencyService;

    public function __construct(AgencyService $agencyService)
    {
        $this->agencyService = $agencyService;
    }

    /**
     * Get recruitment requests for agencies
     */
    public function getRequests(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validate user type
        if ($user->user_type !== 'Agency') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'profession_id' => 'nullable|exists:professions,id',
            'status' => 'nullable|in:Open,PartiallyAwarded,FullyAwarded,Closed',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $requests = $this->agencyService->getRequestsForAgency($user->id, $request->all());

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific recruitment request
     */
    public function getRequest(string $requestId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Agency') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $request = $this->agencyService->getRequestForAgency($requestId, $user->id);

            if (!$request) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $request
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit a proposal for a recruitment request
     */
    public function submitProposal(Request $request, string $requestId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Agency') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'offered_qty' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'lead_time_days' => 'required|integer|min:1|max:365',
            'valid_until' => 'required|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $proposal = $this->agencyService->submitProposal($requestId, $user->id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Proposal submitted successfully',
                'data' => $proposal
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get agency's proposals
     */
    public function getProposals(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Agency') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:Submitted,Approved,PartiallyApproved,Rejected,Withdrawn',
            'request_id' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $proposals = $this->agencyService->getAgencyProposals($user->id, $request->all());

            return response()->json([
                'success' => true,
                'data' => $proposals
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load proposals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific proposal
     */
    public function getProposal(string $proposalId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Agency') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $proposal = $this->agencyService->getAgencyProposal($proposalId, $user->id);

            if (!$proposal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proposal not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $proposal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a proposal
     */
    public function updateProposal(Request $request, string $proposalId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Agency') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'offered_qty' => 'sometimes|required|integer|min:1',
            'unit_price' => 'sometimes|required|numeric|min:0',
            'lead_time_days' => 'sometimes|required|integer|min:1|max:365',
            'valid_until' => 'sometimes|required|date|after:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $proposal = $this->agencyService->updateProposal($proposalId, $user->id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Proposal updated successfully',
                'data' => $proposal
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Withdraw a proposal
     */
    public function withdrawProposal(string $proposalId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Agency') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $this->agencyService->withdrawProposal($proposalId, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Proposal withdrawn successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to withdraw proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a proposal
     */
    public function deleteProposal(string $proposalId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Agency') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $this->agencyService->deleteProposal($proposalId, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Proposal deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}