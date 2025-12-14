<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cached_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->constrained()->onDelete('cascade');
            $table->string('route_hash', 64); // MD5 hash of origin+destination+weight+dimensions
            $table->json('rate_data');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['carrier_id', 'route_hash', 'expires_at'], 'cr_carrier_route_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cached_rates');
    }
};
