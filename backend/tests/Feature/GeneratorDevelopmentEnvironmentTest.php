<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Generator\App\Support\GeneratorPermissions;
use Modules\System\App\Models\Admin;
use Starship\Core\Support\DevEnvironment;
use Tests\TestCase;

class GeneratorDevelopmentEnvironmentTest extends TestCase
{
    public function test_generator_tables_api_is_forbidden_in_production(): void
    {
        $this->app->detectEnvironment(static fn () => 'production');

        $this->assertFalse(DevEnvironment::isDevelopment());

        $admin = Admin::query()->where('is_super', 1)->first();
        if ($admin === null) {
            $this->markTestSkipped('No super admin in database.');
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/admin-api/generator/tables');

        $response->assertForbidden();
    }

    public function test_generator_tables_api_is_allowed_in_local(): void
    {
        $this->app->detectEnvironment(static fn () => 'local');

        $this->assertTrue(DevEnvironment::isDevelopment());

        $admin = Admin::query()->where('is_super', 1)->first();
        if ($admin === null) {
            $this->markTestSkipped('No super admin in database.');
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/admin-api/generator/tables');

        $response->assertOk();
    }

    public function test_auth_menus_excludes_codegen_in_production(): void
    {
        $this->app->detectEnvironment(static fn () => 'production');

        $admin = Admin::query()->where('is_super', 1)->first();
        if ($admin === null) {
            $this->markTestSkipped('No super admin in database.');
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/admin-api/auth/menus');

        $response->assertOk();
        $this->assertStringNotContainsString(
            '/dev/codegen',
            json_encode($response->json('data'), JSON_UNESCAPED_UNICODE) ?: '',
        );
    }
}
