<?php

use Nreljed\DashboardCore\Blog\BlogServiceProvider;
use Nreljed\DashboardCore\Blog\Models\Post;
use Nreljed\DashboardCore\Http\Middleware\HandleInertiaRequests;
use Nreljed\DashboardCore\Models\User;
use Nreljed\ShopModule\Models\Product;
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
    | Shared props handler appended to the web group. The shop-module
    | provider extends the core handler to add cart / wishlist / currency
    | shared props.
    |
    */

    'inertia_middleware' => HandleInertiaRequests::class,

    /*
    |--------------------------------------------------------------------------
    | Feature Service Providers
    |--------------------------------------------------------------------------
    |
    | Self-contained features discovered by the shared FeatureRegistry.
    | Blog is provided by dashboard-core; Shop by the shop-module package.
    |
    */

    'features' => [
        BlogServiceProvider::class,
        ShopServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Orphan Media Targets
    |--------------------------------------------------------------------------
    */

    'orphan_media' => [
        'products' => Product::class,
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
        'groups' => ['localization', 'shop'],
    ],

];
