<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_id',
        'pricing_type',
        'dim_factor',
        'minimum_charge',
        'insurance_rate',
        'currency',
        'effective_from',
        'effective_until',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'dim_factor' => 'decimal:2',
            'minimum_charge' => 'decimal:2',
            'insurance_rate' => 'decimal:2',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'config' => 'array',
        ];
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
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

    public function calculateDimWeight(float $length, float $width, float $height): float
    {
        // Dimensions in cm, result in kg
        $volumeCm3 = $length * $width * $height;
        return $volumeCm3 / $this->dim_factor;
    }

    public function calculateBillableWeight(float $actualWeight, float $length, float $width, float $height): float
    {
        $dimWeight = $this->calculateDimWeight($length, $width, $height);
        return max($actualWeight, $dimWeight);
    }
}
