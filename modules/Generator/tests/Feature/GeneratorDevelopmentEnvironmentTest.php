<?php

namespace Modules\Generator\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\System\App\Models\Admin;
use Starship\Core\Support\DevEnvironment;
use Tests\TestCase;

class GeneratorDevelopmentEnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml 默认 sqlite；本机无 pdo_sqlite 时回退 .env 的 mysql
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $connection = $this->dotenvValue('DB_CONNECTION') ?: 'mysql';
            if ($connection === 'sqlite') {
                $connection = 'mysql';
            }

            config([
                'database.default' => $connection,
                "database.connections.{$connection}.database" => $this->dotenvValue('DB_DATABASE') ?: 'starship',
                "database.connections.{$connection}.host" => $this->dotenvValue('DB_HOST') ?: '127.0.0.1',
                "database.connections.{$connection}.port" => $this->dotenvValue('DB_PORT') ?: '3306',
                "database.connections.{$connection}.username" => $this->dotenvValue('DB_USERNAME') ?: 'root',
                "database.connections.{$connection}.password" => $this->dotenvValue('DB_PASSWORD') ?? '',
            ]);
            DB::purge();
            DB::reconnect();
        }
    }

    protected function dotenvValue(string $key): ?string
    {
        $path = base_path('.env');
        if (! is_file($path)) {
            return null;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            if (trim($name) !== $key) {
                continue;
            }

            return trim($value, " \t\"'");
        }

        return null;
    }

    public function test_generator_tables_api_is_forbidden_in_production(): void
    {
        $admin = $this->superAdminOrSkip();

        $this->app->detectEnvironment(static fn () => 'production');

        $this->assertFalse(DevEnvironment::isDevelopment());

        $this->actingAs($admin, 'sanctum')
            ->getJson('/admin-api/generator/tables')
            ->assertForbidden();
    }

    public function test_generator_tables_api_is_allowed_in_local(): void
    {
        $admin = $this->superAdminOrSkip();

        $this->app->detectEnvironment(static fn () => 'local');

        $this->assertTrue(DevEnvironment::isDevelopment());

        $this->actingAs($admin, 'sanctum')
            ->getJson('/admin-api/generator/tables')
            ->assertOk();
    }

    public function test_auth_menus_excludes_codegen_in_production(): void
    {
        $admin = $this->superAdminOrSkip();

        $this->app->detectEnvironment(static fn () => 'production');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/admin-api/auth/menus');

        $response->assertOk();
        $this->assertStringNotContainsString(
            '/dev/codegen',
            json_encode($response->json('data'), JSON_UNESCAPED_UNICODE) ?: '',
        );
    }

    protected function superAdminOrSkip(): Admin
    {
        if (! $this->databaseReady()) {
            $this->markTestSkipped('数据库不可用，跳过 Generator 环境用例。');
        }

        $admin = Admin::query()->where('is_super', 1)->first();
        if ($admin === null) {
            $this->markTestSkipped('No super admin in database.');
        }

        return $admin;
    }

    protected function databaseReady(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
