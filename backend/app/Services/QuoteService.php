<?php

namespace App\Services;

use App\Models\Carrier;
use App\Models\Quote;
use App\Models\Shipment;
use App\Services\Carriers\CarrierServiceFactory;
use Illuminate\Support\Facades\Log;

class QuoteService
{
    public function __construct(
        private CarrierServiceFactory $carrierServiceFactory
    ) {}

    public function getQuotesForShipment(Shipment $shipment): array
    {
        // Get all active carriers that support the shipment's requirements
        $carriers = $this->getEligibleCarriers($shipment);

        $allQuotes = [];

        foreach ($carriers as $carrier) {
            try {
                $carrierService = $this->carrierServiceFactory->make($carrier);
                $quotes = $carrierService->getQuotes($shipment);

                foreach ($quotes as $quoteData) {
                    $quote = Quote::create([
                        'shipment_id' => $shipment->id,
                        ...$quoteData,
                    ]);

                    $allQuotes[] = $quote->load('carrier.company');
                }
            } catch (\Exception $e) {
                Log::error("Failed to get quotes from carrier {$carrier->id}", [
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $allQuotes;
    }

    private function getEligibleCarriers(Shipment $shipment): \Illuminate\Support\Collection
    {
        return Carrier::query()
            ->where('is_active', true)
            ->with('company')
            ->get()
            ->filter(function (Carrier $carrier) use ($shipment) {
                // Check if carrier supports the transport type
                if ($shipment->transport_type) {
                    if (!$carrier->supportsTransportType($shipment->transport_type)) {
                        return false;
                    }
                }

                // Check if carrier supports origin and destination countries
                if (!$carrier->supportsCountry($shipment->origin_country) ||
                    !$carrier->supportsCountry($shipment->destination_country)) {
                    return false;
                }

                // Check if carrier's company is verified
                if (!$carrier->company?->verified) {
                    return false;
                }

                return true;
            });
    }
}
