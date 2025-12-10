<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Quote;
use App\Models\TrackingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Order::with(['quote.shipment', 'carrier.company', 'user']);

        if ($user->isCarrier()) {
            $carrierId = $user->company?->carrier?->id;
            $query->where('carrier_id', $carrierId);
        } else {
            $query->where('user_id', $user->id);
        }

        $orders = $query->latest()->paginate(15);

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_id' => ['required', 'exists:quotes,id'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'contact_email' => ['required', 'email', 'max:255'],
            'pickup_contact_name' => ['required', 'string', 'max:255'],
            'pickup_contact_phone' => ['required', 'string', 'max:50'],
            'pickup_address' => ['required', 'string'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'pickup_time_from' => ['nullable', 'date_format:H:i'],
            'pickup_time_to' => ['nullable', 'date_format:H:i', 'after:pickup_time_from'],
            'delivery_contact_name' => ['required', 'string', 'max:255'],
            'delivery_contact_phone' => ['required', 'string', 'max:50'],
            'delivery_address' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $quote = Quote::findOrFail($validated['quote_id']);

        if (!$quote->isValid()) {
            return response()->json([
                'message' => 'Quote has expired',
            ], 400);
        }

        if ($quote->shipment->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Mark quote as selected
        $quote->select();

        // Update shipment status
        $quote->shipment->update(['status' => 'ordered']);

        // Create order
        $order = Order::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'carrier_id' => $quote->carrier_id,
            'total_amount' => $quote->price,
            'commission_amount' => $quote->price * Invoice::COMMISSION_RATE,
            'currency' => $quote->currency,
        ]);

        // Create chat
        $chat = Chat::create(['order_id' => $order->id]);
        ChatParticipant::create([
            'chat_id' => $chat->id,
            'user_id' => $request->user()->id,
            'role' => 'customer',
        ]);

        // Create initial tracking event
        TrackingEvent::create([
            'order_id' => $order->id,
            'status' => 'pending',
            'description' => 'Order created, awaiting carrier confirmation',
            'event_time' => now(),
        ]);

        // Create invoice
        $invoiceData = Invoice::calculateFromOrder($order);
        Invoice::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            ...$invoiceData,
            'currency' => $order->currency,
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load(['quote.shipment', 'carrier.company', 'chat', 'invoice']),
        ], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'order' => $order->load([
                'quote.shipment.items',
                'carrier.company',
                'user',
                'trackingEvents',
                'chat.messages',
                'invoice',
                'review',
            ]),
        ]);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'Cannot update order in current status',
            ], 400);
        }

        $validated = $request->validate([
            'contact_name' => ['sometimes', 'string', 'max:255'],
            'contact_phone' => ['sometimes', 'string', 'max:50'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
            'pickup_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'pickup_time_from' => ['nullable', 'date_format:H:i'],
            'pickup_time_to' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ]);

        $order->update($validated);

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order->fresh(),
        ]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        if (!$order->canBeCancelled()) {
            return response()->json([
                'message' => 'Order cannot be cancelled in current status',
            ], 400);
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order->cancel($request->reason);

        // Cancel invoice
        if ($order->invoice && $order->invoice->isPending()) {
            $order->invoice->update(['status' => 'cancelled']);
        }

        // Create tracking event
        TrackingEvent::create([
            'order_id' => $order->id,
            'status' => 'cancelled',
            'description' => 'Order cancelled: ' . ($request->reason ?? 'No reason provided'),
            'event_time' => now(),
        ]);

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order->fresh(),
        ]);
    }

    public function confirm(Request $request, Order $order): JsonResponse
    {
        $this->authorize('confirm', $order);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Order is not pending confirmation',
            ], 400);
        }

        $order->confirm();

        // Add carrier user to chat
        $carrierUser = $order->carrier->company->users()->first();
        if ($carrierUser) {
            ChatParticipant::firstOrCreate([
                'chat_id' => $order->chat->id,
                'user_id' => $carrierUser->id,
            ], [
                'role' => 'carrier',
            ]);
        }

        TrackingEvent::create([
            'order_id' => $order->id,
            'status' => 'confirmed',
            'description' => 'Order confirmed by carrier',
            'event_time' => now(),
        ]);

        return response()->json([
            'message' => 'Order confirmed successfully',
            'order' => $order->fresh(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorize('updateStatus', $order);

        $validated = $request->validate([
            'status' => ['required', 'in:pickup_scheduled,picked_up,in_transit,customs,out_for_delivery,delivered'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
        ]);

        $order->updateStatus($validated['status']);

        TrackingEvent::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'location_city' => $validated['location_city'] ?? null,
            'location_country' => $validated['location_country'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'description' => $validated['description'] ?? null,
            'event_time' => now(),
        ]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->fresh()->load('trackingEvents'),
        ]);
    }

    public function tracking(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'carrier_tracking' => $order->carrier_tracking_number,
            'current_status' => $order->status,
            'events' => $order->trackingEvents,
        ]);
    }
}
