<?php

namespace Tests\Feature\Admin;

use Nreljed\DashboardCore\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'admin')->first();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_system_health_page_loads_for_admin(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('dashboard.admin.system-health'));

        $response->assertStatus(200);
    }

    public function test_system_health_exposes_core_package_and_modules(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('dashboard.admin.system-health'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('core_package')
            ->has('modules'));
    }

    public function test_system_health_refresh_returns_json(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('dashboard.admin.system-health.refresh'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['core_package', 'modules']);
    }
}
