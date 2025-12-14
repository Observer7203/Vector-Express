<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_zone_postal_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_zone_id')->constrained('carrier_zones')->onDelete('cascade');
            $table->string('postal_code_prefix', 10)->nullable();
            $table->string('postal_code_from', 10)->nullable();
            $table->string('postal_code_to', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('country_code', 3)->nullable();
            $table->boolean('is_remote_area')->default(false);
            $table->timestamps();

            $table->index(['carrier_zone_id', 'postal_code_prefix'], 'czpc_zone_prefix_idx');
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_zone_postal_codes');
    }
};
