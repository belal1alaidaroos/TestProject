<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\AppUser;
use App\Models\AppRole;
use App\Models\AppResource;
use App\Models\RolePermission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create resources
        $resources = [
            'workers' => ['create', 'read', 'update', 'delete', 'import', 'export'],
            'contracts' => ['create', 'read', 'update', 'delete', 'import', 'export'],
            'proposals' => ['create', 'read', 'update', 'delete', 'import', 'export'],
            'users' => ['create', 'read', 'update', 'delete', 'import', 'export'],
            'settings' => ['create', 'read', 'update', 'delete', 'import', 'export'],
            'admin' => ['access'],
            'customer' => ['access'],
            'agency' => ['access'],
        ];

        foreach ($resources as $resourceName => $actions) {
            $resource = AppResource::create([
                'name' => $resourceName,
                'display_name' => ucfirst($resourceName),
                'description' => ucfirst($resourceName) . ' management',
            ]);

            foreach ($actions as $action) {
                Permission::create([
                    'name' => "{$resourceName}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // Create roles
        $roles = [
            'Super Admin' => ['workers.*', 'contracts.*', 'proposals.*', 'users.*', 'settings.*', 'admin.access'],
            'Admin' => ['workers.read', 'workers.update', 'contracts.read', 'contracts.update', 'proposals.read', 'proposals.update', 'users.read', 'settings.read', 'admin.access'],
            'Customer' => ['workers.read', 'contracts.create', 'contracts.read', 'contracts.update', 'customer.access'],
            'Agency' => ['proposals.create', 'proposals.read', 'proposals.update', 'proposals.delete', 'agency.access'],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = AppRole::create([
                'name' => $roleName,
                'description' => $roleName . ' role',
            ]);

            $role->givePermissionTo($permissions);
        }

        // Create default admin user
        $adminUser = AppUser::create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'phone' => '+966501234567',
            'password' => Hash::make('Admin@123'),
            'user_type' => 'Internal',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $adminUser->assignRole('Super Admin');

        // Create default customer user
        $customerUser = AppUser::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+966507654321',
            'password' => Hash::make('Customer@123'),
            'user_type' => 'Customer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $customerUser->assignRole('Customer');

        // Create default agency user
        $agencyUser = AppUser::create([
            'name' => 'Test Agency',
            'email' => 'agency@example.com',
            'phone' => '+966509876543',
            'password' => Hash::make('Agency@123'),
            'user_type' => 'Agency',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $agencyUser->assignRole('Agency');
    }
}