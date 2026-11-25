<?php

namespace Starship\Core\Tests\Feature;

use Illuminate\Support\Facades\File;
use Starship\Core\Install\InstallLock;
use Starship\Core\Tests\TestCase;

class InstallApiTest extends TestCase
{
    protected function tearDown(): void
    {
        $lock = new InstallLock;
        if (File::exists($lock->path())) {
            File::delete($lock->path());
        }

        parent::tearDown();
    }

    public function test_status_is_public_and_reports_not_installed(): void
    {
        $response = $this->getJson('/admin-api/install/status');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.installed', false);
    }

    public function test_requirements_available_when_not_installed(): void
    {
        $response = $this->getJson('/admin-api/install/requirements');

        $response->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonStructure([
                'data' => [
                    'passed',
                    'php' => ['required', 'current', 'passed'],
                    'extensions',
                    'directories',
                ],
            ]);
    }

    public function test_mutating_endpoints_blocked_after_lock(): void
    {
        (new InstallLock)->lock();

        $this->getJson('/admin-api/install/status')
            ->assertOk()
            ->assertJsonPath('data.installed', true);

        $this->getJson('/admin-api/install/requirements')
            ->assertStatus(403);

        $this->postJson('/admin-api/install/database', [
            'connection' => 'sqlite',
            'database' => ':memory:',
            'app_url' => 'http://127.0.0.1:8000',
        ])->assertStatus(403);

        $this->postJson('/admin-api/install/run', [
            'site_name' => 'Starship Admin',
            'username' => 'admin',
            'password' => 'secret123',
            'email' => 'admin@starship.org',
        ])->assertStatus(403);
    }

    public function test_database_requires_core_fields(): void
    {
        $this->postJson('/admin-api/install/database', [
            'connection' => 'mysql',
        ])->assertStatus(422);
    }

    public function test_run_requires_admin_fields(): void
    {
        $this->postJson('/admin-api/install/run', [
            'password' => 'secret123',
        ])->assertStatus(422);
    }
}
