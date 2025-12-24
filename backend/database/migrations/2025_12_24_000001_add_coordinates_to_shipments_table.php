<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Origin coordinates
            $table->decimal('origin_lat', 10, 7)->nullable()->after('origin_address');
            $table->decimal('origin_lng', 10, 7)->nullable()->after('origin_lat');
            $table->string('origin_postcode', 20)->nullable()->after('origin_lng');

            // Destination coordinates
            $table->decimal('destination_lat', 10, 7)->nullable()->after('destination_address');
            $table->decimal('destination_lng', 10, 7)->nullable()->after('destination_lat');
            $table->string('destination_postcode', 20)->nullable()->after('destination_lng');

            // Delivery type specifics
            $table->enum('origin_type', ['door', 'terminal', 'port', 'airport'])->default('door')->after('door_to_door');
            $table->enum('destination_type', ['door', 'terminal', 'port', 'airport'])->default('door')->after('origin_type');

            // Reference to nearest terminals (for door-to-door routing)
            $table->foreignId('origin_terminal_id')->nullable()->after('destination_type')
                ->constrained('carrier_terminals')->nullOnDelete();
            $table->foreignId('destination_terminal_id')->nullable()->after('origin_terminal_id')
                ->constrained('carrier_terminals')->nullOnDelete();

            // Calculated distances (km)
            $table->decimal('origin_to_terminal_km', 8, 2)->nullable()->after('destination_terminal_id');
            $table->decimal('terminal_to_destination_km', 8, 2)->nullable()->after('origin_to_terminal_km');
            $table->decimal('total_distance_km', 10, 2)->nullable()->after('terminal_to_destination_km');

            // Add spatial index for location-based queries
            $table->index(['origin_lat', 'origin_lng'], 'idx_origin_coords');
            $table->index(['destination_lat', 'destination_lng'], 'idx_destination_coords');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('idx_origin_coords');
            $table->dropIndex('idx_destination_coords');

            $table->dropForeign(['origin_terminal_id']);
            $table->dropForeign(['destination_terminal_id']);

            $table->dropColumn([
                'origin_lat',
                'origin_lng',
                'origin_postcode',
                'destination_lat',
                'destination_lng',
                'destination_postcode',
                'origin_type',
                'destination_type',
                'origin_terminal_id',
                'destination_terminal_id',
                'origin_to_terminal_km',
                'terminal_to_destination_km',
                'total_distance_km',
            ]);
        });
    }
};
