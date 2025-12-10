<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        private QuoteService $quoteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $shipments = $request->user()
            ->shipments()
            ->with(['items', 'quotes.carrier.company'])
            ->latest()
            ->paginate(15);

        return response()->json($shipments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_country' => ['required', 'string', 'max:100'],
            'origin_city' => ['required', 'string', 'max:100'],
            'origin_address' => ['nullable', 'string'],
            'destination_country' => ['required', 'string', 'max:100'],
            'destination_city' => ['required', 'string', 'max:100'],
            'destination_address' => ['nullable', 'string'],
            'transport_type' => ['nullable', 'in:air,sea,rail,road,multimodal'],
            'cargo_type' => ['nullable', 'in:general,dangerous,fragile,perishable'],
            'packaging_type' => ['nullable', 'in:box,pallet,container'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'insurance_required' => ['boolean'],
            'customs_clearance' => ['boolean'],
            'door_to_door' => ['boolean'],
            'pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.length' => ['required', 'numeric', 'min:0'],
            'items.*.width' => ['required', 'numeric', 'min:0'],
            'items.*.height' => ['required', 'numeric', 'min:0'],
            'items.*.weight' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $shipment = $request->user()->shipments()->create([
            ...$validated,
            'status' => 'draft',
        ]);

        foreach ($validated['items'] as $item) {
            $shipment->items()->create($item);
        }

        $shipment->calculateTotals();

        return response()->json([
            'message' => 'Shipment created successfully',
            'shipment' => $shipment->load('items'),
        ], 201);
    }

    public function show(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);

        return response()->json([
            'shipment' => $shipment->load(['items', 'quotes.carrier.company', 'selectedQuote']),
        ]);
    }

    public function update(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('update', $shipment);

        if (!$shipment->isDraft()) {
            return response()->json([
                'message' => 'Cannot update shipment that is not in draft status',
            ], 400);
        }

        $validated = $request->validate([
            'origin_country' => ['sometimes', 'string', 'max:100'],
            'origin_city' => ['sometimes', 'string', 'max:100'],
            'origin_address' => ['nullable', 'string'],
            'destination_country' => ['sometimes', 'string', 'max:100'],
            'destination_city' => ['sometimes', 'string', 'max:100'],
            'destination_address' => ['nullable', 'string'],
            'transport_type' => ['nullable', 'in:air,sea,rail,road,multimodal'],
            'cargo_type' => ['nullable', 'in:general,dangerous,fragile,perishable'],
            'packaging_type' => ['nullable', 'in:box,pallet,container'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'insurance_required' => ['boolean'],
            'customs_clearance' => ['boolean'],
            'door_to_door' => ['boolean'],
            'pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.length' => ['required', 'numeric', 'min:0'],
            'items.*.width' => ['required', 'numeric', 'min:0'],
            'items.*.height' => ['required', 'numeric', 'min:0'],
            'items.*.weight' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $shipment->update($validated);

        if (isset($validated['items'])) {
            $shipment->items()->delete();
            foreach ($validated['items'] as $item) {
                $shipment->items()->create($item);
            }
            $shipment->calculateTotals();
        }

        return response()->json([
            'message' => 'Shipment updated successfully',
            'shipment' => $shipment->fresh()->load('items'),
        ]);
    }

    public function destroy(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('delete', $shipment);

        if (!$shipment->isDraft()) {
            return response()->json([
                'message' => 'Cannot delete shipment that is not in draft status',
            ], 400);
        }

        $shipment->delete();

        return response()->json([
            'message' => 'Shipment deleted successfully',
        ]);
    }

    public function calculate(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('calculate', $shipment);

        $shipment->update(['status' => 'calculating']);

        try {
            $quotes = $this->quoteService->getQuotesForShipment($shipment);
            $shipment->update(['status' => 'quoted']);

            return response()->json([
                'message' => 'Quotes calculated successfully',
                'quotes' => $quotes,
            ]);
        } catch (\Exception $e) {
            $shipment->update(['status' => 'draft']);

            return response()->json([
                'message' => 'Failed to calculate quotes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function quotes(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);

        $sortBy = $request->get('sort', 'price');
        $sortOrder = $request->get('order', 'asc');

        $quotes = $shipment->quotes()
            ->with('carrier.company')
            ->when($sortBy === 'price', fn($q) => $q->orderBy('price', $sortOrder))
            ->when($sortBy === 'delivery_days', fn($q) => $q->orderBy('delivery_days', $sortOrder))
            ->when($sortBy === 'rating', fn($q) => $q->join('carriers', 'quotes.carrier_id', '=', 'carriers.id')
                ->join('companies', 'carriers.company_id', '=', 'companies.id')
                ->orderBy('companies.rating', $sortOrder)
                ->select('quotes.*'))
            ->get();

        return response()->json([
            'shipment' => $shipment,
            'quotes' => $quotes,
        ]);
    }
}
