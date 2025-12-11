<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyDocumentController extends Controller
{
    /**
     * Получить список документов компании
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->company_id) {
            return response()->json([
                'message' => 'У вас нет привязанной компании',
            ], 400);
        }

        $documents = CompanyDocument::where('company_id', $user->company_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Получаем список недостающих документов
        $company = $user->company;
        $missingDocuments = $company->getMissingDocuments();

        return response()->json([
            'documents' => $documents,
            'missing_documents' => $missingDocuments,
            'required_documents' => CompanyDocument::REQUIRED_FOR_CARRIER,
            'document_type_labels' => CompanyDocument::DOCUMENT_TYPE_LABELS,
            'all_documents_uploaded' => count($missingDocuments) === 0,
            'company_verified' => $company->verified,
        ]);
    }

    /**
     * Загрузить документ
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->company_id) {
            return response()->json([
                'message' => 'У вас нет привязанной компании',
            ], 400);
        }

        $request->validate([
            'document_type' => [
                'required',
                Rule::in(array_keys(CompanyDocument::DOCUMENT_TYPE_LABELS)),
            ],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240', // 10MB max
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('file');
        $documentType = $request->document_type;

        // Проверяем, есть ли уже документ такого типа на рассмотрении
        $existingPending = CompanyDocument::where('company_id', $user->company_id)
            ->where('document_type', $documentType)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'message' => 'Документ этого типа уже на рассмотрении. Дождитесь проверки или удалите предыдущий.',
            ], 400);
        }

        // Сохраняем файл
        $fileName = $documentType . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs(
            'company_documents/' . $user->company_id,
            $fileName,
            'public'
        );

        $document = CompanyDocument::create([
            'company_id' => $user->company_id,
            'document_type' => $documentType,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'notes' => $request->notes,
            'status' => 'pending',
            'uploaded_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Документ успешно загружен и отправлен на проверку',
            'document' => $document,
        ], 201);
    }

    /**
     * Получить информацию о документе
     */
    public function show(Request $request, CompanyDocument $document): JsonResponse
    {
        $user = $request->user();

        // Проверяем, принадлежит ли документ компании пользователя
        if ($document->company_id !== $user->company_id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Доступ запрещен',
            ], 403);
        }

        return response()->json([
            'document' => $document->load('uploadedBy', 'verifiedBy'),
        ]);
    }

    /**
     * Удалить документ (только если pending)
     */
    public function destroy(Request $request, CompanyDocument $document): JsonResponse
    {
        $user = $request->user();

        // Проверяем, принадлежит ли документ компании пользователя
        if ($document->company_id !== $user->company_id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Доступ запрещен',
            ], 403);
        }

        // Можно удалить только pending или rejected документы
        if ($document->status === 'approved') {
            return response()->json([
                'message' => 'Нельзя удалить утвержденный документ',
            ], 400);
        }

        // Удаляем файл
        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        return response()->json([
            'message' => 'Документ удален',
        ]);
    }

    /**
     * Скачать документ
     */
    public function download(Request $request, CompanyDocument $document)
    {
        $user = $request->user();

        // Проверяем, принадлежит ли документ компании пользователя или пользователь админ
        if ($document->company_id !== $user->company_id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Доступ запрещен',
            ], 403);
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
     * Получить статус верификации компании
     */
    public function verificationStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->company_id) {
            return response()->json([
                'message' => 'У вас нет привязанной компании',
            ], 400);
        }

        $company = $user->company;
        $documents = $company->documents;

        $status = [
            'company_verified' => $company->verified,
            'verified_at' => $company->verified_at,
            'documents_status' => [
                'total' => $documents->count(),
                'pending' => $documents->where('status', 'pending')->count(),
                'approved' => $documents->where('status', 'approved')->count(),
                'rejected' => $documents->where('status', 'rejected')->count(),
            ],
            'required_documents' => [],
        ];

        // Статус по каждому обязательному документу
        foreach (CompanyDocument::REQUIRED_FOR_CARRIER as $docType) {
            $doc = $documents->where('document_type', $docType)->first();
            $status['required_documents'][$docType] = [
                'label' => CompanyDocument::DOCUMENT_TYPE_LABELS[$docType] ?? $docType,
                'status' => $doc ? $doc->status : 'not_uploaded',
                'rejection_reason' => $doc ? $doc->rejection_reason : null,
            ];
        }

        $status['all_required_approved'] = $company->hasAllRequiredDocuments();
        $status['missing_documents'] = $company->getMissingDocuments();

        // Сообщение для пользователя
        if ($company->verified) {
            $status['message'] = 'Ваша компания верифицирована и может принимать заказы';
        } elseif (count($status['missing_documents']) > 0) {
            $status['message'] = 'Загрузите все обязательные документы для прохождения верификации';
        } elseif ($documents->where('status', 'pending')->count() > 0) {
            $status['message'] = 'Ваши документы на проверке. Обычно это занимает 1-2 рабочих дня';
        } elseif ($documents->where('status', 'rejected')->count() > 0) {
            $status['message'] = 'Некоторые документы отклонены. Пожалуйста, загрузите исправленные версии';
        } else {
            $status['message'] = 'Ожидание верификации компании администратором';
        }

        return response()->json($status);
    }
}
