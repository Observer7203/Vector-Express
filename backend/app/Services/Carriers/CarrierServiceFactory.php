<?php

namespace App\Services\Carriers;

use App\Models\Carrier;
use InvalidArgumentException;

class CarrierServiceFactory
{
    public function make(Carrier $carrier): CarrierServiceInterface
    {
        // Manual carriers use rate cards from database
        if ($carrier->api_type === 'manual') {
            return new ManualCarrierService($carrier);
        }

        // Check if carrier has valid API configuration
        $config = $carrier->api_config;
        $hasValidConfig = is_array($config) && !empty($config);

        // Use mock service for testing/development if no real API credentials
        if (!$hasValidConfig) {
            return new MockCarrierService($carrier);
        }

        return match ($carrier->api_type) {
            'dhl' => new DhlCarrierService($carrier),
            'fedex' => new FedexCarrierService($carrier),
            'ups' => new UpsCarrierService($carrier),
            'ponyexpress' => new PonyexpressCarrierService($carrier),
            'mock' => new MockCarrierService($carrier),
            default => new MockCarrierService($carrier),
        };
    }
}
