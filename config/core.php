<?php

use Nreljed\DashboardCore\Ai\AiServiceProvider;
use Nreljed\DashboardCore\Http\Middleware\HandleInertiaRequests;
use Nreljed\DashboardCore\Models\User;
use Nreljed\DashboardCore\Posts\Models\Post;
use Nreljed\DashboardCore\Posts\PostsServiceProvider;
use Nreljed\DashboardCore\PublicSite\PublicSiteServiceProvider;
use Nreljed\ShopModule\ShopServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Load Migrations
    |--------------------------------------------------------------------------
    |
    | Enable package migrations — the shop app does not ship its own
    | equivalents for the core tables.
    |
    */

    'load_migrations' => true,

    /*
    |--------------------------------------------------------------------------
    | Inertia Request Middleware
    |--------------------------------------------------------------------------
    |
    | Shared props handler appended to the web group. Modules contribute
    | shared props declaratively via FeatureRegistry::getAllSharedData().
    |
    */

    'inertia_middleware' => HandleInertiaRequests::class,

    /*
    |--------------------------------------------------------------------------
    | Feature Service Providers
    |--------------------------------------------------------------------------
    |
    | Self-contained features discovered by the shared FeatureRegistry.
    | Posts is provided by dashboard-core; Shop by the shop-module package.
    |
    */

    'features' => [
        PublicSiteServiceProvider::class,
        PostsServiceProvider::class,
        AiServiceProvider::class,
        ShopServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Defaults
    |--------------------------------------------------------------------------
    |
    | Per-feature enabled/disabled defaults. These sit below the persisted
    | settings table (FeatureSettingsService) but above the fail-closed
    | fallback in InteractsWithFeatures::defaultEnabled(). An admin can
    | toggle any feature at runtime via the modules settings page; this
    | config only controls the out-of-the-box state.
    |
    */

    'features_enabled' => [
        'public-site' => true,
        'posts' => true,
        'ai' => true,
        'shop' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Orphan Media Targets
    |--------------------------------------------------------------------------
    */

    'orphan_media' => [
        'posts' => Post::class,
        'avatars' => User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Groups
    |--------------------------------------------------------------------------
    |
    | Additional settings groups beyond the core defaults.
    |
    */

    'settings' => [
        'groups' => ['localization'],
    ],

];
