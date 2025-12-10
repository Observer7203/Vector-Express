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
            $table->decimal('value', 10, 2);
            $table->enum('value_type', ['fixed', 'percentage'])->default('fixed');
            $table->date('applies_from')->nullable();
            $table->date('applies_until')->nullable();
            $table->json('conditions')->nullable(); // e.g., {"min_weight": 70, "transport_type": "air"}
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['carrier_id', 'surcharge_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_surcharges');
    }
};
