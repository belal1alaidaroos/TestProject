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

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample countries
        $saudiArabia = Country::create([
            'name' => 'Saudi Arabia',
            'code' => 'SA',
            'phone_code' => '+966',
            'is_active' => true,
        ]);

        // Create sample cities
        $riyadh = City::create([
            'name' => 'Riyadh',
            'country_id' => $saudiArabia->id,
            'is_active' => true,
        ]);

        $jeddah = City::create([
            'name' => 'Jeddah',
            'country_id' => $saudiArabia->id,
            'is_active' => true,
        ]);

        // Create sample districts
        $olaya = District::create([
            'name' => 'Olaya',
            'city_id' => $riyadh->id,
            'is_active' => true,
        ]);

        $malaz = District::create([
            'name' => 'Malaz',
            'city_id' => $riyadh->id,
            'is_active' => true,
        ]);

        // Create sample professions
        $professions = [
            'Housekeeper' => 'Domestic cleaning and housekeeping services',
            'Driver' => 'Personal and commercial driving services',
            'Cook' => 'Food preparation and cooking services',
            'Nanny' => 'Childcare and babysitting services',
            'Security Guard' => 'Security and protection services',
            'Gardener' => 'Landscaping and gardening services',
        ];

        foreach ($professions as $name => $description) {
            Profession::create([
                'name' => $name,
                'description' => $description,
                'is_active' => true,
            ]);
        }

        // Create sample nationalities
        $nationalities = [
            'Saudi' => 'Saudi Arabian',
            'Egyptian' => 'Egyptian',
            'Filipino' => 'Filipino',
            'Indian' => 'Indian',
            'Pakistani' => 'Pakistani',
            'Bangladeshi' => 'Bangladeshi',
        ];

        foreach ($nationalities as $name => $description) {
            Nationality::create([
                'name' => $name,
                'description' => $description,
                'is_active' => true,
            ]);
        }

        // Create sample customer
        $customer = Customer::create([
            'name' => 'Test Customer Company',
            'email' => 'customer@example.com',
            'phone' => '+966507654321',
            'tax_number' => '123456789',
            'is_active' => true,
        ]);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_line1' => '123 Test Street',
            'address_line2' => 'Building A, Floor 2',
            'city_id' => $riyadh->id,
            'district_id' => $olaya->id,
            'postal_code' => '12345',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Create sample agency
        $agency = Agency::create([
            'name' => 'Test Recruitment Agency',
            'email' => 'agency@example.com',
            'phone' => '+966509876543',
            'license_number' => 'AG123456',
            'is_active' => true,
        ]);

        // Create sample workers
        $workers = [
            [
                'name' => 'Ahmed Hassan',
                'phone' => '+966501111111',
                'email' => 'ahmed@example.com',
                'profession_id' => Profession::where('name', 'Driver')->first()->id,
                'nationality_id' => Nationality::where('name', 'Egyptian')->first()->id,
                'experience_years' => 5,
                'salary_expectation' => 3000,
                'is_available' => true,
                'stage' => 'Available',
            ],
            [
                'name' => 'Fatima Ali',
                'phone' => '+966502222222',
                'email' => 'fatima@example.com',
                'profession_id' => Profession::where('name', 'Housekeeper')->first()->id,
                'nationality_id' => Nationality::where('name', 'Filipino')->first()->id,
                'experience_years' => 3,
                'salary_expectation' => 2500,
                'is_available' => true,
                'stage' => 'Available',
            ],
            [
                'name' => 'Mohammed Khan',
                'phone' => '+966503333333',
                'email' => 'mohammed@example.com',
                'profession_id' => Profession::where('name', 'Cook')->first()->id,
                'nationality_id' => Nationality::where('name', 'Pakistani')->first()->id,
                'experience_years' => 7,
                'salary_expectation' => 3500,
                'is_available' => true,
                'stage' => 'Available',
            ],
        ];

        foreach ($workers as $workerData) {
            Worker::create($workerData);
        }

        // Create sample recruitment request
        $request = RecruitmentRequest::create([
            'title' => 'Household Staff Needed',
            'description' => 'Looking for experienced housekeeper and driver for villa in Riyadh',
            'profession_id' => Profession::where('name', 'Housekeeper')->first()->id,
            'quantity_needed' => 2,
            'salary_range_min' => 2500,
            'salary_range_max' => 3500,
            'deadline' => now()->addDays(30),
            'status' => 'Open',
            'is_active' => true,
        ]);

        // Create sample proposal
        SupplierProposal::create([
            'recruitment_request_id' => $request->id,
            'agency_id' => $agency->id,
            'proposed_workers' => 2,
            'proposed_salary' => 3000,
            'delivery_time_days' => 15,
            'notes' => 'We have qualified candidates ready for immediate placement',
            'status' => 'Submitted',
            'is_active' => true,
        ]);

        // Create sample packages
        $packages = [
            [
                'name' => 'Basic Package',
                'description' => 'Standard worker placement service',
                'duration_days' => 30,
                'price' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'Premium Package',
                'description' => 'Extended worker placement with guarantee',
                'duration_days' => 90,
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
            'reserved_until' => now()->addHours(2),
            'status' => 'Reserved',
            'is_active' => true,
        ]);

        // Create sample contract
        $contract = Contract::create([
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
            'package_id' => Package::first()->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(31),
            'total_amount' => 500,
            'status' => 'Active',
            'is_active' => true,
        ]);

        // Create sample invoice
        Invoice::create([
            'contract_id' => $contract->id,
            'invoice_number' => 'INV-2024-001',
            'amount' => 500,
            'tax_amount' => 75,
            'total_amount' => 575,
            'due_date' => now()->addDays(7),
            'status' => 'Pending',
            'is_active' => true,
        ]);

        // Create sample payment
        Payment::create([
            'contract_id' => $contract->id,
            'amount' => 575,
            'payment_method' => 'Bank Transfer',
            'transaction_id' => 'TXN-2024-001',
            'status' => 'Completed',
            'is_active' => true,
        ]);
    }
}