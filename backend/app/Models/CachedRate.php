<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CachedRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_id',
        'route_hash',
        'cache_key',
        'origin_country',
        'origin_city',
        'origin_postal_code',
        'destination_country',
        'destination_city',
        'destination_postal_code',
        'transport_type',
        'weight',
        'volume',
        'rate_data',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'volume' => 'decimal:4',
            'rate_data' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    public static function generateCacheKey(array $params): string
    {
        ksort($params);
        return md5(json_encode($params));
    }

    public static function findCached(int $carrierId, array $params): ?self
    {
        $cacheKey = self::generateCacheKey($params);

        return self::where('carrier_id', $carrierId)
            ->where('cache_key', $cacheKey)
            ->where('expires_at', '>', now())
            ->first();
    }

    public static function cacheRate(int $carrierId, array $params, array $rateData, int $ttlMinutes = 60): self
    {
        $cacheKey = self::generateCacheKey($params);

        return self::updateOrCreate(
            [
                'carrier_id' => $carrierId,
                'cache_key' => $cacheKey,
            ],
            [
                'route_hash' => $cacheKey, // for backwards compatibility
                'origin_country' => $params['origin_country'] ?? null,
                'origin_city' => $params['origin_city'] ?? null,
                'origin_postal_code' => $params['origin_postal_code'] ?? null,
                'destination_country' => $params['destination_country'] ?? null,
                'destination_city' => $params['destination_city'] ?? null,
                'destination_postal_code' => $params['destination_postal_code'] ?? null,
                'transport_type' => $params['transport_type'] ?? null,
                'weight' => $params['weight'] ?? null,
                'volume' => $params['volume'] ?? null,
                'rate_data' => $rateData,
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]
        );
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
