<?php

namespace App\Services\Carriers;

use App\Models\Order;
use App\Models\Shipment;

class FedexCarrierService extends AbstractCarrierService
{
    public function getQuotes(Shipment $shipment): array
    {
        // TODO: Implement FedEx API integration
        // For now, use mock service with FedEx-like pricing
        return (new MockCarrierService($this->carrier))->getQuotes($shipment);
    }

    public function createOrder(Order $order): CarrierOrderResponse
    {
        // TODO: Implement FedEx API integration
        return (new MockCarrierService($this->carrier))->createOrder($order);
    }

    public function getTrackingStatus(string $trackingNumber): TrackingStatus
    {
        // TODO: Implement FedEx API integration
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
