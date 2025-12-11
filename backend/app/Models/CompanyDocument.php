<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'document_type',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'document_number',
        'issued_at',
        'expires_at',
        'issued_by',
        'notes',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    // Типы документов с названиями
    public const DOCUMENT_TYPES = [
        'registration_certificate' => 'Свидетельство о регистрации',
        'transport_license' => 'Лицензия на перевозки',
        'insurance_policy' => 'Страховой полис',
        'international_permit' => 'Допуск к международным перевозкам',
        'association_membership' => 'Членство в ассоциациях',
        'quality_certificate' => 'Сертификат качества',
        'other' => 'Другой документ',
    ];

    // Обязательные документы для перевозчика
    public const REQUIRED_FOR_CARRIER = [
        'registration_certificate',
        'transport_license',
        'insurance_policy',
    ];

    // Алиас для DOCUMENT_TYPES для совместимости с контроллером
    public const DOCUMENT_TYPE_LABELS = self::DOCUMENT_TYPES;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }
}
