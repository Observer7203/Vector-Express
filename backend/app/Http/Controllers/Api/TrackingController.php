<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tracking_number' => ['required', 'string'],
        ]);

        $order = Order::where('tracking_number', $validated['tracking_number'])
            ->orWhere('order_number', $validated['tracking_number'])
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'order' => [
                'order_number' => $order->order_number,
                'tracking_number' => $order->tracking_number,
                'status' => $order->status,
                'carrier' => $order->carrier->company->name ?? 'Unknown',
                'origin' => $order->quote->shipment->origin_city . ', ' . $order->quote->shipment->origin_country,
                'destination' => $order->quote->shipment->destination_city . ', ' . $order->quote->shipment->destination_country,
                'pickup_date' => $order->pickup_date,
                'estimated_delivery' => $order->quote->estimated_delivery_date,
            ],
            'events' => $order->trackingEvents->map(function ($event) {
                return [
                    'status' => $event->status,
                    'location' => $event->location,
                    'description' => $event->description,
                    'timestamp' => $event->event_time,
                    'coordinates' => $event->hasCoordinates() ? [
                        'lat' => $event->latitude,
                        'lng' => $event->longitude,
                    ] : null,
                ];
            }),
        ]);
    }

    public function orderTracking(Request $request, Order $order): JsonResponse
    {
        // Public tracking doesn't require authorization
        // but we limit the information returned

        return response()->json([
            'order_number' => $order->order_number,
            'tracking_number' => $order->tracking_number,
            'current_status' => $order->status,
            'carrier' => $order->carrier->company->name ?? 'Unknown',
            'events' => $order->trackingEvents->map(function ($event) {
                return [
                    'status' => $event->status,
                    'location' => $event->location,
                    'description' => $event->description,
                    'timestamp' => $event->event_time,
                ];
            }),
        ]);
    }
}
