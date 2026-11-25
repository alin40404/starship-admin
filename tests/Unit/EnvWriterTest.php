<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use Starship\Core\Install\EnvWriter;
use Starship\Core\Tests\TestCase;

class EnvWriterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'starship-env-writer-'.uniqid('', true);
        File::ensureDirectoryExists($this->tempDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    public function test_ensure_env_copies_example(): void
    {
        File::put($this->tempDir.DIRECTORY_SEPARATOR.'.env.example', "APP_NAME=Demo\nDB_CONNECTION=sqlite\n");

        $writer = new EnvWriter($this->tempDir);
        $path = $writer->ensureEnvFile();

        $this->assertFileExists($path);
        $this->assertStringContainsString('APP_NAME=Demo', File::get($path));
    }

    public function test_set_many_replaces_and_appends(): void
    {
        File::put($this->tempDir.DIRECTORY_SEPARATOR.'.env', "APP_NAME=Old\nDB_CONNECTION=sqlite\n");

        $writer = new EnvWriter($this->tempDir);
        $writer->setMany([
            'APP_NAME' => 'Starship',
            'DB_PASSWORD' => 'secret value',
            'NEW_KEY' => 'yes',
        ]);

        $content = File::get($this->tempDir.DIRECTORY_SEPARATOR.'.env');
        $this->assertStringContainsString('APP_NAME=Starship', $content);
        $this->assertStringContainsString('DB_PASSWORD="secret value"', $content);
        $this->assertStringContainsString('NEW_KEY=yes', $content);
        $this->assertStringNotContainsString('APP_NAME=Old', $content);
    }
}
