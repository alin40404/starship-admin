<?php

namespace Starship\Core\Install;

use Illuminate\Support\Facades\File;
use RuntimeException;

class InstallLock
{
    public function path(): string
    {
        return storage_path('app/starship.installed');
    }

    public function isInstalled(): bool
    {
        return File::exists($this->path());
    }

    public function assertNotInstalled(): void
    {
        if ($this->isInstalled()) {
            throw new RuntimeException('项目已安装，如需重新安装请先删除安装锁文件：'.$this->path());
        }
    }

    public function lock(): void
    {
        $path = $this->path();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'installed_at' => now()->toIso8601String(),
            'app' => (string) config('app.name', 'Starship Admin'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
    }
}
