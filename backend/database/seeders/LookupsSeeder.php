<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupsSeeder extends Seeder
{
    public function run(): void
    {
        // Countries
        $saudiArabiaId = (string) \Illuminate\Support\Str::uuid();
        $uaeId = (string) \Illuminate\Support\Str::uuid();
        $qatarId = (string) \Illuminate\Support\Str::uuid();

        DB::table('countries')->insert([
            [
                'id' => $saudiArabiaId,
                'name_en' => 'Saudi Arabia',
                'name_ar' => 'المملكة العربية السعودية',
                'code' => 'SAU',
                'phone_code' => '+966',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $uaeId,
                'name_en' => 'United Arab Emirates',
                'name_ar' => 'الإمارات العربية المتحدة',
                'code' => 'UAE',
                'phone_code' => '+971',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $qatarId,
                'name_en' => 'Qatar',
                'name_ar' => 'قطر',
                'code' => 'QAT',
                'phone_code' => '+974',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Cities
        $riyadhId = (string) \Illuminate\Support\Str::uuid();
        $jeddahId = (string) \Illuminate\Support\Str::uuid();
        $dammamId = (string) \Illuminate\Support\Str::uuid();

        DB::table('cities')->insert([
            [
                'id' => $riyadhId,
                'country_id' => $saudiArabiaId,
                'name_en' => 'Riyadh',
                'name_ar' => 'الرياض',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $jeddahId,
                'country_id' => $saudiArabiaId,
                'name_en' => 'Jeddah',
                'name_ar' => 'جدة',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $dammamId,
                'country_id' => $saudiArabiaId,
                'name_en' => 'Dammam',
                'name_ar' => 'الدمام',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Districts
        DB::table('districts')->insert([
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'city_id' => $riyadhId,
                'name_en' => 'Al Olaya',
                'name_ar' => 'الحي العليا',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'city_id' => $riyadhId,
                'name_en' => 'Al Malaz',
                'name_ar' => 'الملز',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'city_id' => $jeddahId,
                'name_en' => 'Al Balad',
                'name_ar' => 'البلد',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Nationalities
        DB::table('nationalities')->insert([
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name_en' => 'Filipino',
                'name_ar' => 'فلبيني',
                'code' => 'PHL',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name_en' => 'Indian',
                'name_ar' => 'هندي',
                'code' => 'IND',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name_en' => 'Pakistani',
                'name_ar' => 'باكستاني',
                'code' => 'PAK',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Professions
        DB::table('professions')->insert([
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name_en' => 'Domestic Worker',
                'name_ar' => 'عامل منزلي',
                'description_en' => 'Household cleaning and maintenance',
                'description_ar' => 'تنظيف وصيانة المنزل',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name_en' => 'Driver',
                'name_ar' => 'سائق',
                'description_en' => 'Vehicle driving and transportation',
                'description_ar' => 'قيادة المركبات والنقل',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name_en' => 'Cook',
                'name_ar' => 'طباخ',
                'description_en' => 'Food preparation and cooking',
                'description_ar' => 'تحضير وطهي الطعام',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Packages
        DB::table('packages')->insert([
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name_en' => 'Monthly Package',
                'name_ar' => 'الباقة الشهرية',
                'duration' => 'Month',
                'price' => 1500.00,
                'description_en' => 'One month worker contract',
                'description_ar' => 'عقد عامل لمدة شهر',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name_en' => 'Yearly Package',
                'name_ar' => 'الباقة السنوية',
                'duration' => 'Year',
                'price' => 15000.00,
                'description_en' => 'One year worker contract',
                'description_ar' => 'عقد عامل لمدة سنة',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}