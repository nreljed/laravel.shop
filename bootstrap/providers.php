<?php

use App\Providers\AppServiceProvider;
use App\Providers\FrontendRouteServiceProvider;
use App\Providers\SeoServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Shop\ShopServiceProvider;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Nreljed\DashboardCore\Blog\BlogServiceProvider;
use Nreljed\DashboardCore\DashboardCoreServiceProvider;
use Nreljed\DashboardCore\Providers\PermissionGateServiceProvider;

return array_filter([
    DashboardCoreServiceProvider::class,
    FrontendRouteServiceProvider::class,
    AppServiceProvider::class,
    SeoServiceProvider::class,
    ShopServiceProvider::class,
    BlogServiceProvider::class,
    PermissionGateServiceProvider::class,
    class_exists(TelescopeApplicationServiceProvider::class)
        ? TelescopeServiceProvider::class
        : null,
]);
