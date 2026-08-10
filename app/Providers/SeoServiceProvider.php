<?php

namespace App\Providers;

use App\Http\Controllers\SitemapController;
use Illuminate\Support\ServiceProvider;
use Nreljed\DashboardCore\Http\Controllers\SitemapController as BaseSitemapController;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BaseSitemapController::class, SitemapController::class);
    }
}
