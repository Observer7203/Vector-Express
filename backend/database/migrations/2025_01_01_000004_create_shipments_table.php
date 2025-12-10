<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Origin
            $table->string('origin_country', 100);
            $table->string('origin_city', 100);
            $table->text('origin_address')->nullable();

            // Destination
            $table->string('destination_country', 100);
            $table->string('destination_city', 100);
            $table->text('destination_address')->nullable();

            // Transport type
            $table->enum('transport_type', ['air', 'sea', 'rail', 'road', 'multimodal'])->nullable();
            $table->enum('cargo_type', ['general', 'dangerous', 'fragile', 'perishable'])->default('general');
            $table->enum('packaging_type', ['box', 'pallet', 'container'])->nullable();

            // Dimensions
            $table->decimal('total_weight', 10, 2)->nullable(); // kg
            $table->decimal('total_volume', 10, 4)->nullable(); // m³
            $table->decimal('declared_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');

            // Options
            $table->boolean('insurance_required')->default(false);
            $table->boolean('customs_clearance')->default(false);
            $table->boolean('door_to_door')->default(true);

            // Dates
            $table->date('pickup_date')->nullable();

            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'calculating', 'quoted', 'ordered', 'expired'])->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
