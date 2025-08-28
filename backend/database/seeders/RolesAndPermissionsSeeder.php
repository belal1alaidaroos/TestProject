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
                'name_en' => 'Super Admin',
                'name_ar' => 'مدير عام',
                'description_en' => 'Super Admin role',
                'description_ar' => 'دور المدير العام',
            ],
            [
                'id' => Str::uuid(),
                'name_en' => 'Admin',
                'name_ar' => 'مدير',
                'description_en' => 'Administrator role',
                'description_ar' => 'دور المدير',
            ],
            [
                'id' => Str::uuid(),
                'name_en' => 'Manager',
                'name_ar' => 'مدير قسم',
                'description_en' => 'Manager role',
                'description_ar' => 'دور مدير القسم',
            ],
            [
                'id' => Str::uuid(),
                'name_en' => 'Employee',
                'name_ar' => 'موظف',
                'description_en' => 'Employee role',
                'description_ar' => 'دور الموظف',
            ],
            [
                'id' => Str::uuid(),
                'name_en' => 'Customer',
                'name_ar' => 'عميل',
                'description_en' => 'Customer role',
                'description_ar' => 'دور العميل',
            ],
            [
                'id' => Str::uuid(),
                'name_en' => 'Agency',
                'name_ar' => 'وكالة',
                'description_en' => 'Agency role',
                'description_ar' => 'دور الوكالة',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('app_roles')->insert([
                'id' => $role['id'],
                'name_en' => $role['name_en'],
                'name_ar' => $role['name_ar'],
                'description_en' => $role['description_en'],
                'description_ar' => $role['description_ar'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert Resources (instead of permissions)
        $resources = [
            [
                'id' => Str::uuid(),
                'name' => 'users',
                'description_en' => 'Ability to create, edit, delete users',
                'description_ar' => 'إمكانية إنشاء وتعديل وحذف المستخدمين',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'roles',
                'description_en' => 'Ability to assign and edit roles',
                'description_ar' => 'إمكانية تعيين وتعديل الأدوار',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'reports',
                'description_en' => 'Ability to view system reports',
                'description_ar' => 'إمكانية عرض تقارير النظام',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'workers',
                'description_en' => 'Ability to manage workers',
                'description_ar' => 'إمكانية إدارة العمالة',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'contracts',
                'description_en' => 'Ability to manage contracts',
                'description_ar' => 'إمكانية إدارة العقود',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'requests',
                'description_en' => 'Ability to manage recruitment requests',
                'description_ar' => 'إمكانية إدارة طلبات التوظيف',
            ],
            [
                'id' => Str::uuid(),
                'name' => 'proposals',
                'description_en' => 'Ability to manage proposals',
                'description_ar' => 'إمكانية إدارة العروض',
            ],
        ];

        foreach ($resources as $resource) {
            DB::table('app_resources')->insert([
                'id' => $resource['id'],
                'name' => $resource['name'],
                'description_en' => $resource['description_en'],
                'description_ar' => $resource['description_ar'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Get role IDs for linking
        $superAdminRoleId = DB::table('app_roles')->where('name_en', 'Super Admin')->first()->id;
        $adminRoleId = DB::table('app_roles')->where('name_en', 'Admin')->first()->id;
        $managerRoleId = DB::table('app_roles')->where('name_en', 'Manager')->first()->id;
        $employeeRoleId = DB::table('app_roles')->where('name_en', 'Employee')->first()->id;
        $customerRoleId = DB::table('app_roles')->where('name_en', 'Customer')->first()->id;
        $agencyRoleId = DB::table('app_roles')->where('name_en', 'Agency')->first()->id;

        // Get resource IDs for linking
        $usersResourceId = DB::table('app_resources')->where('name', 'users')->first()->id;
        $rolesResourceId = DB::table('app_resources')->where('name', 'roles')->first()->id;
        $reportsResourceId = DB::table('app_resources')->where('name', 'reports')->first()->id;
        $workersResourceId = DB::table('app_resources')->where('name', 'workers')->first()->id;
        $contractsResourceId = DB::table('app_resources')->where('name', 'contracts')->first()->id;
        $requestsResourceId = DB::table('app_resources')->where('name', 'requests')->first()->id;
        $proposalsResourceId = DB::table('app_resources')->where('name', 'proposals')->first()->id;

        // Create role permissions
        $rolePermissions = [
            // Super Admin - Full access to everything
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $usersResourceId, 'action' => 'Create'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $usersResourceId, 'action' => 'Read'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $usersResourceId, 'action' => 'Update'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $usersResourceId, 'action' => 'Delete'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $rolesResourceId, 'action' => 'Create'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $rolesResourceId, 'action' => 'Read'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $rolesResourceId, 'action' => 'Update'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $rolesResourceId, 'action' => 'Delete'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $reportsResourceId, 'action' => 'Read'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Create'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Read'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Update'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Delete'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Create'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Read'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Update'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Delete'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Create'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Read'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Update'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Delete'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Create'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Read'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Update'],
            ['app_role_id' => $superAdminRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Delete'],

            // Admin - Full access to most resources
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $usersResourceId, 'action' => 'Create'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $usersResourceId, 'action' => 'Read'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $usersResourceId, 'action' => 'Update'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $usersResourceId, 'action' => 'Delete'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $rolesResourceId, 'action' => 'Read'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $reportsResourceId, 'action' => 'Read'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Create'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Read'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Update'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Create'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Read'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Update'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Create'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Read'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Update'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Read'],
            ['app_role_id' => $adminRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Update'],

            // Manager - Limited access
            ['app_role_id' => $managerRoleId, 'app_resource_id' => $usersResourceId, 'action' => 'Read'],
            ['app_role_id' => $managerRoleId, 'app_resource_id' => $reportsResourceId, 'action' => 'Read'],
            ['app_role_id' => $managerRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Read'],
            ['app_role_id' => $managerRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Update'],
            ['app_role_id' => $managerRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Read'],
            ['app_role_id' => $managerRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Read'],
            ['app_role_id' => $managerRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Read'],

            // Employee - Basic access
            ['app_role_id' => $employeeRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Read'],
            ['app_role_id' => $employeeRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Read'],
            ['app_role_id' => $employeeRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Read'],

            // Customer - Customer-specific access
            ['app_role_id' => $customerRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Read'],
            ['app_role_id' => $customerRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Create'],
            ['app_role_id' => $customerRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Read'],
            ['app_role_id' => $customerRoleId, 'app_resource_id' => $contractsResourceId, 'action' => 'Update'],
            ['app_role_id' => $customerRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Create'],
            ['app_role_id' => $customerRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Read'],
            ['app_role_id' => $customerRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Update'],

            // Agency - Agency-specific access
            ['app_role_id' => $agencyRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Create'],
            ['app_role_id' => $agencyRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Read'],
            ['app_role_id' => $agencyRoleId, 'app_resource_id' => $workersResourceId, 'action' => 'Update'],
            ['app_role_id' => $agencyRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Create'],
            ['app_role_id' => $agencyRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Read'],
            ['app_role_id' => $agencyRoleId, 'app_resource_id' => $proposalsResourceId, 'action' => 'Update'],
            ['app_role_id' => $agencyRoleId, 'app_resource_id' => $requestsResourceId, 'action' => 'Read'],
        ];

        foreach ($rolePermissions as $permission) {
            DB::table('role_permissions')->insert([
                'id' => Str::uuid(),
                'app_role_id' => $permission['app_role_id'],
                'app_resource_id' => $permission['app_resource_id'],
                'action' => $permission['action'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
