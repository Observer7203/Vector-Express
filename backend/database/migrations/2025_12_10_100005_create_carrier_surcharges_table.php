<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_surcharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->constrained()->onDelete('cascade');
            $table->string('surcharge_type', 50); // fuel, residential, remote_area, peak_season, oversized, etc.
            $table->string('name')->nullable();
            $table->enum('calculation_type', ['percentage', 'flat', 'per_kg'])->default('flat');
            $table->decimal('value', 10, 4);
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->json('applies_to_transport_types')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['carrier_id', 'surcharge_type', 'is_active'], 'cs_carrier_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_surcharges');
    }
};
