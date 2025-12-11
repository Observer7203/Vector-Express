<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Тип документа
            $table->enum('document_type', [
                'registration_certificate',  // Свидетельство о регистрации
                'transport_license',         // Лицензия на перевозки
                'insurance_policy',          // Страховой полис
                'international_permit',      // Допуск к международным перевозкам
                'association_membership',    // Членство в ассоциациях (FIATA, IATA)
                'quality_certificate',       // Сертификат качества (ISO)
                'other'                      // Другие документы
            ]);

            // Информация о файле
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable(); // mime type
            $table->unsignedInteger('file_size')->nullable(); // в байтах

            // Детали документа
            $table->string('document_number')->nullable(); // Номер документа
            $table->date('issued_at')->nullable();         // Дата выдачи
            $table->date('expires_at')->nullable();        // Срок действия
            $table->string('issued_by')->nullable();       // Кем выдан

            // Статус верификации
            $table->enum('status', [
                'pending',    // Ожидает проверки
                'approved',   // Подтвержден
                'rejected'    // Отклонен
            ])->default('pending');

            $table->text('rejection_reason')->nullable(); // Причина отклонения
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Индексы
            $table->index(['company_id', 'document_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_documents');
    }
};
