<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_rule_id')->constrained('carrier_pricing_rules')->onDelete('cascade');
            $table->foreignId('origin_zone_id')->nullable()->constrained('carrier_zones')->onDelete('cascade');
            $table->foreignId('destination_zone_id')->nullable()->constrained('carrier_zones')->onDelete('cascade');
            $table->decimal('min_weight', 10, 2)->default(0);
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->decimal('rate', 10, 2);
            $table->enum('rate_unit', ['per_kg', 'per_lb', 'per_100kg', 'per_100lbs', 'flat'])->default('per_kg');
            $table->timestamps();

            $table->index(['pricing_rule_id', 'origin_zone_id', 'destination_zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_rate_cards');
    }
};
