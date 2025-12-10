<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CarrierSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            [
                'company' => [
                    'name' => 'DHL Express',
                    'type' => 'carrier',
                    'email' => 'info@dhl.com',
                    'website' => 'https://www.dhl.com',
                    'rating' => 4.8,
                    'rating_count' => 1500,
                    'verified' => true,
                    'verified_at' => now(),
                ],
                'carrier' => [
                    'api_type' => 'dhl',
                    'supported_transport_types' => ['air', 'road'],
                    'supported_countries' => [],
                    'is_active' => true,
                ],
            ],
            [
                'company' => [
                    'name' => 'FedEx',
                    'type' => 'carrier',
                    'email' => 'info@fedex.com',
                    'website' => 'https://www.fedex.com',
                    'rating' => 4.7,
                    'rating_count' => 1200,
                    'verified' => true,
                    'verified_at' => now(),
                ],
                'carrier' => [
                    'api_type' => 'fedex',
                    'supported_transport_types' => ['air', 'road'],
                    'supported_countries' => [],
                    'is_active' => true,
                ],
            ],
            [
                'company' => [
                    'name' => 'UPS',
                    'type' => 'carrier',
                    'email' => 'info@ups.com',
                    'website' => 'https://www.ups.com',
                    'rating' => 4.6,
                    'rating_count' => 1100,
                    'verified' => true,
                    'verified_at' => now(),
                ],
                'carrier' => [
                    'api_type' => 'ups',
                    'supported_transport_types' => ['air', 'road'],
                    'supported_countries' => [],
                    'is_active' => true,
                ],
            ],
            [
                'company' => [
                    'name' => 'Ponyexpress',
                    'type' => 'carrier',
                    'email' => 'info@ponyexpress.ru',
                    'website' => 'https://www.ponyexpress.ru',
                    'rating' => 4.3,
                    'rating_count' => 800,
                    'verified' => true,
                    'verified_at' => now(),
                ],
                'carrier' => [
                    'api_type' => 'ponyexpress',
                    'supported_transport_types' => ['air', 'road', 'rail'],
                    'supported_countries' => ['Казахстан', 'Россия', 'Китай', 'Узбекистан', 'Кыргызстан'],
                    'is_active' => true,
                ],
            ],
            [
                'company' => [
                    'name' => 'Maersk',
                    'type' => 'carrier',
                    'email' => 'info@maersk.com',
                    'website' => 'https://www.maersk.com',
                    'rating' => 4.5,
                    'rating_count' => 600,
                    'verified' => true,
                    'verified_at' => now(),
                ],
                'carrier' => [
                    'api_type' => 'mock',
                    'supported_transport_types' => ['sea'],
                    'supported_countries' => [],
                    'is_active' => true,
                ],
            ],
            [
                'company' => [
                    'name' => 'Kazakhstan Railways',
                    'type' => 'carrier',
                    'email' => 'info@railways.kz',
                    'website' => 'https://www.railways.kz',
                    'rating' => 4.2,
                    'rating_count' => 450,
                    'verified' => true,
                    'verified_at' => now(),
                ],
                'carrier' => [
                    'api_type' => 'mock',
                    'supported_transport_types' => ['rail'],
                    'supported_countries' => ['Казахстан', 'Россия', 'Китай'],
                    'is_active' => true,
                ],
            ],
            [
                'company' => [
                    'name' => 'Local Trucking KZ',
                    'type' => 'carrier',
                    'email' => 'info@localtrucking.kz',
                    'website' => 'https://www.localtrucking.kz',
                    'rating' => 4.0,
                    'rating_count' => 200,
                    'verified' => true,
                    'verified_at' => now(),
                ],
                'carrier' => [
                    'api_type' => 'manual',
                    'supported_transport_types' => ['road'],
                    'supported_countries' => ['Казахстан'],
                    'is_active' => true,
                ],
            ],
        ];

        foreach ($carriers as $data) {
            $company = Company::create($data['company']);
            Carrier::create([
                ...$data['carrier'],
                'company_id' => $company->id,
            ]);
        }
    }
}
