<?php

namespace App\Services\Carriers;

class CarrierOrderResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $carrierOrderId = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $error = null,
        public readonly array $data = [],
    ) {}

    public static function success(string $carrierOrderId, string $trackingNumber, array $data = []): self
    {
        return new self(
            success: true,
            carrierOrderId: $carrierOrderId,
            trackingNumber: $trackingNumber,
            data: $data,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }
}
