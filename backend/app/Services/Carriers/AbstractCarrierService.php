<?php

namespace App\Services\Carriers;

use App\Models\CachedRate;
use App\Models\Carrier;
use App\Models\CarrierPricingRule;
use App\Models\CarrierRateCard;
use App\Models\CarrierZone;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Http;

abstract class AbstractCarrierService implements CarrierServiceInterface
{
    protected array $config;

    public function __construct(
        protected Carrier $carrier
    ) {
        $this->config = $carrier->api_config ?? [];
    }

    abstract public function getQuotes(Shipment $shipment): array;
    abstract public function createOrder(Order $order): CarrierOrderResponse;
    abstract public function getTrackingStatus(string $trackingNumber): TrackingStatus;
    abstract public function cancelOrder(string $orderNumber): bool;
    abstract public function getShippingLabel(Order $order): string;

    protected function getApiKey(): ?string
    {
        return $this->config['api_key'] ?? null;
    }

    protected function getApiSecret(): ?string
    {
        return $this->config['api_secret'] ?? null;
    }

    protected function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? '';
    }

    /**
     * Get DIM factor from pricing rules or use default
     */
    protected function getDimFactor(): float
    {
        $pricingRule = $this->carrier->activePricingRule();
        return $pricingRule?->dim_factor ?? 5000;
    }

    /**
     * Calculate volumetric weight using carrier's DIM factor
     */
    protected function calculateVolumetricWeight(Shipment $shipment): float
    {
        $dimFactor = $this->getDimFactor();
        $volumetricWeight = 0;

        foreach ($shipment->items as $item) {
            $volumetricWeight += ($item->length * $item->width * $item->height / $dimFactor) * $item->quantity;
        }

        return $volumetricWeight;
    }

    /**
     * Get billable weight (max of actual vs volumetric)
     */
    protected function getChargeableWeight(Shipment $shipment): float
    {
        $actualWeight = (float) $shipment->total_weight;
        $volumetricWeight = $this->calculateVolumetricWeight($shipment);

        return max($actualWeight, $volumetricWeight);
    }

    /**
     * Find zone for a location based on carrier's zone configuration
     */
    protected function findZone(string $country, ?string $city = null, ?string $postalCode = null): ?CarrierZone
    {
        $zones = $this->carrier->zones();

        if ($zones->count() === 0) {
            return null;
        }

        // Маппинг название страны -> ISO код
        $countryMap = [
            'Казахстан' => 'KZ',
            'Россия' => 'RU',
            'Китай' => 'CN',
            'Узбекистан' => 'UZ',
            'Кыргызстан' => 'KG',
            'Таджикистан' => 'TJ',
            'Беларусь' => 'BY',
            'Туркменистан' => 'TM',
            'США' => 'US',
            'Германия' => 'DE',
            'Турция' => 'TR',
            'ОАЭ' => 'AE',
        ];

        // Получаем ISO код
        $countryCode = $countryMap[$country] ?? $country;

        // Ищем зону по country_code или zone_code
        $zone = $zones->where('country_code', $countryCode)->first();

        if (!$zone) {
            // Попробуем найти по zone_code (например zone_code = 'KZ')
            $zone = $zones->where('zone_code', $countryCode)->first();
        }

        return $zone;
    }

    /**
     * Check if destination is a remote area
     */
    protected function isRemoteArea(?string $postalCode, ?CarrierZone $zone): bool
    {
        if (!$postalCode || !$zone) {
            return false;
        }

        return $zone->postalCodes()
            ->where(function ($q) use ($postalCode) {
                $q->whereRaw('? LIKE CONCAT(postal_code_prefix, "%")', [$postalCode])
                  ->orWhere(function ($rangeQ) use ($postalCode) {
                      $rangeQ->where('postal_code_from', '<=', $postalCode)
                             ->where('postal_code_to', '>=', $postalCode);
                  });
            })
            ->where('is_remote_area', true)
            ->exists();
    }

    /**
     * Get applicable rate card for given zones and weight
     */
    protected function findRateCard(
        ?CarrierZone $originZone,
        ?CarrierZone $destinationZone,
        float $weight,
        ?string $transportType = null
    ): ?CarrierRateCard {
        $query = $this->carrier->rateCards()
            ->where(function ($q) use ($weight) {
                $q->where('min_weight', '<=', $weight)
                  ->where(function ($maxQ) use ($weight) {
                      $maxQ->whereNull('max_weight')
                           ->orWhere('max_weight', '>=', $weight);
                  });
            });

        if ($originZone) {
            $query->where('origin_zone_id', $originZone->id);
        }

        if ($destinationZone) {
            $query->where('destination_zone_id', $destinationZone->id);
        }

        if ($transportType) {
            $query->where('transport_type', $transportType);
        }

        return $query->orderBy('min_weight', 'desc')->first();
    }

    /**
     * Calculate rate from rate card
     */
    protected function calculateBaseRate(CarrierRateCard $rateCard, float $billableWeight): float
    {
        $rate = (float) $rateCard->rate;
        $rateUnit = $rateCard->rate_unit ?? 'per_kg';

        return match ($rateUnit) {
            'flat' => $rate,
            'per_kg' => $billableWeight * $rate,
            'per_lb' => $billableWeight * 2.20462 * $rate,
            'per_100kg' => ($billableWeight / 100) * $rate,
            'per_100lbs' => ($billableWeight * 2.20462 / 100) * $rate,
            default => $billableWeight * $rate,
        };
    }

    /**
     * Apply minimum charge from pricing rules
     */
    protected function applyMinimumCharge(float $rate): float
    {
        $pricingRule = $this->carrier->activePricingRule();
        $minCharge = $pricingRule?->minimum_charge ?? 0;

        return max($rate, (float) $minCharge);
    }

    /**
     * Calculate all applicable surcharges
     */
    protected function calculateSurcharges(
        float $baseRate,
        float $weight,
        ?string $transportType = null,
        array $options = []
    ): array {
        $surcharges = [];
        $total = 0;

        $applicableSurcharges = $this->carrier->activeSurcharges()->get();

        foreach ($applicableSurcharges as $surcharge) {
            // Check transport type applicability
            if (!$surcharge->appliesToTransportType($transportType)) {
                continue;
            }

            // Check specific surcharge conditions
            $applies = true;
            switch ($surcharge->surcharge_type) {
                case 'residential':
                    $applies = !empty($options['residential_delivery']);
                    break;
                case 'remote_area':
                    $applies = !empty($options['is_remote_area']);
                    break;
                case 'fuel':
                case 'peak_season':
                    $applies = true; // Always apply
                    break;
            }

            if (!$applies) {
                continue;
            }

            // Calculate surcharge amount
            $amount = match ($surcharge->calculation_type) {
                'percentage' => $baseRate * ((float) $surcharge->value / 100),
                'flat' => (float) $surcharge->value,
                'per_kg' => $weight * (float) $surcharge->value,
                default => 0,
            };

            // Apply min/max bounds
            if ($surcharge->min_value !== null) {
                $amount = max($amount, (float) $surcharge->min_value);
            }
            if ($surcharge->max_value !== null) {
                $amount = min($amount, (float) $surcharge->max_value);
            }

            if ($amount > 0) {
                $surcharges[] = [
                    'type' => $surcharge->surcharge_type,
                    'name' => $surcharge->name,
                    'amount' => round($amount, 2),
                ];
                $total += $amount;
            }
        }

        return [
            'items' => $surcharges,
            'total' => round($total, 2),
        ];
    }

    /**
     * Calculate insurance cost
     */
    protected function calculateInsurance(float $declaredValue): float
    {
        $pricingRule = $this->carrier->activePricingRule();
        $rate = $pricingRule?->insurance_rate ?? 0.5; // Default 0.5%

        return round($declaredValue * ($rate / 100), 2);
    }

    /**
     * Get currency from pricing rules
     */
    protected function getCurrency(): string
    {
        $pricingRule = $this->carrier->activePricingRule();
        return $pricingRule?->currency ?? 'USD';
    }

    /**
     * Check if carrier supports a route
     */
    protected function supportsRoute(string $originCountry, string $destCountry, ?string $transportType = null): bool
    {
        if (!$this->carrier->is_active) {
            return false;
        }

        if (!$this->carrier->supportsCountry($originCountry) || !$this->carrier->supportsCountry($destCountry)) {
            return false;
        }

        if ($transportType && !$this->carrier->supportsTransportType($transportType)) {
            return false;
        }

        return true;
    }

    /**
     * Try to get cached rate
     */
    protected function getCachedRate(array $params): ?array
    {
        $cached = CachedRate::findCached($this->carrier->id, $params);
        return $cached?->rate_data;
    }

    /**
     * Cache a rate calculation
     */
    protected function cacheRate(array $params, array $rateData, int $ttlMinutes = 60): void
    {
        CachedRate::cacheRate($this->carrier->id, $params, $rateData, $ttlMinutes);
    }
}
