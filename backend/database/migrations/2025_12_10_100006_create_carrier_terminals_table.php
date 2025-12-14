<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->constrained()->onDelete('cascade');
            $table->string('terminal_code', 20)->nullable();
            $table->string('name');
            $table->enum('type', ['pickup', 'delivery', 'hub', 'warehouse'])->default('hub');
            $table->string('country_code', 3);
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('service_radius')->nullable(); // km
            $table->json('working_hours')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['carrier_id', 'type', 'is_active'], 'ct_carrier_type_idx');
            $table->index(['country_code', 'city']);
            $table->unique(['carrier_id', 'terminal_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_terminals');
    }
};
