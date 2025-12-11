<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CarrierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Carrier::with('company:id,name,inn,rating,verified')
            ->withCount(['quotes', 'orders']);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by API type
        if ($apiType = $request->get('api_type')) {
            $query->where('api_type', $apiType);
        }

        // Search by company name
        if ($search = $request->get('search')) {
            $query->whereHas('company', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $carriers = $query->latest()->paginate(15);

        return response()->json($carriers);
    }

    public function show(Carrier $carrier): JsonResponse
    {
        $carrier->load('company');

        $stats = [
            'quotes_count' => $carrier->quotes()->count(),
            'orders_count' => $carrier->orders()->count(),
            'completed_orders' => $carrier->orders()->where('status', 'delivered')->count(),
            'total_revenue' => $carrier->orders()->where('status', 'delivered')->sum('total_amount'),
        ];

        return response()->json([
            'carrier' => $carrier,
            'stats' => $stats
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id', 'unique:carriers,company_id'],
            'api_type' => ['required', Rule::in(['manual', 'dhl', 'fedex', 'ups', 'ponyexpress', 'custom'])],
            'api_config' => ['nullable', 'array'],
            'supported_transport_types' => ['required', 'array', 'min:1'],
            'supported_transport_types.*' => [Rule::in(['air', 'sea', 'rail', 'road'])],
            'supported_countries' => ['required', 'array', 'min:1'],
            'supported_countries.*' => ['string'],
            'is_active' => ['boolean'],
            'tariff_config' => ['nullable', 'array'],
        ]);

        // Verify company is of type carrier
        $company = Company::find($validated['company_id']);
        if ($company->type !== 'carrier') {
            return response()->json([
                'message' => 'Компания должна быть типа "carrier"'
            ], 400);
        }

        $carrier = Carrier::create($validated);

        return response()->json([
            'message' => 'Перевозчик создан',
            'carrier' => $carrier->load('company')
        ], 201);
    }

    public function update(Request $request, Carrier $carrier): JsonResponse
    {
        $validated = $request->validate([
            'api_type' => ['sometimes', Rule::in(['manual', 'dhl', 'fedex', 'ups', 'ponyexpress', 'custom'])],
            'api_config' => ['nullable', 'array'],
            'supported_transport_types' => ['sometimes', 'array', 'min:1'],
            'supported_transport_types.*' => [Rule::in(['air', 'sea', 'rail', 'road'])],
            'supported_countries' => ['sometimes', 'array', 'min:1'],
            'supported_countries.*' => ['string'],
            'is_active' => ['sometimes', 'boolean'],
            'tariff_config' => ['nullable', 'array'],
        ]);

        $carrier->update($validated);

        return response()->json([
            'message' => 'Перевозчик обновлён',
            'carrier' => $carrier->fresh()->load('company')
        ]);
    }

    public function destroy(Carrier $carrier): JsonResponse
    {
        if ($carrier->orders()->where('status', '!=', 'delivered')->where('status', '!=', 'cancelled')->exists()) {
            return response()->json([
                'message' => 'Нельзя удалить перевозчика с активными заказами'
            ], 400);
        }

        $carrier->delete();

        return response()->json([
            'message' => 'Перевозчик удалён'
        ]);
    }

    public function toggleActive(Carrier $carrier): JsonResponse
    {
        $carrier->update(['is_active' => !$carrier->is_active]);

        return response()->json([
            'message' => $carrier->is_active ? 'Перевозчик активирован' : 'Перевозчик деактивирован',
            'carrier' => $carrier
        ]);
    }

    /**
     * Get companies that don't have a carrier record yet
     */
    public function availableCompanies(): JsonResponse
    {
        $companies = Company::where('type', 'carrier')
            ->whereDoesntHave('carrier')
            ->select('id', 'name', 'inn', 'verified')
            ->get();

        return response()->json($companies);
    }

    /**
     * Get all countries from existing carriers
     */
    public function countries(): JsonResponse
    {
        $countries = Carrier::pluck('supported_countries')
            ->flatten()
            ->unique()
            ->values();

        return response()->json($countries);
    }
}
