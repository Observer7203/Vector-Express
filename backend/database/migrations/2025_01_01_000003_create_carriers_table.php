<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('api_type', ['dhl', 'fedex', 'ups', 'ponyexpress', 'manual', 'mock'])->default('manual');
            $table->json('api_config')->nullable();
            $table->json('supported_transport_types')->nullable(); // ['air', 'sea', 'rail', 'road']
            $table->json('supported_countries')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};
