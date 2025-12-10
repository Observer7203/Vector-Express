<?php

namespace App\Services\Carriers;

use App\Models\Carrier;
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

    protected function calculateVolumetricWeight(Shipment $shipment, int $divisor = 5000): float
    {
        $volumetricWeight = 0;

        foreach ($shipment->items as $item) {
            $volumetricWeight += ($item->length * $item->width * $item->height / $divisor) * $item->quantity;
        }

        return $volumetricWeight;
    }

    protected function getChargeableWeight(Shipment $shipment): float
    {
        $actualWeight = $shipment->total_weight;
        $volumetricWeight = $this->calculateVolumetricWeight($shipment);

        return max($actualWeight, $volumetricWeight);
    }
}
