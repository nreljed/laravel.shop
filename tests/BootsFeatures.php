<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Nreljed\DashboardCore\Models\Permission;
use Nreljed\DashboardCore\Models\Role;
use Nreljed\DashboardCore\Services\FeatureRegistry;
use Nreljed\DashboardCore\Services\PermissionCacheService;

trait BootsFeatures
{
    protected function bootFeatures(): void
    {
        $registry = app(FeatureRegistry::class);

        app(RolesAndPermissionsSeeder::class)->run();

        app('router')->getRoutes()->refreshNameLookups();

        // Collect permission slugs from all registered feature providers
        $featureSlugs = [];
        foreach ($registry->allProviders() as $provider) {
            foreach ($provider->permissions() as $perm) {
                $featureSlugs[] = $perm['slug'];
            }
        }

        // Create feature permissions in DB (not done by seeders)
        $existing = Permission::pluck('slug')->toArray();
        foreach ($featureSlugs as $slug) {
            if (! in_array($slug, $existing)) {
                Permission::create([
                    'name' => $slug,
                    'slug' => $slug,
                    'description' => '',
                    'guard_name' => 'web',
                ]);
                $existing[] = $slug;
            }
        }

        // Ensure manage_options permission exists
        if (! in_array('manage_options', $existing)) {
            Permission::create([
                'name' => 'Manage Options',
                'slug' => 'manage_options',
                'description' => 'Access application settings.',
                'guard_name' => 'web',
            ]);
        }

        $allPermIds = Permission::all()->pluck('id');
        $featurePermIds = Permission::whereIn('slug', $featureSlugs)->pluck('id');

        // Admin gets ALL permissions (production: Gate::before + permissions:sync)
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->sync($allPermIds);
        }

        // Shop Owner gets ALL feature permissions (matches production: permissions:sync)
        $shopOwnerRole = Role::where('slug', 'shop_owner')->first();
        if ($shopOwnerRole) {
            $existingIds = $shopOwnerRole->permissions()->pluck('permissions.id');
            $newIds = $featurePermIds->diff($existingIds)->values()->toArray();
            if (! empty($newIds)) {
                $shopOwnerRole->permissions()->attach($newIds);
            }
        }

        $this->clearPermissionCache();
    }

    protected function createUserWithRole(string $roleSlug): mixed
    {
        $user = User::factory()->create();
        $user->assignRole($roleSlug);
        $this->clearUserPermissionCache($user->id);

        return $user;
    }

    protected function clearPermissionCache(): void
    {
        app(PermissionCacheService::class)->flushAll();
    }

    protected function clearUserPermissionCache(int $userId): void
    {
        app(PermissionCacheService::class)->forgetUser($userId);
    }
}
