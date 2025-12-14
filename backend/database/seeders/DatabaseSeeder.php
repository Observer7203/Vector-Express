<?php

namespace Database\Seeders;

use App\Models\Carrier;
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

        // Seed carriers and pricing data
        $this->call([
            CarrierSeeder::class,
            CarrierPricingSeeder::class,
        ]);

        // Create Observer Logistics carrier (id=8 as expected by ObserverLogisticsSeeder)
        $observerCompany = Company::create([
            'name' => 'Observer Logistics',
            'type' => 'carrier',
            'email' => 'info@observerlogistics.kz',
            'website' => 'https://observerlogistics.kz',
            'inn' => '987654321098',
            'rating' => 4.5,
            'rating_count' => 350,
            'verified' => true,
            'verified_at' => now(),
        ]);

        Carrier::create([
            'id' => 8, // Fixed ID for ObserverLogisticsSeeder rates
            'company_id' => $observerCompany->id,
            'api_type' => 'manual',
            'supported_transport_types' => ['road', 'rail'],
            'supported_countries' => ['Казахстан', 'Россия', 'Китай', 'Узбекистан', 'Кыргызстан', 'Таджикистан', 'Беларусь'],
            'is_active' => true,
        ]);

        // Create carrier user for Observer Logistics
        User::create([
            'name' => 'Observer Carrier',
            'email' => 'carrier@observerlogistics.kz',
            'password' => Hash::make('password'),
            'role' => 'carrier',
            'company_id' => $observerCompany->id,
            'email_verified_at' => now(),
        ]);

        // Seed Observer Logistics zones and pricing
        $this->call([
            ObserverZonesSeeder::class,
            ObserverLogisticsSeeder::class,
        ]);
    }
}
