<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierTerminal extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_id',
        'terminal_code',
        'name',
        'type',
        'country_code',
        'city',
        'address',
        'postal_code',
        'latitude',
        'longitude',
        'contact_phone',
        'contact_email',
        'working_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'working_hours' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function getCoordinates(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    public function distanceTo(float $lat, float $lng): float
    {
        // Haversine formula for distance calculation
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->latitude);
        $lngFrom = deg2rad($this->longitude);
        $latTo = deg2rad($lat);
        $lngTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2 +
             cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 50)
    {
        // Simple bounding box filter (approximate)
        $latDelta = $radiusKm / 111; // ~111km per degree latitude
        $lngDelta = $radiusKm / (111 * cos(deg2rad($lat)));

        return $query->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
                     ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);
    }
}
