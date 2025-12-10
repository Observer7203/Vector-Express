<?php

namespace App\Services\Carriers;

use App\Models\Order;
use App\Models\Shipment;

class MockCarrierService extends AbstractCarrierService
{
    public function getQuotes(Shipment $shipment): array
    {
        $chargeableWeight = $this->getChargeableWeight($shipment);
        $baseRates = $this->getBaseRates();

        $quotes = [];

        foreach ($this->carrier->supported_transport_types ?? ['road'] as $transportType) {
            $rate = $baseRates[$transportType] ?? 10;
            $deliveryDays = $this->getDeliveryDays($transportType);

            $price = $chargeableWeight * $rate;

            // Add insurance if required
            if ($shipment->insurance_required) {
                $price += $shipment->declared_value * 0.01; // 1% of declared value
            }

            // Add customs clearance fee
            if ($shipment->customs_clearance) {
                $price += 150;
            }

            // Add door-to-door surcharge
            if ($shipment->door_to_door) {
                $price += 50;
            }

            $quotes[] = [
                'carrier_id' => $this->carrier->id,
                'price' => round($price, 2),
                'currency' => $shipment->currency ?? 'USD',
                'delivery_days' => $deliveryDays,
                'estimated_delivery_date' => now()->addDays($deliveryDays)->toDateString(),
                'transport_type' => $transportType,
                'services_included' => $this->getServicesIncluded($shipment),
                'valid_until' => now()->addDays(7),
            ];
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
