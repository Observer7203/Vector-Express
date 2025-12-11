<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $now = Carbon::now();
        $weekAgo = $now->copy()->subWeek();
        $monthAgo = $now->copy()->subMonth();

        // Users stats
        $totalUsers = User::count();
        $newUsersThisWeek = User::where('created_at', '>=', $weekAgo)->count();

        // Companies stats
        $totalCompanies = Company::count();
        $shippersCount = Company::where('type', 'shipper')->count();
        $carriersCount = Company::where('type', 'carrier')->count();
        $pendingVerification = Company::where('type', 'carrier')
            ->where('verified', false)
            ->count();

        // Orders stats
        $totalOrders = Order::count();
        $ordersToday = Order::whereDate('created_at', $now->toDateString())->count();
        $ordersThisWeek = Order::where('created_at', '>=', $weekAgo)->count();
        $ordersThisMonth = Order::where('created_at', '>=', $monthAgo)->count();

        // Financial stats
        $totalRevenue = Order::where('status', 'delivered')->sum('total_amount');
        $totalCommission = Order::where('status', 'delivered')->sum('commission_amount');
        $pendingPayments = Invoice::where('status', 'pending')->sum('total');
        $overduePayments = Invoice::where('status', 'overdue')->sum('total');

        // Shipments stats
        $totalShipments = Shipment::count();
        $shipmentsThisWeek = Shipment::where('created_at', '>=', $weekAgo)->count();

        // Orders by status
        $ordersByStatus = Order::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Orders trend (last 7 days)
        $ordersTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $count = Order::whereDate('created_at', $date->toDateString())->count();
            $ordersTrend[] = [
                'date' => $date->format('d.m'),
                'count' => $count
            ];
        }

        // Top carriers by orders
        $topCarriers = Order::selectRaw('carrier_id, count(*) as orders_count')
            ->with('carrier.company:id,name')
            ->groupBy('carrier_id')
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->carrier?->company?->name ?? 'Unknown',
                    'orders' => $item->orders_count
                ];
            });

        return response()->json([
            'users' => [
                'total' => $totalUsers,
                'new_this_week' => $newUsersThisWeek,
            ],
            'companies' => [
                'total' => $totalCompanies,
                'shippers' => $shippersCount,
                'carriers' => $carriersCount,
                'pending_verification' => $pendingVerification,
            ],
            'orders' => [
                'total' => $totalOrders,
                'today' => $ordersToday,
                'this_week' => $ordersThisWeek,
                'this_month' => $ordersThisMonth,
                'by_status' => $ordersByStatus,
                'trend' => $ordersTrend,
            ],
            'shipments' => [
                'total' => $totalShipments,
                'this_week' => $shipmentsThisWeek,
            ],
            'financial' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_commission' => round($totalCommission, 2),
                'pending_payments' => round($pendingPayments, 2),
                'overdue_payments' => round($overduePayments, 2),
            ],
            'top_carriers' => $topCarriers,
        ]);
    }
}
