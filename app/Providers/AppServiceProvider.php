<?php

namespace App\Providers;

use App\Observers\OrderMarketingObserver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Nreljed\ShopModule\Models\Order;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Real-time marketing integration: sync shop orders into the marketing
        // audience and trigger order-placed workflows as orders are created.
        Order::observe(OrderMarketingObserver::class);

        // Shop policy bindings moved to Nreljed\ShopModule\ShopServiceProvider::registerPolicies()

        // Share store contact details (from the project template-kit config)
        // so public pages render real contact info without hardcoding.
        Inertia::share('contact', function () {
            $contact = Config::get('contact', []);

            return [
                'email' => $contact['email'] ?? '',
                'phone' => $contact['phone'] ?? '',
                'address' => $contact['address'] ?? '',
                'hours' => $contact['hours'] ?? '',
            ];
        });
    }
}
