<?php

namespace App\Providers;

use App\Models\Chat;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Shipment;
use App\Policies\ChatPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\ShipmentPolicy;
use App\Services\Carriers\CarrierServiceFactory;
use App\Services\QuoteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CarrierServiceFactory::class, function ($app) {
            return new CarrierServiceFactory();
        });

        $this->app->singleton(QuoteService::class, function ($app) {
            return new QuoteService($app->make(CarrierServiceFactory::class));
        });
    }

    public function boot(): void
    {
        // Register policies
        Gate::policy(Shipment::class, ShipmentPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Chat::class, ChatPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }
}
