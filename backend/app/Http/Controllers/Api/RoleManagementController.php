<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RoleManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleManagementController extends Controller
{
    public function __construct(
        private RoleManagementService $roleManagementService
    ) {}

    public function assignRole(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|uuid|exists:app_users,id',
                'role_id' => 'required|uuid|exists:app_roles,id',
                'is_primary' => 'boolean',
                'notes' => 'nullable|string|max:500',
            ]);

            $result = $this->roleManagementService->assignRoleToUser(
                $request->user_id,
                $request->role_id,
                auth()->id(),
                $request->boolean('is_primary', false),
                $request->notes
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Role assignment failed', [
                'user_id' => $request->user_id ?? null,
                'role_id' => $request->role_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function removeRole(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|uuid|exists:app_users,id',
                'role_id' => 'required|uuid|exists:app_roles,id',
            ]);

            $result = $this->roleManagementService->removeRoleFromUser(
                $request->user_id,
                $request->role_id,
                auth()->id()
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Role removal failed', [
                'user_id' => $request->user_id ?? null,
                'role_id' => $request->role_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function syncRoles(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|uuid|exists:app_users,id',
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'uuid|exists:app_roles,id',
                'primary_role_id' => 'nullable|uuid|exists:app_roles,id',
            ]);

            $result = $this->roleManagementService->syncUserRoles(
                $request->user_id,
                $request->role_ids,
                auth()->id(),
                $request->primary_role_id
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Role sync failed', [
                'user_id' => $request->user_id ?? null,
                'role_ids' => $request->role_ids ?? [],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function setPrimaryRole(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|uuid|exists:app_users,id',
                'role_id' => 'required|uuid|exists:app_roles,id',
            ]);

            $result = $this->roleManagementService->setPrimaryRole(
                $request->user_id,
                $request->role_id,
                auth()->id()
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Primary role change failed', [
                'user_id' => $request->user_id ?? null,
                'role_id' => $request->role_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getUserRoles(Request $request, string $userId): JsonResponse
    {
        try {
            $result = $this->roleManagementService->getUserRoles($userId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Get user roles failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getUsersByRole(Request $request, string $roleName): JsonResponse
    {
        try {
            $filters = $request->only(['is_active', 'user_type', 'search', 'per_page']);
            $result = $this->roleManagementService->getUsersByRole($roleName, $filters);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Get users by role failed', [
                'role_name' => $roleName,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getRoleAssignmentHistory(Request $request, string $userId): JsonResponse
    {
        try {
            $result = $this->roleManagementService->getRoleAssignmentHistory($userId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Get role assignment history failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getRoleStatistics(): JsonResponse
    {
        try {
            $statistics = [
                'total_users' => \App\Models\AppUser::count(),
                'users_by_role' => \App\Models\AppRole::withCount('users')->get(),
                'recent_role_assignments' => \App\Models\AppUser::with(['roles' => function ($query) {
                    $query->wherePivot('assigned_at', '>=', now()->subDays(30));
                }])->get(),
                'role_distribution' => \App\Models\AppUser::withCount('roles')->get()->groupBy('roles_count')->map->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Get role statistics failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}