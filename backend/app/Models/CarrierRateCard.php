<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierRateCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_id',
        'pricing_rule_id',
        'origin_zone_id',
        'destination_zone_id',
        'transport_type',
        'min_weight',
        'max_weight',
        'rate',
        'rate_unit',
        'currency',
        'transit_days_min',
        'transit_days_max',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'min_weight' => 'decimal:2',
            'max_weight' => 'decimal:2',
            'rate' => 'decimal:4',
            'transit_days_min' => 'integer',
            'transit_days_max' => 'integer',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function originZone(): BelongsTo
    {
        return $this->belongsTo(CarrierZone::class, 'origin_zone_id');
    }

    public function destinationZone(): BelongsTo
    {
        return $this->belongsTo(CarrierZone::class, 'destination_zone_id');
    }

    public function isActive(): bool
    {
        $now = now()->toDateString();

        if ($this->effective_from && $now < $this->effective_from) {
            return false;
        }

        if ($this->effective_until && $now > $this->effective_until) {
            return false;
        }

        return true;
    }

    public function matchesWeight(float $weight): bool
    {
        if ($this->min_weight !== null && $weight < $this->min_weight) {
            return false;
        }

        if ($this->max_weight !== null && $weight > $this->max_weight) {
            return false;
        }

        return true;
    }

    public function calculateRate(float $billableWeight): float
    {
        $rate = (float) $this->rate;
        $rateUnit = $this->rate_unit ?? 'per_kg';

        return match ($rateUnit) {
            'flat' => $rate,
            'per_kg' => $billableWeight * $rate,
            'per_lb' => $billableWeight * 2.20462 * $rate,
            'per_100kg' => ($billableWeight / 100) * $rate,
            'per_100lbs' => ($billableWeight * 2.20462 / 100) * $rate,
            default => $billableWeight * $rate,
        };
    }
}
