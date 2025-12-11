<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarrierTerminal;

/**
 * Сидер для терминалов Observer Logistics
 * НЕ ЗАПУСКАТЬ без необходимости - удалит существующие данные!
 */
class ObserverTerminalsSeeder extends Seeder
{
    public function run(): void
    {
        $carrierId = 8;

        // Удалим старые терминалы
        CarrierTerminal::where('carrier_id', $carrierId)->delete();

        // Терминалы Observer Logistics
        $terminals = [
            // Казахстан - основные хабы
            [
                'terminal_code' => 'ALM-HUB',
                'name' => 'Главный склад Алматы',
                'type' => 'hub',
                'country_code' => 'KZ',
                'city' => 'Алматы',
                'state' => 'Алматы',
                'address' => 'ул. Рыскулова, 212, Алматы, Казахстан',
                'postal_code' => '050000',
                'latitude' => 43.2380,
                'longitude' => 76.9450,
                'service_radius' => 100,
                'working_hours' => json_encode([
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['10:00', '15:00'],
                    'sunday' => null,
                ]),
                'phone' => '+7 727 123 45 67',
                'email' => 'almaty@observer-logistics.kz',
                'is_active' => true,
            ],
            [
                'terminal_code' => 'AST-HUB',
                'name' => 'Склад Астана',
                'type' => 'hub',
                'country_code' => 'KZ',
                'city' => 'Астана',
                'state' => 'Астана',
                'address' => 'пр. Кабанбай батыра, 53, Астана, Казахстан',
                'postal_code' => '010000',
                'latitude' => 51.1694,
                'longitude' => 71.4491,
                'service_radius' => 80,
                'working_hours' => json_encode([
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['10:00', '14:00'],
                    'sunday' => null,
                ]),
                'phone' => '+7 717 234 56 78',
                'email' => 'astana@observer-logistics.kz',
                'is_active' => true,
            ],
            // Казахстан - склады
            [
                'terminal_code' => 'SHY-WH',
                'name' => 'Склад Шымкент',
                'type' => 'warehouse',
                'country_code' => 'KZ',
                'city' => 'Шымкент',
                'state' => 'Туркестанская область',
                'address' => 'ул. Байтурсынова, 15, Шымкент, Казахстан',
                'postal_code' => '160000',
                'latitude' => 42.3417,
                'longitude' => 69.5901,
                'service_radius' => 50,
                'working_hours' => json_encode([
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => null,
                    'sunday' => null,
                ]),
                'phone' => '+7 725 345 67 89',
                'email' => 'shymkent@observer-logistics.kz',
                'is_active' => true,
            ],
            // Казахстан - пункты выдачи
            [
                'terminal_code' => 'AKT-PU',
                'name' => 'Пункт выдачи Актау',
                'type' => 'pickup',
                'country_code' => 'KZ',
                'city' => 'Актау',
                'state' => 'Мангистауская область',
                'address' => 'мкр. 14, дом 5, Актау, Казахстан',
                'postal_code' => '130000',
                'latitude' => 43.6532,
                'longitude' => 51.1605,
                'service_radius' => 30,
                'working_hours' => json_encode([
                    'monday' => ['10:00', '19:00'],
                    'tuesday' => ['10:00', '19:00'],
                    'wednesday' => ['10:00', '19:00'],
                    'thursday' => ['10:00', '19:00'],
                    'friday' => ['10:00', '19:00'],
                    'saturday' => ['10:00', '16:00'],
                    'sunday' => null,
                ]),
                'phone' => '+7 729 456 78 90',
                'email' => 'aktau@observer-logistics.kz',
                'is_active' => true,
            ],
            [
                'terminal_code' => 'KRG-PU',
                'name' => 'Пункт выдачи Караганда',
                'type' => 'pickup',
                'country_code' => 'KZ',
                'city' => 'Караганда',
                'state' => 'Карагандинская область',
                'address' => 'ул. Ерубаева, 44, Караганда, Казахстан',
                'postal_code' => '100000',
                'latitude' => 49.8047,
                'longitude' => 73.0852,
                'service_radius' => 40,
                'working_hours' => json_encode([
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['10:00', '15:00'],
                    'sunday' => null,
                ]),
                'phone' => '+7 721 567 89 01',
                'email' => 'karaganda@observer-logistics.kz',
                'is_active' => true,
            ],
            // Россия - хаб в Москве
            [
                'terminal_code' => 'MOW-HUB',
                'name' => 'Склад Москва',
                'type' => 'hub',
                'country_code' => 'RU',
                'city' => 'Москва',
                'state' => 'Москва',
                'address' => 'ул. Складочная, 1, стр. 18, Москва, Россия',
                'postal_code' => '127018',
                'latitude' => 55.8054,
                'longitude' => 37.5886,
                'service_radius' => 100,
                'working_hours' => json_encode([
                    'monday' => ['09:00', '21:00'],
                    'tuesday' => ['09:00', '21:00'],
                    'wednesday' => ['09:00', '21:00'],
                    'thursday' => ['09:00', '21:00'],
                    'friday' => ['09:00', '21:00'],
                    'saturday' => ['10:00', '18:00'],
                    'sunday' => ['10:00', '16:00'],
                ]),
                'phone' => '+7 495 678 90 12',
                'email' => 'moscow@observer-logistics.ru',
                'is_active' => true,
            ],
            // Китай - хаб в Урумчи
            [
                'terminal_code' => 'URC-HUB',
                'name' => 'Склад Урумчи',
                'type' => 'hub',
                'country_code' => 'CN',
                'city' => 'Урумчи',
                'state' => 'Синьцзян',
                'address' => 'Tianshan District, Urumqi, Xinjiang, China',
                'postal_code' => '830000',
                'latitude' => 43.8256,
                'longitude' => 87.6168,
                'service_radius' => 150,
                'working_hours' => json_encode([
                    'monday' => ['08:00', '20:00'],
                    'tuesday' => ['08:00', '20:00'],
                    'wednesday' => ['08:00', '20:00'],
                    'thursday' => ['08:00', '20:00'],
                    'friday' => ['08:00', '20:00'],
                    'saturday' => ['09:00', '17:00'],
                    'sunday' => null,
                ]),
                'phone' => '+86 991 123 4567',
                'email' => 'urumqi@observer-logistics.cn',
                'is_active' => true,
            ],
            // Китай - склад в Иу
            [
                'terminal_code' => 'YIW-WH',
                'name' => 'Склад Иу',
                'type' => 'warehouse',
                'country_code' => 'CN',
                'city' => 'Иу',
                'state' => 'Чжэцзян',
                'address' => 'International Trade City, Yiwu, Zhejiang, China',
                'postal_code' => '322000',
                'latitude' => 29.3065,
                'longitude' => 120.0750,
                'service_radius' => 80,
                'working_hours' => json_encode([
                    'monday' => ['08:30', '18:30'],
                    'tuesday' => ['08:30', '18:30'],
                    'wednesday' => ['08:30', '18:30'],
                    'thursday' => ['08:30', '18:30'],
                    'friday' => ['08:30', '18:30'],
                    'saturday' => ['09:00', '16:00'],
                    'sunday' => null,
                ]),
                'phone' => '+86 579 234 5678',
                'email' => 'yiwu@observer-logistics.cn',
                'is_active' => true,
            ],
            // Узбекистан - хаб в Ташкенте
            [
                'terminal_code' => 'TAS-HUB',
                'name' => 'Склад Ташкент',
                'type' => 'hub',
                'country_code' => 'UZ',
                'city' => 'Ташкент',
                'state' => 'Ташкент',
                'address' => 'ул. Навои, 100, Ташкент, Узбекистан',
                'postal_code' => '100000',
                'latitude' => 41.2995,
                'longitude' => 69.2401,
                'service_radius' => 70,
                'working_hours' => json_encode([
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['10:00', '15:00'],
                    'sunday' => null,
                ]),
                'phone' => '+998 71 234 56 78',
                'email' => 'tashkent@observer-logistics.uz',
                'is_active' => true,
            ],
            // Кыргызстан - хаб в Бишкеке
            [
                'terminal_code' => 'FRU-HUB',
                'name' => 'Склад Бишкек',
                'type' => 'hub',
                'country_code' => 'KG',
                'city' => 'Бишкек',
                'state' => 'Бишкек',
                'address' => 'ул. Жибек Жолу, 555, Бишкек, Кыргызстан',
                'postal_code' => '720000',
                'latitude' => 42.8746,
                'longitude' => 74.5698,
                'service_radius' => 50,
                'working_hours' => json_encode([
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['10:00', '14:00'],
                    'sunday' => null,
                ]),
                'phone' => '+996 312 34 56 78',
                'email' => 'bishkek@observer-logistics.kg',
                'is_active' => true,
            ],
        ];

        foreach ($terminals as $terminal) {
            CarrierTerminal::create([
                'carrier_id' => $carrierId,
                ...$terminal,
            ]);
        }

        $this->command->info("Created " . count($terminals) . " terminals");
    }
}
