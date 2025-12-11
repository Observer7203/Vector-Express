<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Indexes Migration
 *
 * This migration adds comprehensive indexes to optimize database queries
 * for high-traffic and large-scale operations. Indexes are added based on:
 *
 * 1. Common query patterns (WHERE, JOIN, ORDER BY)
 * 2. Foreign key relationships
 * 3. Search and filtering operations
 * 4. Composite indexes for multi-column queries
 *
 * SOLID Principle: Single Responsibility - this migration only handles indexing
 */
return new class extends Migration
{
    public function up(): void
    {
        // ==========================================================================
        // USERS TABLE INDEXES
        // ==========================================================================
        Schema::table('users', function (Blueprint $table) {
            // Email lookups for authentication
            if (!$this->hasIndex('users', 'users_email_index')) {
                $table->index('email', 'users_email_index');
            }
            // Role-based queries (list all customers, carriers, etc.)
            if (!$this->hasIndex('users', 'users_role_index')) {
                $table->index('role', 'users_role_index');
            }
            // Company relationship queries
            if (!$this->hasIndex('users', 'users_company_id_index')) {
                $table->index('company_id', 'users_company_id_index');
            }
            // Composite index for role + company queries
            if (!$this->hasIndex('users', 'users_role_company_index')) {
                $table->index(['role', 'company_id'], 'users_role_company_index');
            }
        });

        // ==========================================================================
        // COMPANIES TABLE INDEXES
        // ==========================================================================
        Schema::table('companies', function (Blueprint $table) {
            // Type-based queries (list all carriers, shippers)
            if (!$this->hasIndex('companies', 'companies_type_index')) {
                $table->index('type', 'companies_type_index');
            }
            // Verification status queries
            if (!$this->hasIndex('companies', 'companies_verified_index')) {
                $table->index('verified', 'companies_verified_index');
            }
            // Rating-based sorting and filtering
            if (!$this->hasIndex('companies', 'companies_rating_index')) {
                $table->index('rating', 'companies_rating_index');
            }
            // Composite index for verified carriers by rating
            if (!$this->hasIndex('companies', 'companies_type_verified_rating_index')) {
                $table->index(['type', 'verified', 'rating'], 'companies_type_verified_rating_index');
            }
        });

        // ==========================================================================
        // CARRIERS TABLE INDEXES
        // ==========================================================================
        Schema::table('carriers', function (Blueprint $table) {
            // Active carrier queries
            if (!$this->hasIndex('carriers', 'carriers_is_active_index')) {
                $table->index('is_active', 'carriers_is_active_index');
            }
            // API type queries for routing
            if (!$this->hasIndex('carriers', 'carriers_api_type_index')) {
                $table->index('api_type', 'carriers_api_type_index');
            }
            // Composite for active carriers by type
            if (!$this->hasIndex('carriers', 'carriers_active_type_index')) {
                $table->index(['is_active', 'api_type'], 'carriers_active_type_index');
            }
        });

        // ==========================================================================
        // SHIPMENTS TABLE INDEXES
        // ==========================================================================
        Schema::table('shipments', function (Blueprint $table) {
            // User's shipments listing
            if (!$this->hasIndex('shipments', 'shipments_user_id_index')) {
                $table->index('user_id', 'shipments_user_id_index');
            }
            // Status filtering
            if (!$this->hasIndex('shipments', 'shipments_status_index')) {
                $table->index('status', 'shipments_status_index');
            }
            // Route queries (origin-destination)
            if (!$this->hasIndex('shipments', 'shipments_route_index')) {
                $table->index(['origin_country', 'destination_country'], 'shipments_route_index');
            }
            // Transport type filtering
            if (!$this->hasIndex('shipments', 'shipments_transport_type_index')) {
                $table->index('transport_type', 'shipments_transport_type_index');
            }
            // Composite for user's shipments by status
            if (!$this->hasIndex('shipments', 'shipments_user_status_index')) {
                $table->index(['user_id', 'status'], 'shipments_user_status_index');
            }
            // Created date for sorting
            if (!$this->hasIndex('shipments', 'shipments_created_at_index')) {
                $table->index('created_at', 'shipments_created_at_index');
            }
        });

        // ==========================================================================
        // QUOTES TABLE INDEXES
        // ==========================================================================
        Schema::table('quotes', function (Blueprint $table) {
            // Shipment's quotes
            if (!$this->hasIndex('quotes', 'quotes_shipment_id_index')) {
                $table->index('shipment_id', 'quotes_shipment_id_index');
            }
            // Carrier's quotes
            if (!$this->hasIndex('quotes', 'quotes_carrier_id_index')) {
                $table->index('carrier_id', 'quotes_carrier_id_index');
            }
            // Price-based sorting
            if (!$this->hasIndex('quotes', 'quotes_price_index')) {
                $table->index('price', 'quotes_price_index');
            }
            // Validity check
            if (!$this->hasIndex('quotes', 'quotes_valid_until_index')) {
                $table->index('valid_until', 'quotes_valid_until_index');
            }
            // Composite for shipment quotes sorted by price
            if (!$this->hasIndex('quotes', 'quotes_shipment_price_index')) {
                $table->index(['shipment_id', 'price'], 'quotes_shipment_price_index');
            }
        });

        // ==========================================================================
        // ORDERS TABLE INDEXES
        // ==========================================================================
        Schema::table('orders', function (Blueprint $table) {
            // Order number lookup (unique anyway, but explicit index)
            if (!$this->hasIndex('orders', 'orders_order_number_index')) {
                $table->index('order_number', 'orders_order_number_index');
            }
            // User's orders
            if (!$this->hasIndex('orders', 'orders_user_id_index')) {
                $table->index('user_id', 'orders_user_id_index');
            }
            // Carrier's orders
            if (!$this->hasIndex('orders', 'orders_carrier_id_index')) {
                $table->index('carrier_id', 'orders_carrier_id_index');
            }
            // Status filtering
            if (!$this->hasIndex('orders', 'orders_status_index')) {
                $table->index('status', 'orders_status_index');
            }
            // Tracking number lookup
            if (!$this->hasIndex('orders', 'orders_tracking_number_index')) {
                $table->index('tracking_number', 'orders_tracking_number_index');
            }
            // Composite for user's orders by status
            if (!$this->hasIndex('orders', 'orders_user_status_index')) {
                $table->index(['user_id', 'status'], 'orders_user_status_index');
            }
            // Composite for carrier's orders by status
            if (!$this->hasIndex('orders', 'orders_carrier_status_index')) {
                $table->index(['carrier_id', 'status'], 'orders_carrier_status_index');
            }
            // Created date for sorting
            if (!$this->hasIndex('orders', 'orders_created_at_index')) {
                $table->index('created_at', 'orders_created_at_index');
            }
        });

        // ==========================================================================
        // CARRIER ZONES TABLE INDEXES
        // ==========================================================================
        Schema::table('carrier_zones', function (Blueprint $table) {
            // Carrier's zones
            if (!$this->hasIndex('carrier_zones', 'carrier_zones_carrier_id_index')) {
                $table->index('carrier_id', 'carrier_zones_carrier_id_index');
            }
            // Country lookup
            if (!$this->hasIndex('carrier_zones', 'carrier_zones_country_code_index')) {
                $table->index('country_code', 'carrier_zones_country_code_index');
            }
            // Zone code lookup
            if (!$this->hasIndex('carrier_zones', 'carrier_zones_zone_code_index')) {
                $table->index('zone_code', 'carrier_zones_zone_code_index');
            }
            // Composite for carrier's zones by country
            if (!$this->hasIndex('carrier_zones', 'carrier_zones_carrier_country_index')) {
                $table->index(['carrier_id', 'country_code'], 'carrier_zones_carrier_country_index');
            }
        });

        // ==========================================================================
        // CARRIER ZONE POSTAL CODES TABLE INDEXES
        // ==========================================================================
        Schema::table('carrier_zone_postal_codes', function (Blueprint $table) {
            // Zone's postal codes
            if (!$this->hasIndex('carrier_zone_postal_codes', 'carrier_zone_postal_codes_zone_id_index')) {
                $table->index('carrier_zone_id', 'carrier_zone_postal_codes_zone_id_index');
            }
            // Postal code prefix lookup for rate calculation
            if (!$this->hasIndex('carrier_zone_postal_codes', 'carrier_zone_postal_codes_prefix_index')) {
                $table->index('postal_code_prefix', 'carrier_zone_postal_codes_prefix_index');
            }
            // Remote area filtering
            if (!$this->hasIndex('carrier_zone_postal_codes', 'carrier_zone_postal_codes_remote_index')) {
                $table->index('is_remote_area', 'carrier_zone_postal_codes_remote_index');
            }
            // City lookup
            if (!$this->hasIndex('carrier_zone_postal_codes', 'carrier_zone_postal_codes_city_index')) {
                $table->index('city', 'carrier_zone_postal_codes_city_index');
            }
        });

        // ==========================================================================
        // CARRIER RATE CARDS TABLE INDEXES
        // ==========================================================================
        // Note: Already has composite index from original migration
        // Adding additional indexes for specific query patterns
        if (Schema::hasTable('carrier_rate_cards')) {
            Schema::table('carrier_rate_cards', function (Blueprint $table) {
                // Transport type filtering
                if (Schema::hasColumn('carrier_rate_cards', 'transport_type') &&
                    !$this->hasIndex('carrier_rate_cards', 'carrier_rate_cards_transport_type_index')) {
                    $table->index('transport_type', 'carrier_rate_cards_transport_type_index');
                }
                // Weight range queries
                if (Schema::hasColumn('carrier_rate_cards', 'weight_min') &&
                    !$this->hasIndex('carrier_rate_cards', 'carrier_rate_cards_weight_range_index')) {
                    $table->index(['weight_min', 'weight_max'], 'carrier_rate_cards_weight_range_index');
                }
                // Effective date filtering
                if (Schema::hasColumn('carrier_rate_cards', 'effective_from') &&
                    !$this->hasIndex('carrier_rate_cards', 'carrier_rate_cards_effective_index')) {
                    $table->index(['effective_from', 'effective_until'], 'carrier_rate_cards_effective_index');
                }
            });
        }

        // ==========================================================================
        // CARRIER SURCHARGES TABLE INDEXES
        // ==========================================================================
        Schema::table('carrier_surcharges', function (Blueprint $table) {
            // Carrier's surcharges
            if (!$this->hasIndex('carrier_surcharges', 'carrier_surcharges_carrier_id_index')) {
                $table->index('carrier_id', 'carrier_surcharges_carrier_id_index');
            }
            // Active surcharges
            if (!$this->hasIndex('carrier_surcharges', 'carrier_surcharges_is_active_index')) {
                $table->index('is_active', 'carrier_surcharges_is_active_index');
            }
            // Surcharge type lookup
            if (!$this->hasIndex('carrier_surcharges', 'carrier_surcharges_type_index')) {
                $table->index('surcharge_type', 'carrier_surcharges_type_index');
            }
            // Composite for carrier's active surcharges
            if (!$this->hasIndex('carrier_surcharges', 'carrier_surcharges_carrier_active_index')) {
                $table->index(['carrier_id', 'is_active'], 'carrier_surcharges_carrier_active_index');
            }
        });

        // ==========================================================================
        // CARRIER TERMINALS TABLE INDEXES
        // ==========================================================================
        Schema::table('carrier_terminals', function (Blueprint $table) {
            // Carrier's terminals
            if (!$this->hasIndex('carrier_terminals', 'carrier_terminals_carrier_id_index')) {
                $table->index('carrier_id', 'carrier_terminals_carrier_id_index');
            }
            // Active terminals
            if (!$this->hasIndex('carrier_terminals', 'carrier_terminals_is_active_index')) {
                $table->index('is_active', 'carrier_terminals_is_active_index');
            }
            // Country lookup
            if (!$this->hasIndex('carrier_terminals', 'carrier_terminals_country_code_index')) {
                $table->index('country_code', 'carrier_terminals_country_code_index');
            }
            // City lookup
            if (!$this->hasIndex('carrier_terminals', 'carrier_terminals_city_index')) {
                $table->index('city', 'carrier_terminals_city_index');
            }
            // Terminal type filtering
            if (!$this->hasIndex('carrier_terminals', 'carrier_terminals_type_index')) {
                $table->index('type', 'carrier_terminals_type_index');
            }
            // Composite for carrier's active terminals
            if (!$this->hasIndex('carrier_terminals', 'carrier_terminals_carrier_active_index')) {
                $table->index(['carrier_id', 'is_active'], 'carrier_terminals_carrier_active_index');
            }
            // Geolocation index for proximity queries (if supported)
            // Note: For actual geospatial queries, consider using spatial indexes
        });

        // ==========================================================================
        // CARRIER PRICING RULES TABLE INDEXES
        // ==========================================================================
        Schema::table('carrier_pricing_rules', function (Blueprint $table) {
            // Carrier's pricing rules
            if (!$this->hasIndex('carrier_pricing_rules', 'carrier_pricing_rules_carrier_id_index')) {
                $table->index('carrier_id', 'carrier_pricing_rules_carrier_id_index');
            }
            // Effective date filtering
            if (!$this->hasIndex('carrier_pricing_rules', 'carrier_pricing_rules_effective_index')) {
                $table->index(['effective_from', 'effective_until'], 'carrier_pricing_rules_effective_index');
            }
        });

        // ==========================================================================
        // TRACKING EVENTS TABLE INDEXES (if exists)
        // ==========================================================================
        if (Schema::hasTable('tracking_events')) {
            Schema::table('tracking_events', function (Blueprint $table) {
                // Order's tracking events
                if (!$this->hasIndex('tracking_events', 'tracking_events_order_id_index')) {
                    $table->index('order_id', 'tracking_events_order_id_index');
                }
                // Event time for sorting
                if (Schema::hasColumn('tracking_events', 'event_time') &&
                    !$this->hasIndex('tracking_events', 'tracking_events_event_time_index')) {
                    $table->index('event_time', 'tracking_events_event_time_index');
                }
                // Status lookup
                if (!$this->hasIndex('tracking_events', 'tracking_events_status_index')) {
                    $table->index('status', 'tracking_events_status_index');
                }
            });
        }

        // ==========================================================================
        // INVOICES TABLE INDEXES (if exists)
        // ==========================================================================
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                // Invoice number lookup
                if (!$this->hasIndex('invoices', 'invoices_invoice_number_index')) {
                    $table->index('invoice_number', 'invoices_invoice_number_index');
                }
                // User's invoices
                if (!$this->hasIndex('invoices', 'invoices_user_id_index')) {
                    $table->index('user_id', 'invoices_user_id_index');
                }
                // Status filtering
                if (!$this->hasIndex('invoices', 'invoices_status_index')) {
                    $table->index('status', 'invoices_status_index');
                }
                // Due date for overdue queries
                if (!$this->hasIndex('invoices', 'invoices_due_date_index')) {
                    $table->index('due_date', 'invoices_due_date_index');
                }
            });
        }
    }

    public function down(): void
    {
        // Remove all added indexes
        // Note: Removing indexes is generally safe and reversible

        $indexesToDrop = [
            'users' => ['users_email_index', 'users_role_index', 'users_company_id_index', 'users_role_company_index'],
            'companies' => ['companies_type_index', 'companies_verified_index', 'companies_rating_index', 'companies_type_verified_rating_index'],
            'carriers' => ['carriers_is_active_index', 'carriers_api_type_index', 'carriers_active_type_index'],
            'shipments' => ['shipments_user_id_index', 'shipments_status_index', 'shipments_route_index', 'shipments_transport_type_index', 'shipments_user_status_index', 'shipments_created_at_index'],
            'quotes' => ['quotes_shipment_id_index', 'quotes_carrier_id_index', 'quotes_price_index', 'quotes_valid_until_index', 'quotes_shipment_price_index'],
            'orders' => ['orders_order_number_index', 'orders_user_id_index', 'orders_carrier_id_index', 'orders_status_index', 'orders_tracking_number_index', 'orders_user_status_index', 'orders_carrier_status_index', 'orders_created_at_index'],
            'carrier_zones' => ['carrier_zones_carrier_id_index', 'carrier_zones_country_code_index', 'carrier_zones_zone_code_index', 'carrier_zones_carrier_country_index'],
            'carrier_zone_postal_codes' => ['carrier_zone_postal_codes_zone_id_index', 'carrier_zone_postal_codes_prefix_index', 'carrier_zone_postal_codes_remote_index', 'carrier_zone_postal_codes_city_index'],
            'carrier_rate_cards' => ['carrier_rate_cards_transport_type_index', 'carrier_rate_cards_weight_range_index', 'carrier_rate_cards_effective_index'],
            'carrier_surcharges' => ['carrier_surcharges_carrier_id_index', 'carrier_surcharges_is_active_index', 'carrier_surcharges_type_index', 'carrier_surcharges_carrier_active_index'],
            'carrier_terminals' => ['carrier_terminals_carrier_id_index', 'carrier_terminals_is_active_index', 'carrier_terminals_country_code_index', 'carrier_terminals_city_index', 'carrier_terminals_type_index', 'carrier_terminals_carrier_active_index'],
            'carrier_pricing_rules' => ['carrier_pricing_rules_carrier_id_index', 'carrier_pricing_rules_effective_index'],
            'tracking_events' => ['tracking_events_order_id_index', 'tracking_events_event_time_index', 'tracking_events_status_index'],
            'invoices' => ['invoices_invoice_number_index', 'invoices_user_id_index', 'invoices_status_index', 'invoices_due_date_index'],
        ];

        foreach ($indexesToDrop as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $index) {
                        try {
                            $table->dropIndex($index);
                        } catch (\Exception $e) {
                            // Index might not exist, continue
                        }
                    }
                });
            }
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        // For MySQL and others
        try {
            $indexes = $connection->select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};
