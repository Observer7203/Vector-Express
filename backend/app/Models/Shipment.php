<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'origin_country',
        'origin_city',
        'origin_address',
        'origin_lat',
        'origin_lng',
        'origin_postcode',
        'destination_country',
        'destination_city',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'destination_postcode',
        'transport_type',
        'cargo_type',
        'packaging_type',
        'total_weight',
        'total_volume',
        'declared_value',
        'currency',
        'insurance_required',
        'customs_clearance',
        'door_to_door',
        'origin_type',
        'destination_type',
        'origin_terminal_id',
        'destination_terminal_id',
        'origin_to_terminal_km',
        'terminal_to_destination_km',
        'total_distance_km',
        'pickup_date',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:2',
            'total_volume' => 'decimal:4',
            'declared_value' => 'decimal:2',
            'origin_lat' => 'decimal:7',
            'origin_lng' => 'decimal:7',
            'destination_lat' => 'decimal:7',
            'destination_lng' => 'decimal:7',
            'origin_to_terminal_km' => 'decimal:2',
            'terminal_to_destination_km' => 'decimal:2',
            'total_distance_km' => 'decimal:2',
            'insurance_required' => 'boolean',
            'customs_clearance' => 'boolean',
            'door_to_door' => 'boolean',
            'pickup_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function selectedQuote(): HasOne
    {
        return $this->hasOne(Quote::class)->where('is_selected', true);
    }

    public function originTerminal(): BelongsTo
    {
        return $this->belongsTo(CarrierTerminal::class, 'origin_terminal_id');
    }

    public function destinationTerminal(): BelongsTo
    {
        return $this->belongsTo(CarrierTerminal::class, 'destination_terminal_id');
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Find nearest terminal to given coordinates
     */
    public function findNearestTerminal(float $lat, float $lng, ?int $carrierId = null): ?CarrierTerminal
    {
        $query = CarrierTerminal::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($carrierId) {
            $query->where('carrier_id', $carrierId);
        }

        $terminals = $query->get();

        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($terminals as $terminal) {
            $distance = self::calculateDistance($lat, $lng, $terminal->latitude, $terminal->longitude);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $terminal;
            }
        }

        return $nearest;
    }

    /**
     * Check if coordinates are within carrier's service area
     */
    public function isInServiceArea(float $lat, float $lng, int $carrierId): bool
    {
        // Check if within any zone's bounding box or postcode
        $zones = CarrierZone::where('carrier_id', $carrierId)->get();

        foreach ($zones as $zone) {
            // Simple bounding box check for now
            // In production, you'd use proper geospatial queries
            if ($zone->postalCodes()->exists()) {
                // Check postcode match if available
                continue;
            }
        }

        // Fallback: check if there's a terminal within reasonable distance (300km)
        $nearestTerminal = $this->findNearestTerminal($lat, $lng, $carrierId);
        if ($nearestTerminal) {
            $distance = self::calculateDistance($lat, $lng, $nearestTerminal->latitude, $nearestTerminal->longitude);
            return $distance <= 300;
        }

        return false;
    }

    /**
     * Calculate routing for door-to-door delivery
     * Returns array with terminals and distances
     */
    public function calculateDoorToDoorRoute(): array
    {
        $result = [
            'origin_terminal' => null,
            'destination_terminal' => null,
            'origin_to_terminal_km' => null,
            'terminal_to_destination_km' => null,
            'total_distance_km' => null,
        ];

        // Need coordinates for both points
        if (!$this->origin_lat || !$this->origin_lng || !$this->destination_lat || !$this->destination_lng) {
            return $result;
        }

        // Find nearest terminals for origin and destination
        $originTerminal = $this->findNearestTerminal($this->origin_lat, $this->origin_lng);
        $destTerminal = $this->findNearestTerminal($this->destination_lat, $this->destination_lng);

        if ($originTerminal) {
            $result['origin_terminal'] = $originTerminal;
            $result['origin_to_terminal_km'] = self::calculateDistance(
                $this->origin_lat,
                $this->origin_lng,
                $originTerminal->latitude,
                $originTerminal->longitude
            );
        }

        if ($destTerminal) {
            $result['destination_terminal'] = $destTerminal;
            $result['terminal_to_destination_km'] = self::calculateDistance(
                $destTerminal->latitude,
                $destTerminal->longitude,
                $this->destination_lat,
                $this->destination_lng
            );
        }

        // Calculate direct distance between origin and destination
        $result['total_distance_km'] = self::calculateDistance(
            $this->origin_lat,
            $this->origin_lng,
            $this->destination_lat,
            $this->destination_lng
        );

        return $result;
    }

    /**
     * Update route information based on coordinates
     */
    public function updateRouteInfo(): void
    {
        $route = $this->calculateDoorToDoorRoute();

        $this->origin_terminal_id = $route['origin_terminal']?->id;
        $this->destination_terminal_id = $route['destination_terminal']?->id;
        $this->origin_to_terminal_km = $route['origin_to_terminal_km'];
        $this->terminal_to_destination_km = $route['terminal_to_destination_km'];
        $this->total_distance_km = $route['total_distance_km'];

        $this->save();
    }

    public function calculateTotals(): void
    {
        $items = $this->items;

        $this->total_weight = $items->sum(function ($item) {
            return $item->weight * $item->quantity;
        });

        $this->total_volume = $items->sum(function ($item) {
            return ($item->length * $item->width * $item->height / 1000000) * $item->quantity; // cm³ to m³
        });

        $this->save();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isQuoted(): bool
    {
        return $this->status === 'quoted';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }
}
