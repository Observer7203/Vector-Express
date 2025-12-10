<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\CarrierPricingRule;
use App\Models\CarrierRateCard;
use App\Models\CarrierSurcharge;
use App\Models\CarrierTerminal;
use App\Models\CarrierZone;
use App\Models\CarrierZonePostalCode;
use Illuminate\Database\Seeder;

class CarrierPricingSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = Carrier::with('company')->get();

        foreach ($carriers as $carrier) {
            $this->createPricingRule($carrier);
            $zones = $this->createZones($carrier);
            $this->createRateCards($carrier, $zones);
            $this->createSurcharges($carrier);
            $this->createTerminals($carrier);
        }
    }

    private function createPricingRule(Carrier $carrier): void
    {
        // DIM factors vary by carrier
        $dimFactors = [
            'dhl' => 5000,
            'fedex' => 6000,
            'ups' => 5000,
            'ponyexpress' => 5000,
            'mock' => 5000,
            'manual' => 5000,
        ];

        $minCharges = [
            'dhl' => 50,
            'fedex' => 45,
            'ups' => 48,
            'ponyexpress' => 25,
            'mock' => 30,
            'manual' => 20,
        ];

        CarrierPricingRule::create([
            'carrier_id' => $carrier->id,
            'pricing_type' => 'zone',
            'dim_factor' => $dimFactors[$carrier->api_type] ?? 5000,
            'minimum_charge' => $minCharges[$carrier->api_type] ?? 30,
            'insurance_rate' => 0.5, // 0.5% of declared value
            'currency' => 'USD',
            'effective_from' => now()->startOfYear(),
            'effective_until' => now()->endOfYear(),
        ]);
    }

    private function createZones(Carrier $carrier): array
    {
        $zones = [];

        // Create zones for main countries
        $countryZones = [
            ['code' => 'KZ', 'name' => 'Kazakhstan', 'zone_code' => 'Z1'],
            ['code' => 'RU', 'name' => 'Russia', 'zone_code' => 'Z2'],
            ['code' => 'CN', 'name' => 'China', 'zone_code' => 'Z3'],
            ['code' => 'UZ', 'name' => 'Uzbekistan', 'zone_code' => 'Z4'],
            ['code' => 'KG', 'name' => 'Kyrgyzstan', 'zone_code' => 'Z5'],
            ['code' => 'DE', 'name' => 'Germany', 'zone_code' => 'Z6'],
            ['code' => 'US', 'name' => 'United States', 'zone_code' => 'Z7'],
            ['code' => 'TR', 'name' => 'Turkey', 'zone_code' => 'Z8'],
        ];

        foreach ($countryZones as $cz) {
            $zone = CarrierZone::create([
                'carrier_id' => $carrier->id,
                'zone_code' => $cz['zone_code'],
                'zone_name' => $cz['name'],
                'country_code' => $cz['code'],
                'description' => "Zone for {$cz['name']}",
            ]);
            $zones[$cz['code']] = $zone;

            // Add postal codes for Kazakhstan
            if ($cz['code'] === 'KZ') {
                $this->createKazakhstanPostalCodes($zone);
            }
        }

        return $zones;
    }

    private function createKazakhstanPostalCodes(CarrierZone $zone): void
    {
        $cities = [
            ['prefix' => '050', 'city' => 'Almaty', 'remote' => false],
            ['prefix' => '010', 'city' => 'Astana', 'remote' => false],
            ['prefix' => '160', 'city' => 'Shymkent', 'remote' => false],
            ['prefix' => '100', 'city' => 'Karaganda', 'remote' => false],
            ['prefix' => '090', 'city' => 'Aktobe', 'remote' => false],
            ['prefix' => '060', 'city' => 'Atyrau', 'remote' => false],
            ['prefix' => '110', 'city' => 'Kostanay', 'remote' => false],
            ['prefix' => '150', 'city' => 'Turkestan', 'remote' => true],
            ['prefix' => '070', 'city' => 'Uralsk', 'remote' => true],
            ['prefix' => '040', 'city' => 'Semey', 'remote' => true],
        ];

        foreach ($cities as $city) {
            CarrierZonePostalCode::create([
                'carrier_zone_id' => $zone->id,
                'postal_code_prefix' => $city['prefix'],
                'city' => $city['city'],
                'region' => $city['city'] . ' region',
                'is_remote_area' => $city['remote'],
            ]);
        }
    }

    private function createRateCards(Carrier $carrier, array $zones): void
    {
        // Base rates per kg by transport type
        $baseRates = [
            'air' => ['rate' => 12, 'min_days' => 3, 'max_days' => 7],
            'road' => ['rate' => 4, 'min_days' => 7, 'max_days' => 14],
            'rail' => ['rate' => 3, 'min_days' => 15, 'max_days' => 25],
            'sea' => ['rate' => 1.5, 'min_days' => 30, 'max_days' => 45],
        ];

        // Weight breaks
        $weightBreaks = [
            ['min' => 0, 'max' => 1, 'multiplier' => 1.5],
            ['min' => 1, 'max' => 5, 'multiplier' => 1.3],
            ['min' => 5, 'max' => 20, 'multiplier' => 1.1],
            ['min' => 20, 'max' => 100, 'multiplier' => 1.0],
            ['min' => 100, 'max' => 500, 'multiplier' => 0.9],
            ['min' => 500, 'max' => null, 'multiplier' => 0.8],
        ];

        // Zone multipliers (distance factor)
        $zoneMultipliers = [
            'Z1' => ['Z1' => 1.0, 'Z2' => 1.2, 'Z3' => 1.3, 'Z4' => 1.1, 'Z5' => 1.1, 'Z6' => 1.8, 'Z7' => 2.2, 'Z8' => 1.5],
            'Z2' => ['Z1' => 1.2, 'Z2' => 1.0, 'Z3' => 1.2, 'Z4' => 1.3, 'Z5' => 1.3, 'Z6' => 1.5, 'Z7' => 2.0, 'Z8' => 1.4],
            'Z3' => ['Z1' => 1.3, 'Z2' => 1.2, 'Z3' => 1.0, 'Z4' => 1.5, 'Z5' => 1.4, 'Z6' => 1.8, 'Z7' => 2.0, 'Z8' => 1.6],
        ];

        foreach ($carrier->supported_transport_types ?? [] as $transportType) {
            if (!isset($baseRates[$transportType])) continue;

            $rateInfo = $baseRates[$transportType];

            // Create rate cards for each origin-destination zone pair
            foreach ($zones as $originCode => $originZone) {
                foreach ($zones as $destCode => $destZone) {
                    $zoneMultiplier = $zoneMultipliers[$originZone->zone_code][$destZone->zone_code] ?? 1.5;

                    foreach ($weightBreaks as $wb) {
                        $ratePerKg = $rateInfo['rate'] * $zoneMultiplier * $wb['multiplier'];

                        // Carrier-specific rate adjustments
                        $carrierMultiplier = match ($carrier->api_type) {
                            'dhl' => 1.2,
                            'fedex' => 1.15,
                            'ups' => 1.1,
                            'ponyexpress' => 0.85,
                            default => 1.0,
                        };

                        CarrierRateCard::create([
                            'carrier_id' => $carrier->id,
                            'origin_zone_id' => $originZone->id,
                            'destination_zone_id' => $destZone->id,
                            'transport_type' => $transportType,
                            'weight_min' => $wb['min'],
                            'weight_max' => $wb['max'],
                            'rate_per_kg' => round($ratePerKg * $carrierMultiplier, 4),
                            'currency' => 'USD',
                            'transit_days_min' => $rateInfo['min_days'],
                            'transit_days_max' => $rateInfo['max_days'],
                            'effective_from' => now()->startOfYear(),
                            'effective_until' => now()->endOfYear(),
                        ]);
                    }
                }
            }
        }
    }

    private function createSurcharges(Carrier $carrier): void
    {
        $surcharges = [
            [
                'surcharge_type' => 'fuel',
                'name' => 'Fuel Surcharge',
                'calculation_type' => 'percentage',
                'value' => 15.5, // 15.5% of base rate
            ],
            [
                'surcharge_type' => 'residential',
                'name' => 'Residential Delivery',
                'calculation_type' => 'flat',
                'value' => 8.00,
            ],
            [
                'surcharge_type' => 'remote_area',
                'name' => 'Remote Area Surcharge',
                'calculation_type' => 'flat',
                'value' => 25.00,
            ],
            [
                'surcharge_type' => 'oversize',
                'name' => 'Oversize Package',
                'calculation_type' => 'flat',
                'value' => 50.00,
            ],
            [
                'surcharge_type' => 'dangerous_goods',
                'name' => 'Dangerous Goods Handling',
                'calculation_type' => 'flat',
                'value' => 75.00,
            ],
        ];

        foreach ($surcharges as $surcharge) {
            CarrierSurcharge::create([
                'carrier_id' => $carrier->id,
                ...$surcharge,
                'currency' => 'USD',
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'effective_until' => now()->endOfYear(),
            ]);
        }
    }

    private function createTerminals(Carrier $carrier): void
    {
        $terminals = [
            [
                'terminal_code' => 'ALM-HUB',
                'name' => 'Almaty Hub',
                'type' => 'hub',
                'country_code' => 'KZ',
                'city' => 'Almaty',
                'address' => 'Airport area, Almaty',
                'postal_code' => '050000',
                'latitude' => 43.2567,
                'longitude' => 76.9286,
            ],
            [
                'terminal_code' => 'AST-HUB',
                'name' => 'Astana Hub',
                'type' => 'hub',
                'country_code' => 'KZ',
                'city' => 'Astana',
                'address' => 'Logistics center, Astana',
                'postal_code' => '010000',
                'latitude' => 51.1605,
                'longitude' => 71.4704,
            ],
            [
                'terminal_code' => 'MOW-HUB',
                'name' => 'Moscow Hub',
                'type' => 'hub',
                'country_code' => 'RU',
                'city' => 'Moscow',
                'address' => 'Sheremetyevo Airport',
                'postal_code' => '141400',
                'latitude' => 55.9726,
                'longitude' => 37.4146,
            ],
            [
                'terminal_code' => 'GZ-HUB',
                'name' => 'Guangzhou Hub',
                'type' => 'hub',
                'country_code' => 'CN',
                'city' => 'Guangzhou',
                'address' => 'Baiyun Airport',
                'postal_code' => '510000',
                'latitude' => 23.3924,
                'longitude' => 113.2988,
            ],
        ];

        foreach ($terminals as $terminal) {
            CarrierTerminal::create([
                'carrier_id' => $carrier->id,
                ...$terminal,
                'is_active' => true,
                'working_hours' => [
                    'monday' => '09:00-18:00',
                    'tuesday' => '09:00-18:00',
                    'wednesday' => '09:00-18:00',
                    'thursday' => '09:00-18:00',
                    'friday' => '09:00-18:00',
                    'saturday' => '10:00-15:00',
                    'sunday' => 'closed',
                ],
            ]);
        }
    }
}
