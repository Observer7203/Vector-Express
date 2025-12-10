<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierSurcharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_id',
        'surcharge_type',
        'name',
        'calculation_type',
        'value',
        'min_value',
        'max_value',
        'currency',
        'applies_to_transport_types',
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
            'applies_to_transport_types' => 'array',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->toDateString();

        if ($this->effective_from && $now < $this->effective_from) {
            return false;
        }

        if ($this->effective_until && $now > $this->effective_until) {
            return false;
        }

        return true;
    }

    public function appliesToTransportType(?string $transportType): bool
    {
        if (empty($this->applies_to_transport_types)) {
            return true; // Applies to all
        }

        return in_array($transportType, $this->applies_to_transport_types);
    }

    public function calculate(float $baseAmount): float
    {
        $surcharge = match ($this->calculation_type) {
            'percentage' => $baseAmount * ($this->value / 100),
            'flat' => (float) $this->value,
            'per_kg' => $baseAmount, // Weight passed as baseAmount in this case
            default => 0,
        };

        if ($this->min_value !== null && $surcharge < $this->min_value) {
            $surcharge = (float) $this->min_value;
        }

        if ($this->max_value !== null && $surcharge > $this->max_value) {
            $surcharge = (float) $this->max_value;
        }

        return $surcharge;
    }
}
