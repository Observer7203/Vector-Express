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
            $table->foreignId('zone_id')->constrained('carrier_zones')->onDelete('cascade');
            $table->string('postal_code_prefix', 10);
            $table->string('country_code', 2);
            $table->boolean('is_remote_area')->default(false);
            $table->timestamps();

            $table->index(['country_code', 'postal_code_prefix']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_zone_postal_codes');
    }
};
