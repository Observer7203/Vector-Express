<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->constrained()->onDelete('cascade');
            $table->string('zone_code', 10);
            $table->string('zone_name', 100)->nullable();
            $table->string('country_code', 3)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['carrier_id', 'zone_code']);
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_zones');
    }
};
