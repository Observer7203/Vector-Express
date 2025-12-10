<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique(); // VE-2025-000001
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carrier_id')->constrained()->cascadeOnDelete();

            $table->enum('status', [
                'pending', 'confirmed', 'pickup_scheduled', 'picked_up',
                'in_transit', 'customs', 'out_for_delivery', 'delivered', 'cancelled'
            ])->default('pending');

            // Contact info
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email')->nullable();

            // Pickup details
            $table->string('pickup_contact_name')->nullable();
            $table->string('pickup_contact_phone', 50)->nullable();
            $table->text('pickup_address')->nullable();
            $table->date('pickup_date')->nullable();
            $table->time('pickup_time_from')->nullable();
            $table->time('pickup_time_to')->nullable();

            // Delivery details
            $table->string('delivery_contact_name')->nullable();
            $table->string('delivery_contact_phone', 50)->nullable();
            $table->text('delivery_address')->nullable();

            // Tracking
            $table->string('tracking_number', 100)->nullable();
            $table->string('carrier_tracking_number', 100)->nullable();

            // Finances
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('commission_amount', 15, 2)->nullable(); // 5%
            $table->string('currency', 3)->default('USD');

            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
