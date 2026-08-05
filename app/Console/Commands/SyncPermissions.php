<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Nreljed\DashboardCore\Models\Permission;
use Nreljed\DashboardCore\Models\Role;
use Nreljed\DashboardCore\Services\FeatureRegistry;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sync feature permissions into the permissions table and assign to admin and shop_owner roles';

    public function handle(FeatureRegistry $registry): int
    {
        if (! Schema::hasTable('permissions')) {
            $this->error('Permissions table does not exist. Run migrations first.');

            return Command::FAILURE;
        }

        $existing = Permission::pluck('slug')->toArray();
        $synced = 0;

        foreach ($registry->allProviders() as $provider) {
            foreach ($provider->permissions() as $perm) {
                if (! in_array($perm['slug'], $existing)) {
                    Permission::create([
                        'name' => $perm['name'],
                        'slug' => $perm['slug'],
                        'description' => $perm['description'] ?? '',
                        'guard_name' => 'web',
                    ]);
                    $this->line("  Created: {$perm['slug']}");
                    $synced++;
                }
            }
        }

        $this->info("Synced {$synced} new permission(s).");

        $permSlugs = [];
        foreach ($registry->allProviders() as $provider) {
            foreach ($provider->permissions() as $perm) {
                $permSlugs[] = $perm['slug'];
            }
        }
        $featurePerms = Permission::whereIn('slug', $permSlugs)->pluck('id');

        // Auto-assign all feature permissions to admin role (admin gets everything)
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $existingIds = $admin->permissions()->pluck('permissions.id')->toArray();
            $newIds = $featurePerms->diff($existingIds)->values()->toArray();

            if (! empty($newIds)) {
                $admin->permissions()->attach($newIds);
                $this->line('  Assigned '.count($newIds).' feature permission(s) to admin role.');
            } else {
                $this->line('  No new feature permissions to assign to admin role.');
            }
        }

        // Auto-assign all feature permissions to shop_owner role
        $shopOwner = Role::where('slug', 'shop_owner')->first();
        if ($shopOwner) {
            $existingIds = $shopOwner->permissions()->pluck('permissions.id')->toArray();
            $newIds = $featurePerms->diff($existingIds)->values()->toArray();

            if (! empty($newIds)) {
                $shopOwner->permissions()->attach($newIds);
                $this->line('  Assigned '.count($newIds).' feature permission(s) to shop_owner role.');
            } else {
                $this->line('  No new feature permissions to assign to shop_owner role.');
            }
        }

        return Command::SUCCESS;
    }
}
