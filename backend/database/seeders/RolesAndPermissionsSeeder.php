<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Insert Roles
        $roles = [
            [
                'id' => Str::uuid(),
                'name' => 'Super Admin',
                'display_name' => 'Super Admin',
                'description' => 'Super Admin role',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Admin',
                'display_name' => 'Admin',
                'description' => 'Administrator role',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Manager',
                'display_name' => 'Manager',
                'description' => 'Manager role',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Employee',
                'display_name' => 'Employee',
                'description' => 'Employee role',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('app_roles')->insert([
                'id' => $role['id'],
                'name' => $role['name'],
                'display_name' => $role['display_name'] ?? $role['name'], // always filled
                'description' => $role['description'] ?? $role['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert Permissions
        $permissions = [
            [
                'id' => Str::uuid(),
                'name' => 'manage_users',
                'display_name' => 'Manage Users',
                'description' => 'Ability to create, edit, delete users',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'manage_roles',
                'display_name' => 'Manage Roles',
                'description' => 'Ability to assign and edit roles',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'view_reports',
                'display_name' => 'View Reports',
                'description' => 'Ability to view system reports',
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('app_permissions')->insert([
                'id' => $permission['id'],
                'name' => $permission['name'],
                'display_name' => $permission['display_name'] ?? $permission['name'],
                'description' => $permission['description'] ?? $permission['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
