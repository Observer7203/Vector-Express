<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carrier_id')->constrained()->cascadeOnDelete();

            $table->decimal('price', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('delivery_days')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->enum('transport_type', ['air', 'sea', 'rail', 'road'])->nullable();

            $table->json('services_included')->nullable(); // ['door_pickup', 'customs', 'insurance']

            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_selected')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
