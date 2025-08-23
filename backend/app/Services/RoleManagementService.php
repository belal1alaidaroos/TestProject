<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\AppRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleManagementService
{
    public function assignRoleToUser($userId, $roleId, $assignedBy = null, $isPrimary = false, $notes = null)
    {
        try {
            DB::beginTransaction();

            $user = AppUser::findOrFail($userId);
            $role = AppRole::findOrFail($roleId);

            // Check if user already has this role
            if ($user->roles()->where('app_role_id', $roleId)->exists()) {
                throw new \Exception('User already has this role');
            }

            // If this is a primary role, remove primary flag from other roles
            if ($isPrimary) {
                $user->roles()->updateExistingPivot(
                    $user->roles->pluck('id'),
                    ['is_primary' => false]
                );
            }

            // Assign the role
            $user->roles()->attach($roleId, [
                'assigned_by' => $assignedBy ?? auth()->id(),
                'assigned_at' => now(),
                'is_primary' => $isPrimary,
                'notes' => $notes,
            ]);

            // Log the action
            $this->logRoleAssignment($user, $role, $assignedBy, $isPrimary);

            DB::commit();

            return [
                'success' => true,
                'message' => "Role '{$role->name}' assigned successfully",
                'data' => [
                    'user' => $user->load('roles'),
                    'role' => $role,
                    'is_primary' => $isPrimary,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role assignment failed', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function removeRoleFromUser($userId, $roleId, $removedBy = null)
    {
        try {
            DB::beginTransaction();

            $user = AppUser::findOrFail($userId);
            $role = AppRole::findOrFail($roleId);

            // Check if user has this role
            if (!$user->roles()->where('app_role_id', $roleId)->exists()) {
                throw new \Exception('User does not have this role');
            }

            // Check if this is the user's only role
            if ($user->roles()->count() === 1) {
                throw new \Exception('Cannot remove the only role from a user');
            }

            // Check if this is a primary role and there are other roles
            $userRole = $user->roles()->where('app_role_id', $roleId)->first();
            if ($userRole->pivot->is_primary && $user->roles()->count() > 1) {
                // Make another role primary
                $otherRole = $user->roles()->where('app_role_id', '!=', $roleId)->first();
                $user->roles()->updateExistingPivot($otherRole->id, ['is_primary' => true]);
            }

            // Remove the role
            $user->roles()->detach($roleId);

            // Log the action
            $this->logRoleRemoval($user, $role, $removedBy);

            DB::commit();

            return [
                'success' => true,
                'message' => "Role '{$role->name}' removed successfully",
                'data' => [
                    'user' => $user->load('roles'),
                    'role' => $role,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role removal failed', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function syncUserRoles($userId, $roleIds, $assignedBy = null, $primaryRoleId = null)
    {
        try {
            DB::beginTransaction();

            $user = AppUser::findOrFail($userId);
            $roles = AppRole::whereIn('id', $roleIds)->get();

            if ($roles->count() !== count($roleIds)) {
                throw new \Exception('One or more roles not found');
            }

            // Remove all existing roles
            $user->roles()->detach();

            // Assign new roles
            foreach ($roles as $role) {
                $isPrimary = $primaryRoleId && $role->id === $primaryRoleId;
                
                $user->roles()->attach($role->id, [
                    'assigned_by' => $assignedBy ?? auth()->id(),
                    'assigned_at' => now(),
                    'is_primary' => $isPrimary,
                ]);
            }

            // Log the action
            $this->logRoleSync($user, $roles, $assignedBy, $primaryRoleId);

            DB::commit();

            return [
                'success' => true,
                'message' => 'User roles synchronized successfully',
                'data' => [
                    'user' => $user->load('roles'),
                    'roles' => $roles,
                    'primary_role_id' => $primaryRoleId,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role sync failed', [
                'user_id' => $userId,
                'role_ids' => $roleIds,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function setPrimaryRole($userId, $roleId, $setBy = null)
    {
        try {
            DB::beginTransaction();

            $user = AppUser::findOrFail($userId);
            $role = AppRole::findOrFail($roleId);

            // Check if user has this role
            if (!$user->roles()->where('app_role_id', $roleId)->exists()) {
                throw new \Exception('User does not have this role');
            }

            // Remove primary flag from all roles
            $user->roles()->updateExistingPivot(
                $user->roles->pluck('id'),
                ['is_primary' => false]
            );

            // Set the new primary role
            $user->roles()->updateExistingPivot($roleId, [
                'is_primary' => true,
                'assigned_by' => $setBy ?? auth()->id(),
                'assigned_at' => now(),
            ]);

            // Log the action
            $this->logPrimaryRoleChange($user, $role, $setBy);

            DB::commit();

            return [
                'success' => true,
                'message' => "Role '{$role->name}' set as primary",
                'data' => [
                    'user' => $user->load('roles'),
                    'primary_role' => $role,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Primary role change failed', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function getUserRoles($userId)
    {
        $user = AppUser::with(['roles.permissions', 'assignedBy'])->findOrFail($userId);
        
        return [
            'user' => $user,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'is_primary' => $role->pivot->is_primary,
                    'assigned_by' => $role->pivot->assigned_by,
                    'assigned_at' => $role->pivot->assigned_at,
                    'assigned_by_user' => $role->pivot->assignedBy,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            }),
            'primary_role' => $user->primaryRole(),
            'all_permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }

    public function getUsersByRole($roleName, $filters = [])
    {
        $query = AppUser::with(['roles', 'assignedBy'])
            ->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getRoleAssignmentHistory($userId)
    {
        $user = AppUser::with(['roles' => function ($query) {
            $query->orderBy('pivot_assigned_at', 'desc');
        }, 'assignedBy'])->findOrFail($userId);

        return $user->roles->map(function ($role) {
            return [
                'role' => $role,
                'assigned_by' => $role->pivot->assignedBy,
                'assigned_at' => $role->pivot->assigned_at,
                'is_primary' => $role->pivot->is_primary,
                'notes' => $role->pivot->notes,
            ];
        });
    }

    private function logRoleAssignment($user, $role, $assignedBy, $isPrimary)
    {
        // This would integrate with your AuditService
        // $auditService = app(AuditService::class);
        // $auditService->logCustomAction('AppUser', $user->id, 'role_assigned', [
        //     'role_name' => $role->name,
        //     'assigned_by' => $assignedBy,
        //     'is_primary' => $isPrimary,
        // ]);
    }

    private function logRoleRemoval($user, $role, $removedBy)
    {
        // This would integrate with your AuditService
        // $auditService = app(AuditService::class);
        // $auditService->logCustomAction('AppUser', $user->id, 'role_removed', [
        //     'role_name' => $role->name,
        //     'removed_by' => $removedBy,
        // ]);
    }

    private function logRoleSync($user, $roles, $assignedBy, $primaryRoleId)
    {
        // This would integrate with your AuditService
        // $auditService = app(AuditService::class);
        // $auditService->logCustomAction('AppUser', $user->id, 'roles_synced', [
        //     'role_names' => $roles->pluck('name'),
        //     'assigned_by' => $assignedBy,
        //     'primary_role_id' => $primaryRoleId,
        // ]);
    }

    private function logPrimaryRoleChange($user, $role, $setBy)
    {
        // This would integrate with your AuditService
        // $auditService = app(AuditService::class);
        // $auditService->logCustomAction('AppUser', $user->id, 'primary_role_changed', [
        //     'role_name' => $role->name,
        //     'set_by' => $setBy,
        // ]);
    }
}