<?php

namespace App\Imports;

use App\Models\CarrierZone;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CarrierZonesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    private int $carrierId;
    private int $imported = 0;
    private int $skipped = 0;

    public function __construct(int $carrierId)
    {
        $this->carrierId = $carrierId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Пропускаем пустые строки
            if (empty($row['zone_code']) && empty($row['country_code'])) {
                $this->skipped++;
                continue;
            }

            // Проверяем существует ли уже такая зона
            $existing = CarrierZone::where('carrier_id', $this->carrierId)
                ->where('zone_code', $row['zone_code'])
                ->first();

            if ($existing) {
                // Обновляем существующую
                $existing->update([
                    'zone_name' => $row['zone_name'] ?? $existing->zone_name,
                    'country_code' => $row['country_code'] ?? $existing->country_code,
                    'description' => $row['description'] ?? $existing->description,
                ]);
                $this->skipped++;
            } else {
                // Создаём новую
                CarrierZone::create([
                    'carrier_id' => $this->carrierId,
                    'zone_code' => $row['zone_code'],
                    'zone_name' => $row['zone_name'] ?? $row['zone_code'],
                    'country_code' => $row['country_code'] ?? substr($row['zone_code'], 0, 2),
                    'description' => $row['description'] ?? null,
                ]);
                $this->imported++;
            }
        }
    }

    public function rules(): array
    {
        return [
            'zone_code' => 'required|string|max:10',
            'zone_name' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|max:2',
            'description' => 'nullable|string|max:500',
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
