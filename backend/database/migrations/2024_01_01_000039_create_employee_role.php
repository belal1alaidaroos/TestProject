<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert Employee role
        DB::table('app_roles')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Employee',
            'display_name' => 'Employee',
            'description' => 'Company employee with limited access to manage assigned workers, contracts, and reservations',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get the employee role ID
        $employeeRoleId = DB::table('app_roles')
            ->where('name', 'Employee')
            ->value('id');

        // Insert basic permissions for employee role
        $permissions = [
            'workers.view',
            'workers.manage',
            'contracts.view',
            'contracts.update_status',
            'reservations.view',
            'reservations.update_status',
            'notifications.view',
            'notifications.mark_read',
            'profile.view',
            'profile.update',
        ];

        foreach ($permissions as $permission) {
            DB::table('role_permissions')->insert([
                'id' => (string) Str::uuid(),
                'app_role_id' => $employeeRoleId,
                'resource_name' => $permission,
                'can_create' => false,
                'can_read' => true,
                'can_update' => in_array($permission, ['contracts.update_status', 'reservations.update_status', 'notifications.mark_read', 'profile.update']),
                'can_delete' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Get the employee role ID
        $employeeRoleId = DB::table('app_roles')
            ->where('name', 'Employee')
            ->value('id');

        if ($employeeRoleId) {
            // Remove role permissions
            DB::table('role_permissions')
                ->where('app_role_id', $employeeRoleId)
                ->delete();

            // Remove user role assignments
            DB::table('app_user_roles')
                ->where('app_role_id', $employeeRoleId)
                ->delete();

            // Remove the role
            DB::table('app_roles')
                ->where('id', $employeeRoleId)
                ->delete();
        }
    }
};