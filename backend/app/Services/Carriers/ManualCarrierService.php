<?php

namespace App\Services\Carriers;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Str;

/**
 * Service for carriers that don't have API integration
 * Uses internal rate cards and pricing rules
 */
class ManualCarrierService extends AbstractCarrierService
{
    public function getQuotes(Shipment $shipment): array
    {
        if (!$this->supportsRoute(
            $shipment->origin_country,
            $shipment->destination_country,
            $shipment->transport_type
        )) {
            return [];
        }

        $billableWeight = $this->getChargeableWeight($shipment);

        // Find zones
        $originZone = $this->findZone(
            $shipment->origin_country,
            $shipment->origin_city,
            null // postal code if available
        );

        $destinationZone = $this->findZone(
            $shipment->destination_country,
            $shipment->destination_city,
            null
        );

        // Get rate card
        $rateCard = $this->findRateCard(
            $originZone,
            $destinationZone,
            $billableWeight,
            $shipment->transport_type
        );

        if (!$rateCard) {
            return [];
        }

        // Calculate base rate using parent method
        $baseRate = $this->calculateBaseRate($rateCard, $billableWeight);

        // Apply minimum charge
        $pricingRule = $this->carrier->activePricingRule();
        if ($pricingRule?->minimum_charge && $baseRate < $pricingRule->minimum_charge) {
            $baseRate = (float) $pricingRule->minimum_charge;
        }

        // Calculate surcharges
        $surcharges = $this->calculateSurcharges($baseRate, $billableWeight, $shipment->transport_type, [
            'residential_delivery' => $shipment->door_to_door,
        ]);

        // Calculate insurance if required
        $insuranceCost = 0;
        if ($shipment->insurance_required && $shipment->declared_value) {
            $insuranceCost = $this->calculateInsurance((float) $shipment->declared_value);
        }

        // Total price
        $totalPrice = $baseRate + $surcharges['total'] + $insuranceCost;

        // Determine transit time
        $transitDaysMin = $rateCard->transit_days_min ?? 3;
        $transitDaysMax = $rateCard->transit_days_max ?? 7;

        // Build services included
        $servicesIncluded = [];
        if ($shipment->door_to_door) {
            $servicesIncluded[] = 'door_pickup';
            $servicesIncluded[] = 'door_delivery';
        }
        if ($shipment->customs_clearance) {
            $servicesIncluded[] = 'customs_clearance';
        }
        if ($shipment->insurance_required) {
            $servicesIncluded[] = 'insurance';
        }

        return [
            [
                'carrier_id' => $this->carrier->id,
                'price' => round($totalPrice, 2),
                'currency' => $pricingRule?->currency ?? 'USD',
                'base_rate' => round($baseRate, 2),
                'surcharges' => $surcharges,
                'insurance_cost' => $insuranceCost,
                'billable_weight' => round($billableWeight, 2),
                'delivery_days' => $transitDaysMax,
                'delivery_days_min' => $transitDaysMin,
                'delivery_days_max' => $transitDaysMax,
                'estimated_delivery_date' => now()->addDays($transitDaysMax)->format('Y-m-d'),
                'transport_type' => $shipment->transport_type ?? $rateCard->transport_type,
                'services_included' => $servicesIncluded,
                'valid_until' => now()->addDays(7)->format('Y-m-d H:i:s'),
            ],
        ];
    }

    public function createOrder(Order $order): CarrierOrderResponse
    {
        // For manual carriers, we just generate internal tracking number
        $trackingNumber = 'VE-' . strtoupper(Str::random(10));

        return CarrierOrderResponse::success(
            carrierOrderId: $trackingNumber,
            trackingNumber: $trackingNumber,
            data: ['message' => 'Order created successfully. Carrier will be notified.']
        );
    }

    public function getTrackingStatus(string $trackingNumber): TrackingStatus
    {
        // Manual carriers update status through admin panel
        return new TrackingStatus(
            trackingNumber: $trackingNumber,
            status: 'pending',
            events: [],
        );
    }

    public function cancelOrder(string $orderNumber): bool
    {
        // Manual cancellation - just return true
        // Actual cancellation handled by notifying carrier
        return true;
    }

    public function getShippingLabel(Order $order): string
    {
        // Manual carriers don't provide automatic labels
        return '';
    }
}
