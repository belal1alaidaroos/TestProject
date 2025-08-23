<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Get the Employee role ID
        $employeeRoleId = DB::table('app_roles')
            ->where('name', 'Employee')
            ->value('id');

        if (!$employeeRoleId) {
            $this->command->error('Employee role not found. Please run the employee role migration first.');
            return;
        }

        // Create sample employee users
        $employees = [
            [
                'id' => (string) Str::uuid(),
                'name' => 'Ahmed Hassan',
                'email' => 'ahmed.hassan@company.com',
                'phone' => '+966501234567',
                'user_type' => 'Internal',
                'is_active' => true,
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Fatima Al-Zahra',
                'email' => 'fatima.alzahra@company.com',
                'phone' => '+966507654321',
                'user_type' => 'Internal',
                'is_active' => true,
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Omar Khalil',
                'email' => 'omar.khalil@company.com',
                'phone' => '+966509876543',
                'user_type' => 'Internal',
                'is_active' => true,
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($employees as $employee) {
            // Insert the user
            DB::table('app_users')->insert($employee);

            // Assign Employee role
            DB::table('app_user_roles')->insert([
                'id' => (string) Str::uuid(),
                'app_user_id' => $employee['id'],
                'app_role_id' => $employeeRoleId,
                'is_primary' => true,
                'assigned_by' => null,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Sample employee users created successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('Email: ahmed.hassan@company.com, Password: password123');
        $this->command->info('Email: fatima.alzahra@company.com, Password: password123');
        $this->command->info('Email: omar.khalil@company.com, Password: password123');
    }
}