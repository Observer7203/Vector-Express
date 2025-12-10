<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@vectorexpress.kz',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create test customer
        $customerCompany = Company::create([
            'name' => 'Test Company KZ',
            'type' => 'shipper',
            'inn' => '123456789012',
            'verified' => true,
            'verified_at' => now(),
        ]);

        User::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'company_id' => $customerCompany->id,
            'email_verified_at' => now(),
        ]);

        // Seed carriers
        $this->call([
            CarrierSeeder::class,
        ]);
    }
}
