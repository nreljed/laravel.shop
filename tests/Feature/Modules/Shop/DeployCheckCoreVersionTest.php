<?php

namespace Tests\Feature\Modules\Shop;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeployCheckCoreVersionTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = 'test-deploy-token-'.Str::random(16);
        Config::set('app.deploy_token', $this->token);
    }

    public function test_check_reports_core_commit_when_recorded(): void
    {
        $versionFile = base_path('.deployed-version');
        $lockFile = base_path('.deploy-lock');

        Http::fake([
            'api.github.com/repos/nreljed/laravel.shop/releases*' => Http::response([
                ['tag_name' => 'v0.1.34', 'assets' => []],
            ], 200),
        ]);

        File::put($versionFile, 'v0.1.34');
        File::put(storage_path('app/core.sha'), '50e9c0fabcd1234567890'.PHP_EOL);

        try {
            $response = $this->getJson('/deploy/check?token='.$this->token);

            $response->assertOk();
            $response->assertJsonPath('status', 'current');
            $response->assertJsonPath('tag', 'v0.1.34');
            $response->assertJsonPath('core', '50e9c0fabcd1234567890');
        } finally {
            File::delete($versionFile);
            File::delete(storage_path('app/core.sha'));
            File::delete($lockFile);
        }
    }

    public function test_check_reports_null_core_when_not_recorded(): void
    {
        $versionFile = base_path('.deployed-version');
        $lockFile = base_path('.deploy-lock');

        Http::fake([
            'api.github.com/repos/nreljed/laravel.shop/releases*' => Http::response([
                ['tag_name' => 'v0.1.34', 'assets' => []],
            ], 200),
        ]);

        File::put($versionFile, 'v0.1.34');
        File::delete(storage_path('app/core.sha'));

        try {
            $response = $this->getJson('/deploy/check?token='.$this->token);

            $response->assertOk();
            $response->assertJsonPath('status', 'current');
            $response->assertJsonPath('core', null);
        } finally {
            File::delete($versionFile);
            File::delete($lockFile);
        }
    }
}
