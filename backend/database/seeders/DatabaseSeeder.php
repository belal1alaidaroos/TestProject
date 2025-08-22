<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemSettingsSeeder::class,
            LookupsSeeder::class,
            RolesAndPermissionsSeeder::class,
            SampleDataSeeder::class,
        ]);
    }
}