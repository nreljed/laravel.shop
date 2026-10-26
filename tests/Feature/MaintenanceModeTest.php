<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Nreljed\Platform\Http\Middleware\CheckMaintenanceMode;
use Nreljed\Platform\Services\SettingsService;
use Tests\TestCase;

/**
 * Maintenance mode must render, not 500 - and it must actually cover the shop.
 *
 * `CheckMaintenanceMode` lives in the platform package and renders the
 * package's own `maintenance` blade, which is only published under the
 * `core::` namespace. Rendering it by bare name works on a machine where the
 * views were published into `resources/views`, and returns
 * "View [maintenance] not found" - a 500 instead of a 503 - everywhere else.
 * The same mistake made `/maintenance/recovery` 500 in production.
 *
 * This renders the real middleware through a real request, so it fails if the
 * namespace ever regresses.
 *
 * The second half is the coverage half: the storefront (`/`, `/store/*`) and
 * the blog (`/blog`) are registered by the shop and blog packages, not by
 * platform, so they were reachable while every platform page answered 503 -
 * a shopper could browse (and check out against) tables that were being
 * rebuilt by the admin UI's `migrate:fresh`. Both packages now carry the
 * middleware, and the assertions below fail if either drops it again.
 *
 * The Stripe webhook is the one deliberate exception: it stays reachable
 * during a maintenance window so a payment authorised just before the doors
 * closed still settles. That is asserted too, so nobody "fixes" it later by
 * gating the group the webhook came from.
 */
class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        SettingsService::set('maintenance_mode', '0');

        parent::tearDown();
    }

    public function test_guests_see_the_maintenance_page_instead_of_an_error(): void
    {
        SettingsService::set('maintenance_mode', '1');
        SettingsService::set('maintenance_message', 'Back in a moment.');

        $response = $this->get('/services');

        $response->assertStatus(503);
        $response->assertSee('Back in a moment.', false);
        $response->assertHeader('Retry-After');

        $this->assertStringNotContainsString(
            'View [maintenance] not found',
            $response->getContent(),
        );
    }

    public function test_every_public_surface_is_gated_while_maintenance_mode_is_on(): void
    {
        SettingsService::set('maintenance_mode', '1');

        // Platform pages, the storefront (home page included) and the blog.
        // Unknown slugs are intentional: the middleware runs before the
        // controller, so a 503 here proves the whole prefix is gated rather
        // than one hand-picked route.
        $uris = [
            '/services',
            '/about',
            '/contact',
            '/',
            '/store',
            '/store/products',
            '/store/cart',
            '/blog',
            '/blog/any-post-slug',
        ];

        foreach ($uris as $uri) {
            $response = $this->get($uri);

            $response->assertStatus(503);
            $response->assertHeader('Retry-After');

            $this->assertStringNotContainsString(
                'View [maintenance] not found',
                $response->getContent(),
                "Maintenance view is not resolvable on {$uri}.",
            );
        }
    }

    public function test_the_maintenance_middleware_is_what_gates_the_public_surface(): void
    {
        // Guards the assumption the tests above rest on: these routes carry
        // `maintenance` on their own, rather than returning 503 for some other
        // reason that happens to coincide.
        $gated = $this->routesCarryingMaintenance();

        $platform = ['services', 'about', 'contact'];

        // `/` is registered by the host (FrontendRouteServiceProvider), the
        // rest by the shop and blog packages.
        $packageOwned = [
            '/',
            'store',
            'store/products',
            'store/{product}',
            'store/cart',
            'store/checkout',
            'store/wishlist',
            'blog',
            'blog/{slug}',
        ];

        foreach ([...$platform, ...$packageOwned] as $uri) {
            $this->assertContains(
                $uri,
                $gated,
                "Route [{$uri}] is not behind maintenance mode.",
            );
        }

        // Payment callbacks are the exception: an authorised payment has to
        // settle even while the storefront is closed.
        $this->assertNotContains('store/webhook/stripe', $gated);
    }

    public function test_the_stripe_webhook_stays_reachable_during_maintenance(): void
    {
        SettingsService::set('maintenance_mode', '1');

        // No signature header, so the controller rejects it - the point is
        // that the request reaches the controller (400) instead of being
        // answered with the maintenance page (503).
        $response = $this->postJson('/store/webhook/stripe', [
            'id' => 'evt_probe',
            'type' => 'payment_intent.succeeded',
        ]);

        $response->assertStatus(400);
    }

    public function test_admins_bypass_maintenance_mode(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        SettingsService::set('maintenance_mode', '1');

        foreach (['/services', '/', '/store', '/blog'] as $uri) {
            $this->actingAs($user)->get($uri)->assertOk();
        }
    }

    /**
     * @return Collection<int, string>
     */
    protected function routesCarryingMaintenance()
    {
        return collect(app('router')->getRoutes())
            ->filter(fn ($route) => collect($route->gatherMiddleware())->contains(
                fn ($middleware) => $middleware === 'maintenance'
                    || $middleware === CheckMaintenanceMode::class,
            ))
            ->map(fn ($route) => $route->uri())
            ->unique()
            ->values();
    }
}
