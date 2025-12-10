<?php

namespace App\Services\Carriers;

use App\Models\Order;
use App\Models\Shipment;

interface CarrierServiceInterface
{
    /**
     * Get quotes for a shipment
     *
     * @param Shipment $shipment
     * @return array Array of quote data
     */
    public function getQuotes(Shipment $shipment): array;

    /**
     * Create an order with the carrier
     *
     * @param Order $order
     * @return CarrierOrderResponse
     */
    public function createOrder(Order $order): CarrierOrderResponse;

    /**
     * Get tracking status for an order
     *
     * @param string $trackingNumber
     * @return TrackingStatus
     */
    public function getTrackingStatus(string $trackingNumber): TrackingStatus;

    /**
     * Cancel an order with the carrier
     *
     * @param string $orderNumber
     * @return bool
     */
    public function cancelOrder(string $orderNumber): bool;

    /**
     * Get shipping label for an order
     *
     * @param Order $order
     * @return string URL or base64 encoded PDF
     */
    public function getShippingLabel(Order $order): string;
}
