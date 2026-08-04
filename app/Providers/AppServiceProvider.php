<?php

namespace App\Providers;

use App\Policies\CouponPolicy;
use App\Policies\CurrencyPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductReviewPolicy;
use App\Policies\ShippingMethodPolicy;
use App\Policies\ShippingZonePolicy;
use App\Policies\TaxRatePolicy;
use App\Policies\WebhookPolicy;
use App\Shop\Models\Coupon;
use App\Shop\Models\Currency;
use App\Shop\Models\Order;
use App\Shop\Models\Product;
use App\Shop\Models\ProductReview;
use App\Shop\Models\ShippingMethod;
use App\Shop\Models\ShippingZone;
use App\Shop\Models\TaxRate;
use App\Shop\Models\Webhook;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Shop-specific policy bindings (core policies handled by DashboardCoreServiceProvider)
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Currency::class, CurrencyPolicy::class);
        Gate::policy(ProductReview::class, ProductReviewPolicy::class);
        Gate::policy(ShippingMethod::class, ShippingMethodPolicy::class);
        Gate::policy(ShippingZone::class, ShippingZonePolicy::class);
        Gate::policy(TaxRate::class, TaxRatePolicy::class);
        Gate::policy(Webhook::class, WebhookPolicy::class);
    }
}
