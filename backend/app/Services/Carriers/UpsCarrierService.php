<?php

namespace App\Services\Carriers;

use App\Models\Order;
use App\Models\Shipment;

class UpsCarrierService extends AbstractCarrierService
{
    public function getQuotes(Shipment $shipment): array
    {
        // TODO: Implement UPS API integration
        return (new MockCarrierService($this->carrier))->getQuotes($shipment);
    }

    public function createOrder(Order $order): CarrierOrderResponse
    {
        // TODO: Implement UPS API integration
        return (new MockCarrierService($this->carrier))->createOrder($order);
    }

    public function getTrackingStatus(string $trackingNumber): TrackingStatus
    {
        // TODO: Implement UPS API integration
        return (new MockCarrierService($this->carrier))->getTrackingStatus($trackingNumber);
    }

    public function cancelOrder(string $orderNumber): bool
    {
        return true;
    }

    public function getShippingLabel(Order $order): string
    {
        return '';
    }
}
