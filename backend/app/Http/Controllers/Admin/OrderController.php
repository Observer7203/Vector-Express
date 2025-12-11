<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with([
            'user:id,name,email',
            'carrier.company:id,name',
            'quote.shipment:id,origin_city,origin_country,destination_city,destination_country'
        ]);

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by carrier
        if ($carrierId = $request->get('carrier_id')) {
            $query->where('carrier_id', $carrierId);
        }

        // Filter by date range
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()->paginate(15);

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'user',
            'carrier.company',
            'quote.shipment.items',
            'invoice',
            'trackingEvents'
        ]);

        return response()->json([
            'order' => $order
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                'pending', 'confirmed', 'pickup_scheduled', 'picked_up',
                'in_transit', 'customs', 'out_for_delivery', 'delivered', 'cancelled'
            ])],
            'note' => ['nullable', 'string'],
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        // Update timestamps based on status
        if ($validated['status'] === 'confirmed' && !$order->confirmed_at) {
            $order->update(['confirmed_at' => now()]);
        } elseif ($validated['status'] === 'picked_up' && !$order->picked_up_at) {
            $order->update(['picked_up_at' => now()]);
        } elseif ($validated['status'] === 'delivered' && !$order->delivered_at) {
            $order->update(['delivered_at' => now()]);
        } elseif ($validated['status'] === 'cancelled' && !$order->cancelled_at) {
            $order->update([
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['note'] ?? 'Отменено администратором'
            ]);
        }

        return response()->json([
            'message' => "Статус изменён с {$oldStatus} на {$validated['status']}",
            'order' => $order->fresh()
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->subMonth());
        $dateTo = $request->get('date_to', now());

        $stats = [
            'total' => Order::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'by_status' => Order::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'total_amount' => Order::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'delivered')
                ->sum('total_amount'),
            'total_commission' => Order::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'delivered')
                ->sum('commission_amount'),
        ];

        return response()->json($stats);
    }
}
