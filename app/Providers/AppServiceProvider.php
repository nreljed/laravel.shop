<?php

namespace App\Providers;

use App\Listeners\MarketingCartAbandonedListener;
use App\Listeners\MarketingProductReviewedListener;
use App\Observers\OrderMarketingObserver;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Nreljed\ShopModule\Events\CartAbandoned;
use Nreljed\ShopModule\Events\ProductReviewed;
use Nreljed\ShopModule\Models\Order;
use Nreljed\ShopModule\Services\CartMergeService;

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

        // Merge any lingering guest cart into the user's cart on login, so a shopper
        // who added items while anonymous keeps them after signing in (not only at
        // checkout). Best-effort: never block the login flow on a cart merge failure.
        Event::listen(LoginEvent::class, function (LoginEvent $event): void {
            if (! $event->user) {
                return;
            }

            try {
                app(CartMergeService::class)->mergeGuestCartIntoUserCart($event->user);
            } catch (\Throwable $e) {
                Log::warning('Guest cart merge on login failed', [
                    'user_id' => $event->user->getAuthIdentifier(),
                    'error' => $e->getMessage(),
                ]);
            }
        });

        // Marketing workflows for cart abandonment and product reviews.
        Event::listen(CartAbandoned::class, MarketingCartAbandonedListener::class);
        Event::listen(ProductReviewed::class, MarketingProductReviewedListener::class);

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
