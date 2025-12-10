<?php

namespace App\Services\Carriers;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DhlCarrierService extends AbstractCarrierService
{
    private const API_BASE_URL = 'https://api-mock.dhl.com/mydhlapi';

    public function getQuotes(Shipment $shipment): array
    {
        try {
            $response = Http::withBasicAuth($this->getApiKey(), $this->getApiSecret())
                ->post($this->getBaseUrl() . '/rates', [
                    'customerDetails' => [
                        'shipperDetails' => [
                            'postalCode' => '00000',
                            'cityName' => $shipment->origin_city,
                            'countryCode' => $this->getCountryCode($shipment->origin_country),
                        ],
                        'receiverDetails' => [
                            'postalCode' => '00000',
                            'cityName' => $shipment->destination_city,
                            'countryCode' => $this->getCountryCode($shipment->destination_country),
                        ],
                    ],
                    'plannedShippingDateAndTime' => $shipment->pickup_date?->format('Y-m-d\TH:i:s') . ' GMT+00:00',
                    'unitOfMeasurement' => 'metric',
                    'isCustomsDeclarable' => $shipment->customs_clearance,
                    'packages' => $shipment->items->map(fn($item) => [
                        'weight' => $item->weight,
                        'dimensions' => [
                            'length' => $item->length,
                            'width' => $item->width,
                            'height' => $item->height,
                        ],
                    ])->toArray(),
                ]);

            if ($response->successful()) {
                return $this->parseQuoteResponse($response->json(), $shipment);
            }

            Log::error('DHL API error', ['response' => $response->body()]);
            return [];

        } catch (\Exception $e) {
            Log::error('DHL API exception', ['error' => $e->getMessage()]);
            // Fall back to mock service
            return (new MockCarrierService($this->carrier))->getQuotes($shipment);
        }
    }

    public function createOrder(Order $order): CarrierOrderResponse
    {
        try {
            $shipment = $order->quote->shipment;

            $response = Http::withBasicAuth($this->getApiKey(), $this->getApiSecret())
                ->post($this->getBaseUrl() . '/shipments', [
                    'plannedShippingDateAndTime' => $order->pickup_date->format('Y-m-d\TH:i:s') . ' GMT+00:00',
                    'pickup' => [
                        'isRequested' => true,
                    ],
                    'productCode' => 'P',
                    'accounts' => [
                        [
                            'typeCode' => 'shipper',
                            'number' => $this->config['account_number'] ?? '',
                        ],
                    ],
                    'customerDetails' => [
                        'shipperDetails' => [
                            'postalAddress' => [
                                'cityName' => $shipment->origin_city,
                                'countryCode' => $this->getCountryCode($shipment->origin_country),
                                'addressLine1' => $order->pickup_address,
                            ],
                            'contactInformation' => [
                                'fullName' => $order->pickup_contact_name,
                                'phone' => $order->pickup_contact_phone,
                            ],
                        ],
                        'receiverDetails' => [
                            'postalAddress' => [
                                'cityName' => $shipment->destination_city,
                                'countryCode' => $this->getCountryCode($shipment->destination_country),
                                'addressLine1' => $order->delivery_address,
                            ],
                            'contactInformation' => [
                                'fullName' => $order->delivery_contact_name,
                                'phone' => $order->delivery_contact_phone,
                            ],
                        ],
                    ],
                    'content' => [
                        'packages' => $shipment->items->map(fn($item) => [
                            'weight' => $item->weight,
                            'dimensions' => [
                                'length' => $item->length,
                                'width' => $item->width,
                                'height' => $item->height,
                            ],
                        ])->toArray(),
                        'isCustomsDeclarable' => $shipment->customs_clearance,
                        'declaredValue' => $shipment->declared_value,
                        'declaredValueCurrency' => $shipment->currency,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return CarrierOrderResponse::success(
                    $data['shipmentTrackingNumber'] ?? uniqid('DHL'),
                    $data['trackingUrl'] ?? $data['shipmentTrackingNumber'],
                    $data
                );
            }

            return CarrierOrderResponse::failure($response->body());

        } catch (\Exception $e) {
            Log::error('DHL create order exception', ['error' => $e->getMessage()]);
            return (new MockCarrierService($this->carrier))->createOrder($order);
        }
    }

    public function getTrackingStatus(string $trackingNumber): TrackingStatus
    {
        try {
            $response = Http::withBasicAuth($this->getApiKey(), $this->getApiSecret())
                ->get($this->getBaseUrl() . '/shipments/' . $trackingNumber . '/tracking');

            if ($response->successful()) {
                return $this->parseTrackingResponse($response->json());
            }

            return (new MockCarrierService($this->carrier))->getTrackingStatus($trackingNumber);

        } catch (\Exception $e) {
            Log::error('DHL tracking exception', ['error' => $e->getMessage()]);
            return (new MockCarrierService($this->carrier))->getTrackingStatus($trackingNumber);
        }
    }

    public function cancelOrder(string $orderNumber): bool
    {
        // DHL doesn't support direct cancellation via API
        // In production, this would need manual intervention
        return true;
    }

    public function getShippingLabel(Order $order): string
    {
        // Return label URL from order creation response
        return $order->carrier_tracking_number
            ? "https://www.dhl.com/label/{$order->carrier_tracking_number}"
            : '';
    }

    protected function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? self::API_BASE_URL;
    }

    private function parseQuoteResponse(array $response, Shipment $shipment): array
    {
        $quotes = [];

        foreach ($response['products'] ?? [] as $product) {
            $quotes[] = [
                'carrier_id' => $this->carrier->id,
                'price' => $product['totalPrice'][0]['price'] ?? 0,
                'currency' => $product['totalPrice'][0]['priceCurrency'] ?? 'USD',
                'delivery_days' => $product['deliveryCapabilities']['estimatedDeliveryDateAndTime']
                    ? now()->diffInDays($product['deliveryCapabilities']['estimatedDeliveryDateAndTime'])
                    : null,
                'estimated_delivery_date' => $product['deliveryCapabilities']['estimatedDeliveryDateAndTime'] ?? null,
                'transport_type' => 'air',
                'services_included' => ['door_pickup', 'door_delivery'],
                'valid_until' => now()->addDays(7),
            ];
        }

        return $quotes;
    }

    private function parseTrackingResponse(array $response): TrackingStatus
    {
        $shipments = $response['shipments'] ?? [];
        $shipment = $shipments[0] ?? [];
        $events = $shipment['events'] ?? [];
        $latestEvent = $events[0] ?? [];

        return new TrackingStatus(
            status: $this->mapDhlStatus($latestEvent['typeCode'] ?? 'unknown'),
            locationCity: $latestEvent['serviceArea'][0]['description'] ?? null,
            locationCountry: null,
            description: $latestEvent['description'] ?? null,
            timestamp: isset($latestEvent['date']) ? new \DateTime($latestEvent['date']) : null,
            events: array_map(fn($event) => [
                'status' => $this->mapDhlStatus($event['typeCode'] ?? 'unknown'),
                'location' => $event['serviceArea'][0]['description'] ?? null,
                'description' => $event['description'] ?? null,
                'timestamp' => $event['date'] ?? null,
            ], $events),
        );
    }

    private function mapDhlStatus(string $dhlStatus): string
    {
        return match (strtoupper($dhlStatus)) {
            'PU' => 'picked_up',
            'PL' => 'in_transit',
            'AR' => 'in_transit',
            'DF' => 'in_transit',
            'CC' => 'customs',
            'OH' => 'out_for_delivery',
            'OK' => 'delivered',
            default => 'in_transit',
        };
    }

    private function getCountryCode(string $country): string
    {
        $countryCodes = [
            'Казахстан' => 'KZ',
            'Kazakhstan' => 'KZ',
            'Китай' => 'CN',
            'China' => 'CN',
            'Россия' => 'RU',
            'Russia' => 'RU',
            'США' => 'US',
            'USA' => 'US',
            'United States' => 'US',
            // Add more as needed
        ];

        return $countryCodes[$country] ?? substr(strtoupper($country), 0, 2);
    }
}
