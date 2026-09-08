<?php

use App\Providers\AppServiceProvider;
use App\Providers\FrontendRouteServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Nreljed\DashboardCore\Ai\AiServiceProvider;
use Nreljed\DashboardCore\DashboardCoreServiceProvider;
use Nreljed\DashboardCore\Posts\PostsServiceProvider;
use Nreljed\DashboardCore\Providers\PermissionGateServiceProvider;
use Nreljed\DashboardCore\PublicSite\PublicSiteServiceProvider;
use Nreljed\ShopModule\ShopServiceProvider;

return array_filter([
    DashboardCoreServiceProvider::class,
    FrontendRouteServiceProvider::class,
    AppServiceProvider::class,
    ShopServiceProvider::class,
    PostsServiceProvider::class,
    PublicSiteServiceProvider::class,
    AiServiceProvider::class,
    PermissionGateServiceProvider::class,
    class_exists(TelescopeApplicationServiceProvider::class)
        ? TelescopeServiceProvider::class
        : null,
]);
