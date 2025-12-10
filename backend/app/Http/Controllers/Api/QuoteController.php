<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    /**
     * Get quote details
     */
    public function show(Request $request, Quote $quote): JsonResponse
    {
        $shipment = $quote->shipment;

        if ($shipment->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'quote' => $quote->load(['carrier.company', 'shipment.items']),
        ]);
    }

    /**
     * Select a quote and create an order
     */
    public function select(Request $request, Quote $quote): JsonResponse
    {
        $shipment = $quote->shipment;

        if ($shipment->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($shipment->status !== 'quoted') {
            return response()->json([
                'message' => 'Shipment is not in quoted status',
            ], 400);
        }

        if ($quote->valid_until && $quote->valid_until < now()) {
            return response()->json([
                'message' => 'Quote has expired',
            ], 400);
        }

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'contact_email' => ['required', 'email', 'max:255'],
            'pickup_contact_name' => ['nullable', 'string', 'max:255'],
            'pickup_contact_phone' => ['nullable', 'string', 'max:50'],
            'pickup_address' => ['nullable', 'string'],
            'pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'pickup_time_from' => ['nullable', 'date_format:H:i'],
            'pickup_time_to' => ['nullable', 'date_format:H:i', 'after:pickup_time_from'],
            'delivery_contact_name' => ['nullable', 'string', 'max:255'],
            'delivery_contact_phone' => ['nullable', 'string', 'max:50'],
            'delivery_address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // Mark quote as selected
        $quote->update(['is_selected' => true]);

        // Update shipment status
        $shipment->update(['status' => 'ordered']);

        // Generate order number
        $orderNumber = 'VE-' . date('Y') . '-' . str_pad(Order::count() + 1, 6, '0', STR_PAD_LEFT);

        // Calculate commission (5%)
        $commission = $quote->price * 0.05;

        // Create order
        $order = Order::create([
            'order_number' => $orderNumber,
            'quote_id' => $quote->id,
            'user_id' => $request->user()->id,
            'carrier_id' => $quote->carrier_id,
            'status' => 'pending',
            'contact_name' => $validated['contact_name'],
            'contact_phone' => $validated['contact_phone'],
            'contact_email' => $validated['contact_email'],
            'pickup_contact_name' => $validated['pickup_contact_name'] ?? $validated['contact_name'],
            'pickup_contact_phone' => $validated['pickup_contact_phone'] ?? $validated['contact_phone'],
            'pickup_address' => $validated['pickup_address'] ?? $shipment->origin_address,
            'pickup_date' => $validated['pickup_date'] ?? $shipment->pickup_date,
            'pickup_time_from' => $validated['pickup_time_from'] ?? null,
            'pickup_time_to' => $validated['pickup_time_to'] ?? null,
            'delivery_contact_name' => $validated['delivery_contact_name'] ?? $validated['contact_name'],
            'delivery_contact_phone' => $validated['delivery_contact_phone'] ?? $validated['contact_phone'],
            'delivery_address' => $validated['delivery_address'] ?? $shipment->destination_address,
            'tracking_number' => $orderNumber,
            'total_amount' => $quote->price,
            'commission_amount' => $commission,
            'currency' => $quote->currency,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load(['quote.carrier.company', 'quote.shipment']),
        ], 201);
    }

    /**
     * Compare multiple quotes
     */
    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_ids' => ['required', 'array', 'min:2', 'max:5'],
            'quote_ids.*' => ['required', 'integer', 'exists:quotes,id'],
        ]);

        $quotes = Quote::whereIn('id', $validated['quote_ids'])
            ->with(['carrier.company', 'shipment'])
            ->get();

        // Verify user owns all shipments
        foreach ($quotes as $quote) {
            if ($quote->shipment->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        return response()->json([
            'quotes' => $quotes,
            'comparison' => [
                'cheapest' => $quotes->sortBy('price')->first(),
                'fastest' => $quotes->sortBy('delivery_days')->first(),
                'best_rated' => $quotes->sortByDesc(fn($q) => $q->carrier->company->rating ?? 0)->first(),
            ],
        ]);
    }
}
