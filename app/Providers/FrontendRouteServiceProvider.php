<?php

namespace App\Providers;

use App\Shop\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers project-level public routes that must take precedence over the
 * dashboard-core package defaults.
 *
 * dashboard-core registers its own `/` route (named `home`) while the app
 * boots. Laravel's RouteCollection keeps only the LAST route registered for a
 * given URI + method pair, so registering here in `register()` is not enough —
 * the package route would overwrite ours. Instead we register the route inside
 * a `booted()` callback, which runs after every provider has booted, so our
 * route always wins.
 */
class FrontendRouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->booted(function () {
            Route::middleware('web')
                ->get('/', [StorefrontController::class, 'index'])
                ->name('home');
        });
    }
}
