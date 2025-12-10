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
        'origin_zone_id',
        'destination_zone_id',
        'transport_type',
        'weight_min',
        'weight_max',
        'rate_per_kg',
        'flat_rate',
        'currency',
        'transit_days_min',
        'transit_days_max',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'weight_min' => 'decimal:2',
            'weight_max' => 'decimal:2',
            'rate_per_kg' => 'decimal:4',
            'flat_rate' => 'decimal:2',
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
        if ($this->weight_min !== null && $weight < $this->weight_min) {
            return false;
        }

        if ($this->weight_max !== null && $weight > $this->weight_max) {
            return false;
        }

        return true;
    }

    public function calculateRate(float $billableWeight): float
    {
        if ($this->flat_rate !== null) {
            return (float) $this->flat_rate;
        }

        return $billableWeight * (float) $this->rate_per_kg;
    }
}
