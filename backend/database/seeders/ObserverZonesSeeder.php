<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarrierZone;
use App\Models\CarrierZonePostalCode;

/**
 * Сидер для зон и городов Observer Logistics
 * НЕ ЗАПУСКАТЬ без необходимости - удалит существующие данные!
 */
class ObserverZonesSeeder extends Seeder
{
    public function run(): void
    {
        $carrierId = 8;

        // Удалим старые зоны (каскадно удалятся postal_codes)
        CarrierZone::where('carrier_id', $carrierId)->delete();

        // Зоны доставки
        $zones = [
            [
                'zone_code' => 'KZ',
                'zone_name' => 'Казахстан',
                'country_code' => 'KZ',
                'description' => 'Внутренние перевозки по Казахстану',
            ],
            [
                'zone_code' => 'RU',
                'zone_name' => 'Россия',
                'country_code' => 'RU',
                'description' => 'Перевозки в Россию и из России',
            ],
            [
                'zone_code' => 'UZ',
                'zone_name' => 'Узбекистан',
                'country_code' => 'UZ',
                'description' => 'Перевозки в Узбекистан',
            ],
            [
                'zone_code' => 'KG',
                'zone_name' => 'Кыргызстан',
                'country_code' => 'KG',
                'description' => 'Перевозки в Кыргызстан',
            ],
            [
                'zone_code' => 'TJ',
                'zone_name' => 'Таджикистан',
                'country_code' => 'TJ',
                'description' => 'Перевозки в Таджикистан',
            ],
            [
                'zone_code' => 'BY',
                'zone_name' => 'Беларусь',
                'country_code' => 'BY',
                'description' => 'Перевозки в Беларусь',
            ],
            [
                'zone_code' => 'CN',
                'zone_name' => 'Китай',
                'country_code' => 'CN',
                'description' => 'Перевозки из Китая (карго)',
            ],
        ];

        $createdZones = [];
        foreach ($zones as $zone) {
            $createdZone = CarrierZone::create([
                'carrier_id' => $carrierId,
                ...$zone,
            ]);
            $createdZones[$zone['zone_code']] = $createdZone->id;
        }

        $this->command->info("Created " . count($zones) . " zones");

        // Города и почтовые коды
        $postalCodes = [
            // Казахстан - 12 городов
            'KZ' => [
                ['postal_code_prefix' => '050', 'city' => 'Алматы', 'region' => 'Алматы', 'is_remote_area' => false],
                ['postal_code_prefix' => '010', 'city' => 'Астана', 'region' => 'Астана', 'is_remote_area' => false],
                ['postal_code_prefix' => '160', 'city' => 'Шымкент', 'region' => 'Туркестанская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '100', 'city' => 'Караганда', 'region' => 'Карагандинская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '130', 'city' => 'Актобе', 'region' => 'Актюбинская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '150', 'city' => 'Тараз', 'region' => 'Жамбылская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '070', 'city' => 'Усть-Каменогорск', 'region' => 'ВКО', 'is_remote_area' => false],
                ['postal_code_prefix' => '110', 'city' => 'Костанай', 'region' => 'Костанайская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '020', 'city' => 'Кокшетау', 'region' => 'Акмолинская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '090', 'city' => 'Уральск', 'region' => 'ЗКО', 'is_remote_area' => false],
                ['postal_code_prefix' => '060', 'city' => 'Атырау', 'region' => 'Атырауская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '030', 'city' => 'Актау', 'region' => 'Мангистауская область', 'is_remote_area' => true],
            ],
            // Россия - 12 городов
            'RU' => [
                ['postal_code_prefix' => '101', 'city' => 'Москва', 'region' => 'Москва', 'is_remote_area' => false],
                ['postal_code_prefix' => '190', 'city' => 'Санкт-Петербург', 'region' => 'Санкт-Петербург', 'is_remote_area' => false],
                ['postal_code_prefix' => '630', 'city' => 'Новосибирск', 'region' => 'Новосибирская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '620', 'city' => 'Екатеринбург', 'region' => 'Свердловская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '420', 'city' => 'Казань', 'region' => 'Татарстан', 'is_remote_area' => false],
                ['postal_code_prefix' => '603', 'city' => 'Нижний Новгород', 'region' => 'Нижегородская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '454', 'city' => 'Челябинск', 'region' => 'Челябинская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '644', 'city' => 'Омск', 'region' => 'Омская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '443', 'city' => 'Самара', 'region' => 'Самарская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '344', 'city' => 'Ростов-на-Дону', 'region' => 'Ростовская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '450', 'city' => 'Уфа', 'region' => 'Башкортостан', 'is_remote_area' => false],
                ['postal_code_prefix' => '660', 'city' => 'Красноярск', 'region' => 'Красноярский край', 'is_remote_area' => false],
            ],
            // Узбекистан - 7 городов
            'UZ' => [
                ['postal_code_prefix' => '100', 'city' => 'Ташкент', 'region' => 'Ташкент', 'is_remote_area' => false],
                ['postal_code_prefix' => '140', 'city' => 'Самарканд', 'region' => 'Самаркандская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '150', 'city' => 'Бухара', 'region' => 'Бухарская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '120', 'city' => 'Наманган', 'region' => 'Наманганская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '130', 'city' => 'Андижан', 'region' => 'Андижанская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '210', 'city' => 'Нукус', 'region' => 'Каракалпакстан', 'is_remote_area' => true],
                ['postal_code_prefix' => '110', 'city' => 'Фергана', 'region' => 'Ферганская область', 'is_remote_area' => false],
            ],
            // Кыргызстан - 5 городов
            'KG' => [
                ['postal_code_prefix' => '720', 'city' => 'Бишкек', 'region' => 'Бишкек', 'is_remote_area' => false],
                ['postal_code_prefix' => '723', 'city' => 'Ош', 'region' => 'Ошская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '724', 'city' => 'Джалал-Абад', 'region' => 'Джалал-Абадская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '722', 'city' => 'Каракол', 'region' => 'Иссык-Кульская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '725', 'city' => 'Токмок', 'region' => 'Чуйская область', 'is_remote_area' => false],
            ],
            // Таджикистан - 5 городов
            'TJ' => [
                ['postal_code_prefix' => '734', 'city' => 'Душанбе', 'region' => 'Душанбе', 'is_remote_area' => false],
                ['postal_code_prefix' => '735', 'city' => 'Худжанд', 'region' => 'Согдийская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '736', 'city' => 'Куляб', 'region' => 'Хатлонская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '735', 'city' => 'Бохтар', 'region' => 'Хатлонская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '736', 'city' => 'Истаравшан', 'region' => 'Согдийская область', 'is_remote_area' => false],
            ],
            // Беларусь - 6 городов
            'BY' => [
                ['postal_code_prefix' => '220', 'city' => 'Минск', 'region' => 'Минск', 'is_remote_area' => false],
                ['postal_code_prefix' => '230', 'city' => 'Гродно', 'region' => 'Гродненская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '246', 'city' => 'Гомель', 'region' => 'Гомельская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '212', 'city' => 'Могилев', 'region' => 'Могилевская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '210', 'city' => 'Витебск', 'region' => 'Витебская область', 'is_remote_area' => false],
                ['postal_code_prefix' => '224', 'city' => 'Брест', 'region' => 'Брестская область', 'is_remote_area' => false],
            ],
            // Китай - 10 городов
            'CN' => [
                ['postal_code_prefix' => '100', 'city' => 'Пекин', 'region' => 'Пекин', 'is_remote_area' => false],
                ['postal_code_prefix' => '200', 'city' => 'Шанхай', 'region' => 'Шанхай', 'is_remote_area' => false],
                ['postal_code_prefix' => '510', 'city' => 'Гуанчжоу', 'region' => 'Гуандун', 'is_remote_area' => false],
                ['postal_code_prefix' => '518', 'city' => 'Шэньчжэнь', 'region' => 'Гуандун', 'is_remote_area' => false],
                ['postal_code_prefix' => '310', 'city' => 'Ханчжоу', 'region' => 'Чжэцзян', 'is_remote_area' => false],
                ['postal_code_prefix' => '610', 'city' => 'Чэнду', 'region' => 'Сычуань', 'is_remote_area' => false],
                ['postal_code_prefix' => '400', 'city' => 'Чунцин', 'region' => 'Чунцин', 'is_remote_area' => false],
                ['postal_code_prefix' => '830', 'city' => 'Урумчи', 'region' => 'Синьцзян', 'is_remote_area' => false],
                ['postal_code_prefix' => '150', 'city' => 'Харбин', 'region' => 'Хэйлунцзян', 'is_remote_area' => false],
                ['postal_code_prefix' => '322', 'city' => 'Иу', 'region' => 'Чжэцзян', 'is_remote_area' => false],
            ],
        ];

        $totalCities = 0;
        foreach ($postalCodes as $zoneCode => $cities) {
            $zoneId = $createdZones[$zoneCode] ?? null;
            if (!$zoneId) continue;

            foreach ($cities as $city) {
                CarrierZonePostalCode::create([
                    'carrier_zone_id' => $zoneId,
                    ...$city,
                ]);
                $totalCities++;
            }
        }

        $this->command->info("Created {$totalCities} cities/postal codes");
    }
}
