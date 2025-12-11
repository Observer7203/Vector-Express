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
            null
        );

        $destinationZone = $this->findZone(
            $shipment->destination_country,
            $shipment->destination_city,
            null
        );

        // If transport type is null (any), get quotes for all available types
        if ($shipment->transport_type === null) {
            return $this->getQuotesForAllTransportTypes($shipment, $originZone, $destinationZone, $billableWeight);
        }

        return $this->buildQuoteForTransportType(
            $shipment,
            $originZone,
            $destinationZone,
            $billableWeight,
            $shipment->transport_type
        );
    }

    private function getQuotesForAllTransportTypes(
        Shipment $shipment,
        $originZone,
        $destinationZone,
        float $billableWeight
    ): array {
        $quotes = [];
        $transportTypes = $this->carrier->supported_transport_types ?? ['road', 'rail', 'air', 'sea'];

        foreach ($transportTypes as $transportType) {
            $quote = $this->buildQuoteForTransportType(
                $shipment,
                $originZone,
                $destinationZone,
                $billableWeight,
                $transportType
            );
            if (!empty($quote)) {
                $quotes = array_merge($quotes, $quote);
            }
        }

        return $quotes;
    }

    private function buildQuoteForTransportType(
        Shipment $shipment,
        $originZone,
        $destinationZone,
        float $billableWeight,
        string $transportType
    ): array {
        $rateCard = $this->findRateCard(
            $originZone,
            $destinationZone,
            $billableWeight,
            $transportType
        );

        if (!$rateCard) {
            return [];
        }

        $baseRate = $this->calculateBaseRate($rateCard, $billableWeight);

        $pricingRule = $this->carrier->activePricingRule();
        if ($pricingRule?->minimum_charge && $baseRate < $pricingRule->minimum_charge) {
            $baseRate = (float) $pricingRule->minimum_charge;
        }

        $surcharges = $this->calculateSurcharges($baseRate, $billableWeight, $transportType, [
            'residential_delivery' => $shipment->door_to_door,
        ]);

        $insuranceCost = 0;
        if ($shipment->insurance_required && $shipment->declared_value) {
            $insuranceCost = $this->calculateInsurance((float) $shipment->declared_value);
        }

        $totalPrice = $baseRate + $surcharges['total'] + $insuranceCost;

        $transitDaysMin = $rateCard->transit_days_min ?? 3;
        $transitDaysMax = $rateCard->transit_days_max ?? 7;

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
                'transport_type' => $transportType,
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
