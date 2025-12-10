<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'api_type',
        'api_config',
        'supported_transport_types',
        'supported_countries',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'api_config' => 'array',
            'supported_transport_types' => 'array',
            'supported_countries' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(CarrierPricingRule::class);
    }

    public function activePricingRule(): ?CarrierPricingRule
    {
        return $this->pricingRules()
            ->where(function ($query) {
                $query->whereNull('effective_from')
                      ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', now());
            })
            ->latest()
            ->first();
    }

    public function zones(): HasMany
    {
        return $this->hasMany(CarrierZone::class);
    }

    public function rateCards(): HasMany
    {
        return $this->hasMany(CarrierRateCard::class);
    }

    public function surcharges(): HasMany
    {
        return $this->hasMany(CarrierSurcharge::class);
    }

    public function activeSurcharges(): HasMany
    {
        return $this->surcharges()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('effective_from')
                      ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', now());
            });
    }

    public function terminals(): HasMany
    {
        return $this->hasMany(CarrierTerminal::class);
    }

    public function activeTerminals(): HasMany
    {
        return $this->terminals()->where('is_active', true);
    }

    public function cachedRates(): HasMany
    {
        return $this->hasMany(CachedRate::class);
    }

    public function supportsTransportType(string $type): bool
    {
        return in_array($type, $this->supported_transport_types ?? []);
    }

    public function supportsCountry(string $country): bool
    {
        $countries = $this->supported_countries ?? [];
        return empty($countries) || in_array($country, $countries);
    }
}
