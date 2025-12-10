<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->constrained()->onDelete('cascade');
            $table->enum('pricing_type', ['zone', 'distance', 'weight', 'hybrid'])->default('zone');
            $table->decimal('dim_factor', 8, 2)->default(139.00); // DIM weight divisor
            $table->decimal('minimum_charge', 10, 2)->nullable();
            $table->decimal('insurance_rate', 5, 2)->nullable(); // Percentage of declared value
            $table->string('currency', 3)->default('USD');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->json('config')->nullable(); // Additional complex pricing rules
            $table->timestamps();

            $table->index(['carrier_id', 'effective_from', 'effective_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_pricing_rules');
    }
};
