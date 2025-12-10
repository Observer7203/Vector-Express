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
