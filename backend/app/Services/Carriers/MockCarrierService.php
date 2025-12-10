<?php

namespace App\Services\Carriers;

use App\Models\Order;
use App\Models\Shipment;

class MockCarrierService extends AbstractCarrierService
{
    public function getQuotes(Shipment $shipment): array
    {
        // Check route support
        if (!$this->supportsRoute(
            $shipment->origin_country,
            $shipment->destination_country,
            $shipment->transport_type
        )) {
            return [];
        }

        // Try to get cached rate first
        $cacheParams = [
            'origin_country' => $shipment->origin_country,
            'origin_city' => $shipment->origin_city,
            'destination_country' => $shipment->destination_country,
            'destination_city' => $shipment->destination_city,
            'transport_type' => $shipment->transport_type,
            'weight' => (float) $shipment->total_weight,
            'volume' => (float) $shipment->total_volume,
        ];

        $cachedRate = $this->getCachedRate($cacheParams);
        if ($cachedRate) {
            return $cachedRate;
        }

        $chargeableWeight = $this->getChargeableWeight($shipment);

        // Find zones
        $originZone = $this->findZone($shipment->origin_country, $shipment->origin_city);
        $destinationZone = $this->findZone($shipment->destination_country, $shipment->destination_city);

        // Check for remote area
        $isRemoteArea = $this->isRemoteArea(null, $destinationZone);

        $quotes = [];
        $transportTypes = $shipment->transport_type
            ? [$shipment->transport_type]
            : ($this->carrier->supported_transport_types ?? ['road']);

        foreach ($transportTypes as $transportType) {
            // Try to get rate from rate cards first
            $rateCard = $this->findRateCard($originZone, $destinationZone, $chargeableWeight, $transportType);

            if ($rateCard) {
                // Use rate card pricing
                $baseRate = $this->calculateBaseRate($rateCard, $chargeableWeight);
                $baseRate = $this->applyMinimumCharge($baseRate);
                $transitDaysMin = $rateCard->transit_days_min ?? $this->getDeliveryDays($transportType);
                $transitDaysMax = $rateCard->transit_days_max ?? $transitDaysMin + 3;
            } else {
                // Fall back to default rates
                $defaultRates = $this->getBaseRates();
                $rate = $defaultRates[$transportType] ?? 10;
                $baseRate = $this->applyMinimumCharge($chargeableWeight * $rate);
                $transitDaysMin = $this->getDeliveryDays($transportType);
                $transitDaysMax = $transitDaysMin + 3;
            }

            // Calculate surcharges using new system
            $surcharges = $this->calculateSurcharges($baseRate, $chargeableWeight, $transportType, [
                'residential_delivery' => $shipment->door_to_door,
                'is_remote_area' => $isRemoteArea,
            ]);

            // Calculate insurance
            $insuranceCost = 0;
            if ($shipment->insurance_required && $shipment->declared_value) {
                $insuranceCost = $this->calculateInsurance((float) $shipment->declared_value);
            }

            // Add customs clearance fee if not in surcharges
            $customsFee = 0;
            if ($shipment->customs_clearance) {
                $customsFee = 150;
            }

            $totalPrice = $baseRate + $surcharges['total'] + $insuranceCost + $customsFee;

            $quotes[] = [
                'carrier_id' => $this->carrier->id,
                'price' => round($totalPrice, 2),
                'currency' => $this->getCurrency(),
                'base_rate' => round($baseRate, 2),
                'surcharges' => $surcharges,
                'insurance_cost' => $insuranceCost,
                'customs_fee' => $customsFee,
                'billable_weight' => round($chargeableWeight, 2),
                'delivery_days' => $transitDaysMax,
                'delivery_days_min' => $transitDaysMin,
                'delivery_days_max' => $transitDaysMax,
                'estimated_delivery_date' => now()->addDays($transitDaysMax)->toDateString(),
                'transport_type' => $transportType,
                'services_included' => $this->getServicesIncluded($shipment),
                'valid_until' => now()->addDays(7),
            ];
        }

        // Cache the result
        if (!empty($quotes)) {
            $this->cacheRate($cacheParams, $quotes, 60);
        }

        return $quotes;
    }

    public function createOrder(Order $order): CarrierOrderResponse
    {
        // Simulate order creation
        $carrierOrderId = 'MOCK-' . strtoupper(uniqid());
        $trackingNumber = 'TRK' . rand(100000000, 999999999);

        return CarrierOrderResponse::success($carrierOrderId, $trackingNumber, [
            'estimated_pickup' => now()->addDays(1)->toDateString(),
        ]);
    }

    public function getTrackingStatus(string $trackingNumber): TrackingStatus
    {
        // Return mock tracking status
        return new TrackingStatus(
            status: 'in_transit',
            locationCity: 'Transit Hub',
            locationCountry: 'Kazakhstan',
            description: 'Package is in transit',
            timestamp: now(),
            events: [
                [
                    'status' => 'picked_up',
                    'location' => 'Origin City',
                    'timestamp' => now()->subDays(2)->toIso8601String(),
                ],
                [
                    'status' => 'in_transit',
                    'location' => 'Transit Hub',
                    'timestamp' => now()->subDay()->toIso8601String(),
                ],
            ],
        );
    }

    public function cancelOrder(string $orderNumber): bool
    {
        // Simulate successful cancellation
        return true;
    }

    public function getShippingLabel(Order $order): string
    {
        // Return a placeholder label URL
        return 'https://example.com/labels/' . $order->tracking_number . '.pdf';
    }

    private function getBaseRates(): array
    {
        return [
            'air' => 15.00,    // per kg
            'sea' => 3.00,    // per kg
            'rail' => 5.00,   // per kg
            'road' => 8.00,   // per kg
        ];
    }

    private function getDeliveryDays(string $transportType): int
    {
        return match ($transportType) {
            'air' => rand(3, 7),
            'sea' => rand(25, 40),
            'rail' => rand(15, 25),
            'road' => rand(7, 14),
            default => rand(10, 20),
        };
    }

    private function getServicesIncluded(Shipment $shipment): array
    {
        $services = [];

        if ($shipment->door_to_door) {
            $services[] = 'door_pickup';
            $services[] = 'door_delivery';
        }

        if ($shipment->customs_clearance) {
            $services[] = 'customs_clearance';
        }

        if ($shipment->insurance_required) {
            $services[] = 'insurance';
        }

        return $services;
    }
}
