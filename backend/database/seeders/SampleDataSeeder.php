<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\City;
use App\Models\District;
use App\Models\Profession;
use App\Models\Nationality;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Agency;
use App\Models\Worker;
use App\Models\WorkerStage;
use App\Models\RecruitmentRequest;
use App\Models\SupplierProposal;
use App\Models\Package;
use App\Models\WorkerReservation;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AppUser;
use App\Models\AppRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample countries
        $saudiArabia = Country::create([
            'name_en' => 'Saudi Arabia',
            'name_ar' => 'المملكة العربية السعودية',
            'code' => 'SA',
            'phone_code' => '+966',
            'is_active' => true,
        ]);

        // Create sample cities
        $riyadh = City::create([
            'name_en' => 'Riyadh',
            'name_ar' => 'الرياض',
            'country_id' => $saudiArabia->id,
            'is_active' => true,
        ]);

        $jeddah = City::create([
            'name_en' => 'Jeddah',
            'name_ar' => 'جدة',
            'country_id' => $saudiArabia->id,
            'is_active' => true,
        ]);

        // Create sample districts
        $olaya = District::create([
            'name_en' => 'Olaya',
            'name_ar' => 'العليا',
            'city_id' => $riyadh->id,
            'is_active' => true,
        ]);

        $malaz = District::create([
            'name_en' => 'Malaz',
            'name_ar' => 'الملز',
            'city_id' => $riyadh->id,
            'is_active' => true,
        ]);

        // Create sample professions
        $professions = [
            ['name_en' => 'Housekeeper', 'name_ar' => 'مدبرة منزل', 'description_en' => 'Domestic cleaning and housekeeping services', 'description_ar' => 'خدمات التنظيف المنزلي وإدارة المنزل'],
            ['name_en' => 'Driver', 'name_ar' => 'سائق', 'description_en' => 'Personal and commercial driving services', 'description_ar' => 'خدمات القيادة الشخصية والتجارية'],
            ['name_en' => 'Cook', 'name_ar' => 'طباخ', 'description_en' => 'Food preparation and cooking services', 'description_ar' => 'خدمات إعداد الطعام والطبخ'],
            ['name_en' => 'Nanny', 'name_ar' => 'مربية أطفال', 'description_en' => 'Childcare and babysitting services', 'description_ar' => 'خدمات رعاية الأطفال والحضانة'],
            ['name_en' => 'Security Guard', 'name_ar' => 'حارس أمن', 'description_en' => 'Security and protection services', 'description_ar' => 'خدمات الأمن والحماية'],
            ['name_en' => 'Gardener', 'name_ar' => 'بستاني', 'description_en' => 'Landscaping and gardening services', 'description_ar' => 'خدمات تنسيق الحدائق والبستنة'],
        ];

        foreach ($professions as $profession) {
            Profession::create([
                'name_en' => $profession['name_en'],
                'name_ar' => $profession['name_ar'],
                'description_en' => $profession['description_en'],
                'description_ar' => $profession['description_ar'],
                'is_active' => true,
            ]);
        }

        // Create sample nationalities
        $nationalities = [
            ['name_en' => 'Saudi', 'name_ar' => 'سعودي', 'code' => 'SA'],
            ['name_en' => 'Egyptian', 'name_ar' => 'مصري', 'code' => 'EG'],
            ['name_en' => 'Filipino', 'name_ar' => 'فلبيني', 'code' => 'PH'],
            ['name_en' => 'Indian', 'name_ar' => 'هندي', 'code' => 'IN'],
            ['name_en' => 'Pakistani', 'name_ar' => 'باكستاني', 'code' => 'PK'],
            ['name_en' => 'Bangladeshi', 'name_ar' => 'بنغلاديشي', 'code' => 'BD'],
        ];

        foreach ($nationalities as $nationality) {
            Nationality::create([
                'name_en' => $nationality['name_en'],
                'name_ar' => $nationality['name_ar'],
                'code' => $nationality['code'],
                'is_active' => true,
            ]);
        }

        // Get roles for user assignments
        $superAdminRole = AppRole::where('name_en', 'Super Admin')->first();
        $adminRole = AppRole::where('name_en', 'Admin')->first();
        $managerRole = AppRole::where('name_en', 'Manager')->first();
        $employeeRole = AppRole::where('name_en', 'Employee')->first();
        $customerRole = AppRole::where('name_en', 'Customer')->first();
        $agencyRole = AppRole::where('name_en', 'Agency')->first();

        // Create test users for each portal
        // 1. Admin Portal Users
        $adminUser = AppUser::create([
            'id' => Str::uuid(),
            'name' => 'Super Admin User',
            'email' => 'admin@paypass.com',
            'phone' => '+966500000001',
            'user_type' => 'Internal',
            'password' => Hash::make('paypass8523'),
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Assign Super Admin role
        if ($superAdminRole) {
            DB::table('app_user_roles')->insert([
                'id' => Str::uuid(),
                'app_user_id' => $adminUser->id,
                'app_role_id' => $superAdminRole->id,
                'is_primary' => true,
                'assigned_by' => $adminUser->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $managerUser = AppUser::create([
            'id' => Str::uuid(),
            'name' => 'Manager User',
            'email' => 'manager@paypass.com',
            'phone' => '+966500000002',
            'user_type' => 'Internal',
            'password' => Hash::make('paypass8523'),
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Assign Manager role
        if ($managerRole) {
            DB::table('app_user_roles')->insert([
                'id' => Str::uuid(),
                'app_user_id' => $managerUser->id,
                'app_role_id' => $managerRole->id,
                'is_primary' => true,
                'assigned_by' => $adminUser->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Employee Portal User
        $employeeUser = AppUser::create([
            'id' => Str::uuid(),
            'name' => 'Employee User',
            'email' => 'employee@paypass.com',
            'phone' => '+966500000003',
            'user_type' => 'Internal',
            'password' => Hash::make('paypass8523'),
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Assign Employee role
        if ($employeeRole) {
            DB::table('app_user_roles')->insert([
                'id' => Str::uuid(),
                'app_user_id' => $employeeUser->id,
                'app_role_id' => $employeeRole->id,
                'is_primary' => true,
                'assigned_by' => $adminUser->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create sample agency first
        $agency = Agency::create([
            'name_en' => 'Test Recruitment Agency',
            'name_ar' => 'وكالة التوظيف التجريبية',
            'email' => 'agency@paypass.com',
            'phone' => '+966509876543',
            'license_number' => 'AG123456',
            'contact_person' => 'Agency Manager',
            'address' => '123 Agency Street, Riyadh',
            'country_id' => $saudiArabia->id,
            'city_id' => $riyadh->id,
            'status' => 'active',
        ]);

        // 3. Agency Portal User
        $agencyUser = AppUser::create([
            'id' => Str::uuid(),
            'name' => 'Agency User',
            'email' => 'agency@paypass.com',
            'phone' => '+966500000004',
            'user_type' => 'Agency',
            'agency_id' => $agency->id,
            'password' => Hash::make('paypass8523'),
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Assign Agency role
        if ($agencyRole) {
            DB::table('app_user_roles')->insert([
                'id' => Str::uuid(),
                'app_user_id' => $agencyUser->id,
                'app_role_id' => $agencyRole->id,
                'is_primary' => true,
                'assigned_by' => $adminUser->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Customer Portal User
        $customerUser = AppUser::create([
            'id' => Str::uuid(),
            'name' => 'Customer User',
            'email' => 'customer@paypass.com',
            'phone' => '+966500000005',
            'user_type' => 'Customer',
            'password' => Hash::make('paypass8523'),
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Assign Customer role
        if ($customerRole) {
            DB::table('app_user_roles')->insert([
                'id' => Str::uuid(),
                'app_user_id' => $customerUser->id,
                'app_role_id' => $customerRole->id,
                'is_primary' => true,
                'assigned_by' => $adminUser->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create sample customer linked to user
        $customer = Customer::create([
            'app_user_id' => $customerUser->id,
            'company_name_en' => 'Test Customer Company',
            'company_name_ar' => 'شركة العميل التجريبية',
            'contact_person' => 'Customer Manager',
            'email' => 'customer@paypass.com',
            'phone' => '+966507654321',
            'status' => 'active',
        ]);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'title' => 'Home Address',
            'address_line' => '123 Test Street, Building A, Floor 2',
            'city_id' => $riyadh->id,
            'district_id' => $olaya->id,
            'is_default' => true,
            'status' => 'active',
        ]);

        // Create sample workers
        $workers = [
            [
                'worker_number' => 'W001',
                'name_en' => 'Ahmed Hassan',
                'name_ar' => 'أحمد حسن',
                'date_of_birth' => '1990-01-01',

                'profession_id' => Profession::where('name_en', 'Driver')->first()->id,
                'nationality_id' => Nationality::where('name_en', 'Egyptian')->first()->id,
                'agency_id' => $agency->id,
                'experience_years' => 5,
                'status' => 'Ready',
            ],
            [
                'worker_number' => 'W002',
                'name_en' => 'Fatima Ali',
                'name_ar' => 'فاطمة علي',
                'date_of_birth' => '1992-03-15',

                'profession_id' => Profession::where('name_en', 'Housekeeper')->first()->id,
                'nationality_id' => Nationality::where('name_en', 'Filipino')->first()->id,
                'agency_id' => $agency->id,
                'experience_years' => 3,
                'status' => 'Ready',
            ],
            [
                'worker_number' => 'W003',
                'name_en' => 'Mohammed Khan',
                'name_ar' => 'محمد خان',
                'date_of_birth' => '1988-07-20',

                'profession_id' => Profession::where('name_en', 'Cook')->first()->id,
                'nationality_id' => Nationality::where('name_en', 'Pakistani')->first()->id,
                'agency_id' => $agency->id,
                'experience_years' => 7,
                'status' => 'Ready',
            ],
        ];

        foreach ($workers as $workerData) {
            Worker::create($workerData);
        }

        // Create sample recruitment request
        $request = RecruitmentRequest::create([
            'profession_id' => Profession::where('name_en', 'Housekeeper')->first()->id,
            'nationality_id' => Nationality::where('name_en', 'Filipino')->first()->id,
            'country_id' => $saudiArabia->id,
            'quantity' => 2,
            'sla_days' => 30,
            'requirements' => 'Looking for experienced housekeeper and driver for villa in Riyadh',
            'deadline' => now()->addDays(30),
            'status' => 'Open',
        ]);

        // Create sample proposal
        SupplierProposal::create([
            'request_id' => $request->id,
            'agency_id' => $agency->id,
            'offered_qty' => 2,
            'unit_price' => 3000,
            'lead_time_days' => 15,
            'notes' => 'We have qualified candidates ready for immediate placement',
            'status' => 'Submitted',
        ]);

        // Create sample packages
        $packages = [
            [
                'name_en' => 'Basic Package',
                'name_ar' => 'الباقة الأساسية',
                'description_en' => 'Standard worker placement service',
                'description_ar' => 'خدمة توظيف عمالة عادية',
                'duration' => 'Month',
                'price' => 500,
                'is_active' => true,
            ],
            [
                'name_en' => 'Premium Package',
                'name_ar' => 'الباقة المميزة',
                'description_en' => 'Extended worker placement with guarantee',
                'description_ar' => 'خدمة توظيف عمالة ممتدة مع ضمان',
                'duration' => 'Year',
                'price' => 1200,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $packageData) {
            Package::create($packageData);
        }

        // Create sample worker reservation
        $worker = Worker::first();
        $reservation = WorkerReservation::create([
            'worker_id' => $worker->id,
            'customer_id' => $customer->id,
            'expires_at' => now()->addHours(2),
            'state' => 'AwaitingContract',
        ]);

        // Create sample contract
        $contract = Contract::create([
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
            'package_id' => Package::first()->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(31),
            'total_amount' => 500,
            'original_amount' => 500,
            'status' => 'Active',
        ]);

        // Create sample invoice
        $invoice = Invoice::create([
            'contract_id' => $contract->id,
            'amount' => 575,
            'due_date' => now()->addDays(7),
            'status' => 'Unpaid',
        ]);

        // Create sample payment
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 575,
            'method' => 'bank_transfer',
            'paid_at' => now(),
        ]);
    }
}