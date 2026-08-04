<?php

use App\Shop\Models\Product;
use App\Shop\ShopServiceProvider;
use Nreljed\DashboardCore\Blog\BlogServiceProvider;
use Nreljed\DashboardCore\Blog\Models\Post;
use Nreljed\DashboardCore\Models\User;

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
    | Feature Service Providers
    |--------------------------------------------------------------------------
    |
    | Self-contained features discovered by the shared FeatureRegistry.
    | Blog is provided by the package; Shop is app-specific.
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
