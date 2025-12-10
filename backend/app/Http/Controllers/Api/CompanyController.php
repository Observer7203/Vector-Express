<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function show(Company $company): JsonResponse
    {
        return response()->json([
            'company' => $company->load(['carrier', 'users']),
        ]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:50'],
            'legal_address' => ['nullable', 'string'],
            'actual_address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $company->update($validated);

        return response()->json([
            'message' => 'Company updated successfully',
            'company' => $company->fresh(),
        ]);
    }

    public function updateLogo(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $path = $request->file('logo')->store('company-logos', 'public');
        $company->update(['logo' => $path]);

        return response()->json([
            'message' => 'Logo updated successfully',
            'logo' => Storage::url($path),
        ]);
    }

    public function requestVerification(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'max:10240'], // 10MB max per file
        ]);

        // Store verification documents (in real app, would create verification request)
        $documentPaths = [];
        foreach ($request->file('documents') as $document) {
            $documentPaths[] = $document->store("verification/{$company->id}", 'public');
        }

        // In a real implementation, create a verification request record
        // For now, just return success
        return response()->json([
            'message' => 'Verification request submitted successfully',
            'documents_count' => count($documentPaths),
        ]);
    }

    public function setupCarrier(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        if ($company->type !== 'carrier') {
            return response()->json([
                'message' => 'Company must be of type carrier',
            ], 400);
        }

        $validated = $request->validate([
            'api_type' => ['required', 'in:dhl,fedex,ups,ponyexpress,manual'],
            'api_config' => ['nullable', 'array'],
            'supported_transport_types' => ['required', 'array'],
            'supported_transport_types.*' => ['in:air,sea,rail,road'],
            'supported_countries' => ['nullable', 'array'],
        ]);

        $carrier = Carrier::updateOrCreate(
            ['company_id' => $company->id],
            $validated
        );

        return response()->json([
            'message' => 'Carrier setup successfully',
            'carrier' => $carrier,
        ]);
    }
}
