<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarrierRateCard;
use App\Models\CarrierSurcharge;
use App\Models\CarrierPricingRule;

class ObserverLogisticsSeeder extends Seeder
{
    public function run(): void
    {
        $carrierId = 8;
        $zones = ['KZ' => 57, 'RU' => 58, 'UZ' => 59, 'KG' => 60, 'TJ' => 61, 'BY' => 62, 'CN' => 63];

        // Удалим старые данные
        CarrierRateCard::where('carrier_id', $carrierId)->delete();
        CarrierSurcharge::where('carrier_id', $carrierId)->delete();
        CarrierPricingRule::where('carrier_id', $carrierId)->delete();

        // РЕАЛЬНЫЕ ЦЕНЫ на основе исследования рынка СНГ (в USD за кг)
        // Источники: KazTransCargo ($0.5-1.2/кг), GTrans ($0.6/кг), СДЭК, TransKaz ($3/кг)
        $rates = [
            'road' => [
                // Китай-Казахстан (карго): $0.5-3.0/кг, 7-18 дней
                'CN-KZ' => ['rate' => 1.2, 'min' => 10, 'max' => 18],
                'KZ-CN' => ['rate' => 1.5, 'min' => 12, 'max' => 20],

                // Внутри Казахстана: ~$0.3-0.8/кг, 3-7 дней
                'KZ-KZ' => ['rate' => 0.5, 'min' => 3, 'max' => 7],

                // Казахстан-Россия: ~$1.5-3.0/кг, 7-14 дней
                'KZ-RU' => ['rate' => 2.0, 'min' => 7, 'max' => 14],
                'RU-KZ' => ['rate' => 2.2, 'min' => 8, 'max' => 14],

                // Казахстан-Центральная Азия: ~$1.0-2.0/кг, 5-10 дней
                'KZ-UZ' => ['rate' => 1.5, 'min' => 5, 'max' => 10],
                'UZ-KZ' => ['rate' => 1.6, 'min' => 6, 'max' => 10],
                'KZ-KG' => ['rate' => 1.0, 'min' => 3, 'max' => 6],
                'KG-KZ' => ['rate' => 1.1, 'min' => 4, 'max' => 7],
                'KZ-TJ' => ['rate' => 1.8, 'min' => 6, 'max' => 12],
                'TJ-KZ' => ['rate' => 1.9, 'min' => 7, 'max' => 12],

                // Казахстан-Беларусь (через Россию)
                'KZ-BY' => ['rate' => 2.8, 'min' => 12, 'max' => 20],
                'BY-KZ' => ['rate' => 3.0, 'min' => 14, 'max' => 22],
            ],
            'rail' => [
                // Китай-Казахстан ж/д: дешевле, но дольше
                'CN-KZ' => ['rate' => 0.8, 'min' => 18, 'max' => 30],
                'KZ-CN' => ['rate' => 0.9, 'min' => 20, 'max' => 32],

                // Внутри Казахстана ж/д
                'KZ-KZ' => ['rate' => 0.3, 'min' => 7, 'max' => 15],

                // Казахстан-Россия ж/д
                'KZ-RU' => ['rate' => 1.2, 'min' => 14, 'max' => 25],
                'RU-KZ' => ['rate' => 1.3, 'min' => 15, 'max' => 25],

                // Беларусь ж/д
                'KZ-BY' => ['rate' => 1.8, 'min' => 20, 'max' => 35],
                'BY-KZ' => ['rate' => 2.0, 'min' => 22, 'max' => 38],
            ],
        ];

        // Весовые диапазоны с реалистичными скидками
        $weightBreaks = [
            ['min' => 0, 'max' => 20, 'mult' => 1.8],      // мелкие посылки - дороже
            ['min' => 20, 'max' => 100, 'mult' => 1.3],    // средние
            ['min' => 100, 'max' => 500, 'mult' => 1.0],   // базовая ставка
            ['min' => 500, 'max' => 1000, 'mult' => 0.85], // скидка за объём
            ['min' => 1000, 'max' => null, 'mult' => 0.7], // оптовая цена
        ];

        $count = 0;
        foreach ($rates as $transport => $routes) {
            foreach ($routes as $route => $info) {
                [$from, $to] = explode('-', $route);
                $fromZone = $zones[$from] ?? null;
                $toZone = $zones[$to] ?? null;

                if (!$fromZone || !$toZone) continue;

                foreach ($weightBreaks as $wb) {
                    CarrierRateCard::create([
                        'carrier_id' => $carrierId,
                        'origin_zone_id' => $fromZone,
                        'destination_zone_id' => $toZone,
                        'transport_type' => $transport,
                        'min_weight' => $wb['min'],
                        'max_weight' => $wb['max'],
                        'rate' => round($info['rate'] * $wb['mult'], 4),
                        'rate_unit' => 'per_kg',
                        'currency' => 'USD',
                        'transit_days_min' => $info['min'],
                        'transit_days_max' => $info['max'],
                    ]);
                    $count++;
                }
            }
        }

        $this->command->info("Created {$count} rate cards");

        // Надбавки (surcharges)
        $surcharges = [
            [
                'surcharge_type' => 'fuel',
                'name' => 'Топливная надбавка',
                'calculation_type' => 'percentage',
                'value' => 15.0, // 15%
                'applies_to_transport_types' => ['road'],
                'is_active' => true,
            ],
            [
                'surcharge_type' => 'remote_area',
                'name' => 'Удалённый район',
                'calculation_type' => 'flat',
                'value' => 25.0, // $25 фиксированно
                'applies_to_transport_types' => ['road', 'rail'],
                'is_active' => true,
            ],
            [
                'surcharge_type' => 'oversized',
                'name' => 'Негабаритный груз',
                'calculation_type' => 'percentage',
                'value' => 30.0, // +30%
                'applies_to_transport_types' => ['road', 'rail'],
                'is_active' => true,
            ],
            [
                'surcharge_type' => 'dangerous',
                'name' => 'Опасный груз',
                'calculation_type' => 'percentage',
                'value' => 50.0, // +50%
                'applies_to_transport_types' => ['road', 'rail'],
                'is_active' => true,
            ],
            [
                'surcharge_type' => 'customs',
                'name' => 'Таможенное оформление',
                'calculation_type' => 'flat',
                'value' => 50.0, // $50 за оформление
                'applies_to_transport_types' => ['road', 'rail'],
                'is_active' => true,
            ],
        ];

        foreach ($surcharges as $s) {
            CarrierSurcharge::create([
                'carrier_id' => $carrierId,
                ...$s,
            ]);
        }

        $this->command->info("Created " . count($surcharges) . " surcharges");

        // Правило ценообразования
        CarrierPricingRule::create([
            'carrier_id' => $carrierId,
            'pricing_type' => 'zone',
            'minimum_charge' => 15.0, // минимальная стоимость заказа $15
            'currency' => 'USD',
            'dim_factor' => 5000, // стандартный DIM фактор
            'insurance_rate' => 0.5, // 0.5% от объявленной стоимости
            'effective_from' => now(),
            'config' => json_encode([
                'name' => 'Стандартное правило Observer Logistics',
                'weight_breaks' => true,
                'volume_discount' => true,
            ]),
        ]);

        $this->command->info("Created pricing rule");
    }
}
