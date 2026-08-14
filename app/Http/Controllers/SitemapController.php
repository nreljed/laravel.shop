<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Nreljed\DashboardCore\Blog\Models\Post;
use Nreljed\DashboardCore\Blog\Models\PostCategory;
use Nreljed\DashboardCore\Blog\Models\PostTag;
use Nreljed\DashboardCore\Http\Controllers\SitemapController as BaseSitemapController;
use Nreljed\DashboardCore\Services\SettingsService;
use Nreljed\ShopModule\Models\Product;
use Nreljed\ShopModule\Models\ProductCategory;

class SitemapController extends BaseSitemapController
{
    public function index()
    {
        $baseUrl = rtrim((string) SettingsService::get('app_url', config('app.url', '')), '/');

        if ($baseUrl === '') {
            $baseUrl = url('/');
        }

        $urls = [
            $this->url($baseUrl.'/', 'daily', '1.0'),
            $this->url($baseUrl.'/about', 'monthly', '0.5'),
            $this->url($baseUrl.'/services', 'monthly', '0.5'),
            $this->url($baseUrl.'/contact', 'monthly', '0.5'),
            $this->url($baseUrl.'/blog', 'daily', '0.8'),
            $this->url($baseUrl.'/store', 'daily', '0.9'),
            $this->url($baseUrl.'/store/products', 'daily', '0.8'),
        ];

        $publishedScope = function ($query) {
            $query->where('status', 'published')
                ->where(function ($q) {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                });
        };

        $posts = Post::query()
            ->where(fn ($q) => $publishedScope($q))
            ->latest('published_at')
            ->get(['slug', 'updated_at']);

        foreach ($posts as $post) {
            $urls[] = $this->url(
                $baseUrl.'/blog/'.$post->slug,
                'weekly',
                '0.7',
                $post->updated_at?->toDateString(),
            );
        }

        $categories = PostCategory::query()
            ->whereHas('posts', fn ($q) => $publishedScope($q))
            ->get(['slug']);

        foreach ($categories as $category) {
            $urls[] = $this->url($baseUrl.'/blog?category='.$category->slug, 'weekly', '0.5');
        }

        $tags = PostTag::query()
            ->whereHas('posts', fn ($q) => $publishedScope($q))
            ->get(['slug']);

        foreach ($tags as $tag) {
            $urls[] = $this->url($baseUrl.'/blog?tag='.$tag->slug, 'weekly', '0.4');
        }

        $storeProducts = Product::query()
            ->where('status', 'published')
            ->whereIn('catalog_visibility', ['visible', 'catalog'])
            ->orderBy('updated_at', 'desc')
            ->get(['slug', 'updated_at']);

        foreach ($storeProducts as $product) {
            $urls[] = $this->url(
                $baseUrl.'/store/'.$product->slug,
                'weekly',
                '0.8',
                $product->updated_at?->toDateString(),
            );
        }

        $storeCategories = ProductCategory::query()
            ->whereHas('products', fn ($q) => $q->where('status', 'published'))
            ->get(['slug']);

        foreach ($storeCategories as $category) {
            $urls[] = $this->url($baseUrl.'/store/category/'.$category->slug, 'weekly', '0.6');
        }

        $xml = $this->buildXml($urls);

        return app(ResponseFactory::class)->make($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
