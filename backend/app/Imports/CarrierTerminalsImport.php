<?php

namespace App\Imports;

use App\Models\CarrierTerminal;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CarrierTerminalsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError
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
            if (empty($row['name']) && empty($row['city'])) {
                $this->skipped++;
                continue;
            }

            // Проверяем существует ли такой терминал
            $existing = null;
            if (!empty($row['terminal_code'])) {
                $existing = CarrierTerminal::where('carrier_id', $this->carrierId)
                    ->where('terminal_code', $row['terminal_code'])
                    ->first();
            }

            $data = [
                'carrier_id' => $this->carrierId,
                'terminal_code' => $row['terminal_code'] ?? null,
                'name' => $row['name'],
                'type' => $row['type'] ?? 'hub',
                'country_code' => $row['country_code'] ?? 'KZ',
                'city' => $row['city'],
                'state' => $row['state'] ?? null,
                'address' => $row['address'] ?? null,
                'postal_code' => $row['postal_code'] ?? null,
                'latitude' => !empty($row['latitude']) ? (float)$row['latitude'] : null,
                'longitude' => !empty($row['longitude']) ? (float)$row['longitude'] : null,
                'service_radius' => !empty($row['service_radius']) ? (int)$row['service_radius'] : null,
                'phone' => $row['phone'] ?? null,
                'email' => $row['email'] ?? null,
                'is_active' => isset($row['is_active']) ? (bool)$row['is_active'] : true,
            ];

            // Парсим рабочие часы если есть
            if (!empty($row['working_hours'])) {
                $data['working_hours'] = $this->parseWorkingHours($row['working_hours']);
            }

            if ($existing) {
                $existing->update($data);
                $this->skipped++;
            } else {
                CarrierTerminal::create($data);
                $this->imported++;
            }
        }
    }

    private function parseWorkingHours(string $hours): array
    {
        // Формат: "Пн-Пт: 09:00-18:00, Сб: 10:00-15:00"
        // или JSON строка
        if (str_starts_with($hours, '{') || str_starts_with($hours, '[')) {
            return json_decode($hours, true) ?? [];
        }

        return ['description' => $hours];
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'terminal_code' => 'nullable|string|max:20',
            'type' => 'nullable|in:hub,depot,pickup_point,delivery_point,pickup,delivery,warehouse',
            'country_code' => 'nullable|string|max:3',
            'state' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'service_radius' => 'nullable|integer|min:0',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'is_active' => 'nullable|boolean',
            'working_hours' => 'nullable|string',
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
