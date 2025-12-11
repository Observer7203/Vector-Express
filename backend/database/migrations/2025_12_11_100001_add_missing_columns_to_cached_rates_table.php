<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cached_rates', function (Blueprint $table) {
            // Add cache_key column if not exists
            if (!Schema::hasColumn('cached_rates', 'cache_key')) {
                $table->string('cache_key', 64)->nullable()->after('carrier_id');
            }

            // Add location columns for better querying
            if (!Schema::hasColumn('cached_rates', 'origin_country')) {
                $table->string('origin_country', 3)->nullable()->after('cache_key');
            }
            if (!Schema::hasColumn('cached_rates', 'origin_city')) {
                $table->string('origin_city', 100)->nullable()->after('origin_country');
            }
            if (!Schema::hasColumn('cached_rates', 'origin_postal_code')) {
                $table->string('origin_postal_code', 20)->nullable()->after('origin_city');
            }
            if (!Schema::hasColumn('cached_rates', 'destination_country')) {
                $table->string('destination_country', 3)->nullable()->after('origin_postal_code');
            }
            if (!Schema::hasColumn('cached_rates', 'destination_city')) {
                $table->string('destination_city', 100)->nullable()->after('destination_country');
            }
            if (!Schema::hasColumn('cached_rates', 'destination_postal_code')) {
                $table->string('destination_postal_code', 20)->nullable()->after('destination_city');
            }
            if (!Schema::hasColumn('cached_rates', 'transport_type')) {
                $table->string('transport_type', 20)->nullable()->after('destination_postal_code');
            }
            if (!Schema::hasColumn('cached_rates', 'weight')) {
                $table->decimal('weight', 10, 2)->nullable()->after('transport_type');
            }
            if (!Schema::hasColumn('cached_rates', 'volume')) {
                $table->decimal('volume', 10, 4)->nullable()->after('weight');
            }
        });

        // Add index for cache_key lookup
        Schema::table('cached_rates', function (Blueprint $table) {
            $table->index(['carrier_id', 'cache_key'], 'cached_rates_carrier_cache_key_index');
        });
    }

    public function down(): void
    {
        Schema::table('cached_rates', function (Blueprint $table) {
            $table->dropIndex('cached_rates_carrier_cache_key_index');
            $table->dropColumn([
                'cache_key',
                'origin_country',
                'origin_city',
                'origin_postal_code',
                'destination_country',
                'destination_city',
                'destination_postal_code',
                'transport_type',
                'weight',
                'volume',
            ]);
        });
    }
};
