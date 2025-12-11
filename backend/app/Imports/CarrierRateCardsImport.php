<?php

namespace App\Imports;

use App\Models\CarrierRateCard;
use App\Models\CarrierZone;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CarrierRateCardsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    private int $carrierId;
    private int $imported = 0;
    private int $skipped = 0;
    private array $zoneMap = [];

    public function __construct(int $carrierId)
    {
        $this->carrierId = $carrierId;
        $this->loadZoneMap();
    }

    private function loadZoneMap(): void
    {
        $zones = CarrierZone::where('carrier_id', $this->carrierId)->get();
        foreach ($zones as $zone) {
            $this->zoneMap[$zone->zone_code] = $zone->id;
        }
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Пропускаем пустые строки
            if (empty($row['origin_zone']) && empty($row['destination_zone'])) {
                $this->skipped++;
                continue;
            }

            // Находим ID зон по кодам
            $originZoneId = $this->zoneMap[$row['origin_zone']] ?? null;
            $destZoneId = $this->zoneMap[$row['destination_zone']] ?? null;

            if (!$originZoneId || !$destZoneId) {
                $this->skipped++;
                continue;
            }

            // Проверяем существует ли такой тариф
            $existing = CarrierRateCard::where('carrier_id', $this->carrierId)
                ->where('origin_zone_id', $originZoneId)
                ->where('destination_zone_id', $destZoneId)
                ->where('transport_type', $row['transport_type'] ?? 'road')
                ->where('min_weight', $row['min_weight'] ?? 0)
                ->first();

            $data = [
                'carrier_id' => $this->carrierId,
                'origin_zone_id' => $originZoneId,
                'destination_zone_id' => $destZoneId,
                'transport_type' => $row['transport_type'] ?? 'road',
                'min_weight' => $row['min_weight'] ?? 0,
                'max_weight' => $row['max_weight'] ?: null,
                'rate' => $row['rate'] ?? 0,
                'rate_unit' => $row['rate_unit'] ?? 'per_kg',
                'currency' => $row['currency'] ?? 'USD',
                'transit_days_min' => $row['transit_days_min'] ?? null,
                'transit_days_max' => $row['transit_days_max'] ?? null,
            ];

            if ($existing) {
                $existing->update($data);
                $this->skipped++;
            } else {
                CarrierRateCard::create($data);
                $this->imported++;
            }
        }
    }

    public function rules(): array
    {
        return [
            'origin_zone' => 'required|string|max:10',
            'destination_zone' => 'required|string|max:10',
            'transport_type' => 'nullable|in:air,road,rail,sea',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'rate_unit' => 'nullable|in:per_kg,per_lb,per_100kg,flat',
            'currency' => 'nullable|string|max:3',
            'transit_days_min' => 'nullable|integer|min:1',
            'transit_days_max' => 'nullable|integer|min:1',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }
}
