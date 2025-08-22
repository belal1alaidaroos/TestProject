<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\SupplierProposal;
use App\Models\SystemSetting;
use App\Services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $stats = $this->adminService->getDashboardStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all proposals for review
     */
    public function getProposals(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:Submitted,Approved,PartiallyApproved,Rejected,Withdrawn',
            'request_id' => 'nullable|string',
            'agency_id' => 'nullable|exists:app_users,id',
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
            $proposals = $this->adminService->getProposalsForReview($request->all());

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
     * Approve a proposal
     */
    public function approveProposal(Request $request, string $proposalId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
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
            $this->adminService->approveProposal($proposalId, $user->id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Proposal approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a proposal
     */
    public function rejectProposal(Request $request, string $proposalId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
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
            $this->adminService->rejectProposal($proposalId, $user->id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Proposal rejected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Partially approve a proposal
     */
    public function partialApproveProposal(Request $request, string $proposalId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'approved_qty' => 'required|integer|min:1',
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
            $this->adminService->partialApproveProposal($proposalId, $user->id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Proposal partially approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to partially approve proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users
     */
    public function getUsers(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_type' => 'nullable|in:Customer,Agency,Internal',
            'status' => 'nullable|in:Active,Inactive,Suspended',
            'search' => 'nullable|string|max:255',
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
            $users = $this->adminService->getUsers($request->all());

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific user
     */
    public function getUser(string $userId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $userData = $this->adminService->getUser($userId);

            if (!$userData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a user
     */
    public function updateUser(Request $request, string $userId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:app_users,email,' . $userId,
            'phone' => 'sometimes|required|string|max:20',
            'status' => 'sometimes|required|in:Active,Inactive,Suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userData = $this->adminService->updateUser($userId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user status
     */
    public function updateUserStatus(Request $request, string $userId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Active,Inactive,Suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->adminService->updateUserStatus($userId, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user roles
     */
    public function updateUserRoles(Request $request, string $userId): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:app_roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->adminService->updateUserRoles($userId, $request->role_ids);

            return response()->json([
                'success' => true,
                'message' => 'User roles updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system settings
     */
    public function getSystemSettings(): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $settings = $this->adminService->getSystemSettings();

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load system settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $this->adminService->updateSystemSettings($request->all());

            return response()->json([
                'success' => true,
                'message' => 'System settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update system settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset system settings to defaults
     */
    public function resetSystemSettings(): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $this->adminService->resetSystemSettings();

            return response()->json([
                'success' => true,
                'message' => 'System settings reset to defaults successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset system settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get agencies for filtering
     */
    public function getAgencies(): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $agencies = $this->adminService->getAgencies();

            return response()->json([
                'success' => true,
                'data' => $agencies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load agencies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get roles for assignment
     */
    public function getRoles(): JsonResponse
    {
        $user = Auth::user();

        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            $roles = $this->adminService->getRoles();

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}