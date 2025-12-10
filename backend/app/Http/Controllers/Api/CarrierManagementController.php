<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\CarrierPricingRule;
use App\Models\CarrierRateCard;
use App\Models\CarrierSurcharge;
use App\Models\CarrierTerminal;
use App\Models\CarrierZone;
use App\Models\CarrierZonePostalCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Carrier Management Controller
 *
 * Follows SOLID principles:
 * - Single Responsibility: Each method handles one specific resource
 * - Open/Closed: Extensible through inheritance/interfaces
 * - Liskov Substitution: Uses proper type hints and contracts
 * - Interface Segregation: Separate endpoints for each resource type
 * - Dependency Inversion: Uses Laravel's dependency injection
 */
class CarrierManagementController extends Controller
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Get the current user's carrier
     */
    private function getCarrier(Request $request): ?Carrier
    {
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return null;
        }

        return Cache::remember(
            "carrier:company:{$user->company_id}",
            self::CACHE_TTL,
            fn() => Carrier::where('company_id', $user->company_id)->first()
        );
    }

    /**
     * Clear carrier-related caches
     */
    private function clearCarrierCache(int $carrierId): void
    {
        Cache::forget("carrier:{$carrierId}:zones");
        Cache::forget("carrier:{$carrierId}:rates");
        Cache::forget("carrier:{$carrierId}:surcharges");
        Cache::forget("carrier:{$carrierId}:terminals");
        Cache::forget("carrier:{$carrierId}:pricing_rule");
    }

    // =========================================================================
    // ZONES
    // =========================================================================

    /**
     * List all zones for the carrier
     */
    public function getZones(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $zones = Cache::remember(
            "carrier:{$carrier->id}:zones",
            self::CACHE_TTL,
            fn() => CarrierZone::where('carrier_id', $carrier->id)
                ->with('postalCodes')
                ->orderBy('zone_code')
                ->get()
        );

        return response()->json(['data' => $zones]);
    }

    /**
     * Create a new zone
     */
    public function createZone(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $validated = $request->validate([
            'zone_code' => 'required|string|max:10',
            'zone_name' => 'required|string|max:100',
            'country_code' => 'required|string|max:2',
            'description' => 'nullable|string|max:500',
            'postal_codes' => 'nullable|array',
            'postal_codes.*.postal_code_prefix' => 'required_with:postal_codes|string|max:10',
            'postal_codes.*.city' => 'required_with:postal_codes|string|max:100',
            'postal_codes.*.region' => 'nullable|string|max:100',
            'postal_codes.*.is_remote_area' => 'nullable|boolean',
        ]);

        $zone = DB::transaction(function () use ($carrier, $validated) {
            $zone = CarrierZone::create([
                'carrier_id' => $carrier->id,
                'zone_code' => $validated['zone_code'],
                'zone_name' => $validated['zone_name'],
                'country_code' => $validated['country_code'],
                'description' => $validated['description'] ?? null,
            ]);

            // Create postal codes if provided
            if (!empty($validated['postal_codes'])) {
                foreach ($validated['postal_codes'] as $pc) {
                    CarrierZonePostalCode::create([
                        'carrier_zone_id' => $zone->id,
                        'postal_code_prefix' => $pc['postal_code_prefix'],
                        'city' => $pc['city'],
                        'region' => $pc['region'] ?? null,
                        'is_remote_area' => $pc['is_remote_area'] ?? false,
                    ]);
                }
            }

            return $zone->load('postalCodes');
        });

        $this->clearCarrierCache($carrier->id);

        return response()->json(['data' => $zone], 201);
    }

    /**
     * Update a zone
     */
    public function updateZone(Request $request, int $id): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $zone = CarrierZone::where('carrier_id', $carrier->id)->find($id);

        if (!$zone) {
            return response()->json(['error' => 'Zone not found'], 404);
        }

        $validated = $request->validate([
            'zone_code' => 'sometimes|string|max:10',
            'zone_name' => 'sometimes|string|max:100',
            'country_code' => 'sometimes|string|max:2',
            'description' => 'nullable|string|max:500',
            'postal_codes' => 'nullable|array',
        ]);

        DB::transaction(function () use ($zone, $validated) {
            $zone->update($validated);

            // Update postal codes if provided
            if (isset($validated['postal_codes'])) {
                // Remove existing and recreate
                $zone->postalCodes()->delete();

                foreach ($validated['postal_codes'] as $pc) {
                    CarrierZonePostalCode::create([
                        'carrier_zone_id' => $zone->id,
                        'postal_code_prefix' => $pc['postal_code_prefix'],
                        'city' => $pc['city'],
                        'region' => $pc['region'] ?? null,
                        'is_remote_area' => $pc['is_remote_area'] ?? false,
                    ]);
                }
            }
        });

        $this->clearCarrierCache($carrier->id);

        return response()->json(['data' => $zone->fresh()->load('postalCodes')]);
    }

    /**
     * Delete a zone
     */
    public function deleteZone(Request $request, int $id): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $zone = CarrierZone::where('carrier_id', $carrier->id)->find($id);

        if (!$zone) {
            return response()->json(['error' => 'Zone not found'], 404);
        }

        $zone->delete();
        $this->clearCarrierCache($carrier->id);

        return response()->json(['message' => 'Zone deleted']);
    }

    // =========================================================================
    // TERMINALS
    // =========================================================================

    /**
     * List all terminals for the carrier
     */
    public function getTerminals(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $terminals = Cache::remember(
            "carrier:{$carrier->id}:terminals",
            self::CACHE_TTL,
            fn() => CarrierTerminal::where('carrier_id', $carrier->id)
                ->orderBy('terminal_code')
                ->get()
        );

        return response()->json(['data' => $terminals]);
    }

    /**
     * Create a new terminal
     */
    public function createTerminal(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $validated = $request->validate([
            'terminal_code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'type' => 'required|in:hub,depot,pickup_point,delivery_point',
            'country_code' => 'required|string|max:2',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
            'working_hours' => 'nullable|array',
        ]);

        $terminal = CarrierTerminal::create([
            'carrier_id' => $carrier->id,
            ...$validated,
        ]);

        $this->clearCarrierCache($carrier->id);

        return response()->json(['data' => $terminal], 201);
    }

    /**
     * Update a terminal
     */
    public function updateTerminal(Request $request, int $id): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $terminal = CarrierTerminal::where('carrier_id', $carrier->id)->find($id);

        if (!$terminal) {
            return response()->json(['error' => 'Terminal not found'], 404);
        }

        $validated = $request->validate([
            'terminal_code' => 'sometimes|string|max:20',
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:hub,depot,pickup_point,delivery_point',
            'country_code' => 'sometimes|string|max:2',
            'city' => 'sometimes|string|max:100',
            'address' => 'sometimes|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
            'working_hours' => 'nullable|array',
        ]);

        $terminal->update($validated);
        $this->clearCarrierCache($carrier->id);

        return response()->json(['data' => $terminal]);
    }

    /**
     * Delete a terminal
     */
    public function deleteTerminal(Request $request, int $id): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $terminal = CarrierTerminal::where('carrier_id', $carrier->id)->find($id);

        if (!$terminal) {
            return response()->json(['error' => 'Terminal not found'], 404);
        }

        $terminal->delete();
        $this->clearCarrierCache($carrier->id);

        return response()->json(['message' => 'Terminal deleted']);
    }

    // =========================================================================
    // SURCHARGES
    // =========================================================================

    /**
     * List all surcharges for the carrier
     */
    public function getSurcharges(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $surcharges = Cache::remember(
            "carrier:{$carrier->id}:surcharges",
            self::CACHE_TTL,
            fn() => CarrierSurcharge::where('carrier_id', $carrier->id)
                ->orderBy('surcharge_type')
                ->get()
        );

        return response()->json(['data' => $surcharges]);
    }

    /**
     * Create a new surcharge
     */
    public function createSurcharge(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $validated = $request->validate([
            'surcharge_type' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'calculation_type' => 'required|in:flat,percentage,per_kg',
            'value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'is_active' => 'boolean',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from',
        ]);

        $surcharge = CarrierSurcharge::create([
            'carrier_id' => $carrier->id,
            'currency' => 'USD',
            'is_active' => true,
            ...$validated,
        ]);

        $this->clearCarrierCache($carrier->id);

        return response()->json(['data' => $surcharge], 201);
    }

    /**
     * Update a surcharge
     */
    public function updateSurcharge(Request $request, int $id): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $surcharge = CarrierSurcharge::where('carrier_id', $carrier->id)->find($id);

        if (!$surcharge) {
            return response()->json(['error' => 'Surcharge not found'], 404);
        }

        $validated = $request->validate([
            'surcharge_type' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:100',
            'calculation_type' => 'sometimes|in:flat,percentage,per_kg',
            'value' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'is_active' => 'boolean',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date',
        ]);

        $surcharge->update($validated);
        $this->clearCarrierCache($carrier->id);

        return response()->json(['data' => $surcharge]);
    }

    /**
     * Delete a surcharge
     */
    public function deleteSurcharge(Request $request, int $id): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $surcharge = CarrierSurcharge::where('carrier_id', $carrier->id)->find($id);

        if (!$surcharge) {
            return response()->json(['error' => 'Surcharge not found'], 404);
        }

        $surcharge->delete();
        $this->clearCarrierCache($carrier->id);

        return response()->json(['message' => 'Surcharge deleted']);
    }

    // =========================================================================
    // RATE CARDS
    // =========================================================================

    /**
     * List all rate cards for the carrier
     */
    public function getRateCards(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $perPage = min($request->get('per_page', 50), 100);

        // Use pagination for large datasets
        $rateCards = CarrierRateCard::where('carrier_id', $carrier->id)
            ->with(['originZone', 'destinationZone'])
            ->orderBy('origin_zone_id')
            ->orderBy('destination_zone_id')
            ->orderBy('transport_type')
            ->orderBy('weight_min')
            ->paginate($perPage);

        return response()->json($rateCards);
    }

    /**
     * Create a new rate card
     */
    public function createRateCard(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $validated = $request->validate([
            'origin_zone_id' => 'required|exists:carrier_zones,id',
            'destination_zone_id' => 'required|exists:carrier_zones,id',
            'transport_type' => 'required|in:air,road,rail,sea',
            'weight_min' => 'required|numeric|min:0',
            'weight_max' => 'nullable|numeric|gt:weight_min',
            'rate_per_kg' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'transit_days_min' => 'nullable|integer|min:1',
            'transit_days_max' => 'nullable|integer|min:1',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from',
        ]);

        $rateCard = CarrierRateCard::create([
            'carrier_id' => $carrier->id,
            'currency' => 'USD',
            ...$validated,
        ]);

        $this->clearCarrierCache($carrier->id);

        return response()->json(['data' => $rateCard->load(['originZone', 'destinationZone'])], 201);
    }

    /**
     * Update a rate card
     */
    public function updateRateCard(Request $request, int $id): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $rateCard = CarrierRateCard::where('carrier_id', $carrier->id)->find($id);

        if (!$rateCard) {
            return response()->json(['error' => 'Rate card not found'], 404);
        }

        $validated = $request->validate([
            'origin_zone_id' => 'sometimes|exists:carrier_zones,id',
            'destination_zone_id' => 'sometimes|exists:carrier_zones,id',
            'transport_type' => 'sometimes|in:air,road,rail,sea',
            'weight_min' => 'sometimes|numeric|min:0',
            'weight_max' => 'nullable|numeric',
            'rate_per_kg' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'transit_days_min' => 'nullable|integer|min:1',
            'transit_days_max' => 'nullable|integer|min:1',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date',
        ]);

        $rateCard->update($validated);
        $this->clearCarrierCache($carrier->id);

        return response()->json(['data' => $rateCard->load(['originZone', 'destinationZone'])]);
    }

    /**
     * Delete a rate card
     */
    public function deleteRateCard(Request $request, int $id): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $rateCard = CarrierRateCard::where('carrier_id', $carrier->id)->find($id);

        if (!$rateCard) {
            return response()->json(['error' => 'Rate card not found'], 404);
        }

        $rateCard->delete();
        $this->clearCarrierCache($carrier->id);

        return response()->json(['message' => 'Rate card deleted']);
    }

    // =========================================================================
    // PRICING RULE
    // =========================================================================

    /**
     * Get pricing rule for the carrier
     */
    public function getPricingRule(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $pricingRule = Cache::remember(
            "carrier:{$carrier->id}:pricing_rule",
            self::CACHE_TTL,
            fn() => CarrierPricingRule::where('carrier_id', $carrier->id)
                ->whereDate('effective_from', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('effective_until')
                        ->orWhereDate('effective_until', '>=', now());
                })
                ->first()
        );

        return response()->json(['data' => $pricingRule]);
    }

    /**
     * Update pricing rule for the carrier
     */
    public function updatePricingRule(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $validated = $request->validate([
            'pricing_type' => 'sometimes|in:zone,distance,flat',
            'dim_factor' => 'sometimes|integer|min:1000|max:10000',
            'minimum_charge' => 'sometimes|numeric|min:0',
            'insurance_rate' => 'sometimes|numeric|min:0|max:10',
            'currency' => 'sometimes|string|max:3',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from',
        ]);

        $pricingRule = CarrierPricingRule::updateOrCreate(
            ['carrier_id' => $carrier->id],
            $validated
        );

        $this->clearCarrierCache($carrier->id);

        return response()->json(['data' => $pricingRule]);
    }

    // =========================================================================
    // CARRIER STATS
    // =========================================================================

    /**
     * Get carrier statistics dashboard
     */
    public function getStats(Request $request): JsonResponse
    {
        $carrier = $this->getCarrier($request);

        if (!$carrier) {
            return response()->json(['error' => 'Carrier not found'], 404);
        }

        $stats = Cache::remember(
            "carrier:{$carrier->id}:stats",
            300, // 5 minutes
            function () use ($carrier) {
                return [
                    'zones_count' => CarrierZone::where('carrier_id', $carrier->id)->count(),
                    'rate_cards_count' => CarrierRateCard::where('carrier_id', $carrier->id)->count(),
                    'terminals_count' => CarrierTerminal::where('carrier_id', $carrier->id)
                        ->where('is_active', true)
                        ->count(),
                    'surcharges_count' => CarrierSurcharge::where('carrier_id', $carrier->id)
                        ->where('is_active', true)
                        ->count(),
                ];
            }
        );

        return response()->json(['data' => $stats]);
    }
}
