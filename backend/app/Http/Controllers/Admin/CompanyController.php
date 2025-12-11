<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Company;
use App\Models\CompanyDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::withCount(['users']);

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Filter by verification status
        if ($request->has('verified')) {
            $query->where('verified', $request->boolean('verified'));
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('inn', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $companies = $query->latest()->paginate(15);

        return response()->json($companies);
    }

    public function show(Company $company): JsonResponse
    {
        $company->load(['users', 'carrier', 'documents.uploadedBy', 'documents.verifiedBy']);

        $stats = [
            'users_count' => $company->users()->count(),
            'orders_count' => $company->orders()->count(),
            'documents_count' => $company->documents()->count(),
            'pending_documents' => $company->documents()->where('status', 'pending')->count(),
        ];

        if ($company->type === 'carrier' && $company->carrier) {
            $stats['quotes_count'] = $company->carrier->quotes()->count();
            $stats['completed_orders'] = $company->carrier->orders()->where('status', 'delivered')->count();
        }

        // Добавляем информацию о необходимых документах
        $verificationInfo = [
            'has_all_required_documents' => $company->hasAllRequiredDocuments(),
            'missing_documents' => $company->getMissingDocuments(),
            'required_documents' => CompanyDocument::REQUIRED_FOR_CARRIER,
            'document_type_labels' => CompanyDocument::DOCUMENT_TYPE_LABELS,
        ];

        return response()->json([
            'company' => $company,
            'stats' => $stats,
            'verification_info' => $verificationInfo
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:50'],
            'type' => ['required', Rule::in(['shipper', 'carrier'])],
            'legal_address' => ['nullable', 'string'],
            'actual_address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'website' => ['nullable', 'url'],
            'verified' => ['boolean'],
        ]);

        $company = Company::create($validated);

        return response()->json([
            'message' => 'Компания создана',
            'company' => $company
        ], 201);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:50'],
            'type' => ['sometimes', Rule::in(['shipper', 'carrier'])],
            'legal_address' => ['nullable', 'string'],
            'actual_address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'website' => ['nullable', 'url'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ]);

        $company->update($validated);

        return response()->json([
            'message' => 'Компания обновлена',
            'company' => $company->fresh()
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        if ($company->users()->count() > 0) {
            return response()->json([
                'message' => 'Нельзя удалить компанию с пользователями'
            ], 400);
        }

        $company->delete();

        return response()->json([
            'message' => 'Компания удалена'
        ]);
    }

    public function verify(Company $company): JsonResponse
    {
        $company->update([
            'verified' => true,
            'verified_at' => now()
        ]);

        // Создаём или активируем carrier для компании-перевозчика
        if ($company->type === 'carrier') {
            if ($company->carrier) {
                $company->carrier->update(['is_active' => true]);
            } else {
                Carrier::create([
                    'company_id' => $company->id,
                    'api_type' => 'manual',
                    'supported_transport_types' => ['road', 'rail', 'air', 'sea'],
                    'supported_countries' => ['*'],
                    'is_active' => true,
                ]);
            }
        }

        return response()->json([
            'message' => 'Компания верифицирована',
            'company' => $company->fresh('carrier')
        ]);
    }

    public function unverify(Company $company): JsonResponse
    {
        $company->update([
            'verified' => false,
            'verified_at' => null
        ]);

        // Деактивируем carrier если есть
        if ($company->carrier) {
            $company->carrier->update(['is_active' => false]);
        }

        return response()->json([
            'message' => 'Верификация отменена',
            'company' => $company
        ]);
    }

    /**
     * Получить документы компании
     */
    public function documents(Company $company): JsonResponse
    {
        $documents = $company->documents()
            ->with(['uploadedBy', 'verifiedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'documents' => $documents,
            'missing_documents' => $company->getMissingDocuments(),
            'required_documents' => CompanyDocument::REQUIRED_FOR_CARRIER,
            'document_type_labels' => CompanyDocument::DOCUMENT_TYPE_LABELS,
        ]);
    }

    /**
     * Одобрить документ
     */
    public function approveDocument(Request $request, CompanyDocument $document): JsonResponse
    {
        $document->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $company = $document->company;

        // Проверяем, все ли обязательные документы одобрены
        if ($company->hasAllRequiredDocuments()) {
            // Автоматически верифицируем компанию
            $company->update([
                'verified' => true,
                'verified_at' => now(),
            ]);

            // Создаём или активируем carrier
            if ($company->carrier) {
                $company->carrier->update(['is_active' => true]);
            } else {
                // Автоматически создаём Carrier для верифицированной компании
                Carrier::create([
                    'company_id' => $company->id,
                    'api_type' => 'manual',
                    'supported_transport_types' => ['road', 'rail', 'air', 'sea'],
                    'supported_countries' => ['*'], // Все страны по умолчанию
                    'is_active' => true,
                ]);
            }

            return response()->json([
                'message' => 'Документ одобрен. Компания верифицирована и может принимать заказы.',
                'document' => $document->fresh(['uploadedBy', 'verifiedBy']),
                'company_verified' => true,
            ]);
        }

        return response()->json([
            'message' => 'Документ одобрен',
            'document' => $document->fresh(['uploadedBy', 'verifiedBy']),
            'missing_documents' => $company->getMissingDocuments(),
        ]);
    }

    /**
     * Отклонить документ
     */
    public function rejectDocument(Request $request, CompanyDocument $document): JsonResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $document->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Документ отклонен',
            'document' => $document->fresh(['uploadedBy', 'verifiedBy']),
        ]);
    }

    /**
     * Скачать документ (с проверкой токена из query string для браузерных загрузок)
     */
    public function downloadDocument(Request $request, CompanyDocument $document)
    {
        // Проверяем авторизацию через токен в query string или через auth middleware
        $user = $request->user();

        if (!$user && $request->has('token')) {
            $token = $request->get('token');
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            }
        }

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'message' => 'Не авторизован',
            ], 401);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'message' => 'Файл не найден',
            ], 404);
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->file_name
        );
    }

    /**
     * Получить список компаний ожидающих верификации
     */
    public function pendingVerification(Request $request): JsonResponse
    {
        $companies = Company::where('type', 'carrier')
            ->where('verified', false)
            ->whereHas('documents', function ($query) {
                $query->where('status', 'pending');
            })
            ->with(['documents' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->withCount(['documents as pending_documents_count' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->latest()
            ->paginate(15);

        return response()->json($companies);
    }
}
