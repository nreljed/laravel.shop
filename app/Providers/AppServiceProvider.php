<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Shop policy bindings moved to Nreljed\ShopModule\ShopServiceProvider::registerPolicies()
    }
}
