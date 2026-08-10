<?php

namespace Database\Seeders\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

/**
 * Ensures an admin user exists before a seeder runs.
 *
 * The maintenance reset flow seeds demo groups before app:create-admin
 * runs, so demo seeders that attribute records to an admin user must
 * bootstrap one themselves. Re-running the flow is a no-op when an admin
 * already exists.
 */
trait BootstrapsAdmin
{
    private function ensureAdminUser(): User
    {
        $admin = User::whereHas('roles', fn ($query) => $query->where('slug', 'admin'))->first();

        if ($admin) {
            return $admin;
        }

        Artisan::call('app:create-admin', [
            '--no-interaction' => true,
            '--email' => config('admin.email') ?: 'admin@example.com',
            '--password' => config('admin.password') ?: 'password',
            '--name' => config('admin.name') ?: 'Admin',
        ]);

        if (trim(Artisan::output()) !== '') {
            $this->command->line(trim(Artisan::output()));
        }

        return User::whereHas('roles', fn ($query) => $query->where('slug', 'admin'))->firstOrFail();
    }
}
