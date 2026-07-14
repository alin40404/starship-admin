<?php

namespace Modules\System\App\Support\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Modules\System\App\Support\StorageConfigRegistrar;
use RuntimeException;
use Starship\Core\Contracts\Storage\StorageInterface;

/**
 * 默认存储实现：代理到系统配置的 Filesystem disk（public / local / oss / cos / s3）。
 */
class LocalStorage implements StorageInterface
{
    public function __construct(
        protected ?string $disk = null,
    ) {}

    public function put(string $path, mixed $content): bool
    {
        return (bool) $this->filesystem()->put($path, $content);
    }

    public function get(string $path): string
    {
        $content = $this->filesystem()->get($path);
        if ($content === null) {
            throw new RuntimeException("文件不存在：{$path}");
        }

        return $content;
    }

    public function delete(string $path): bool
    {
        return (bool) $this->filesystem()->delete($path);
    }

    public function url(string $path): string
    {
        return $this->filesystem()->url($path);
    }

    public function exists(string $path): bool
    {
        return $this->filesystem()->exists($path);
    }

    /**
     * 指定磁盘（如附件记录上的 storage 字段）。
     */
    public function onDisk(?string $disk): self
    {
        return new self($disk);
    }

    protected function filesystem(): Filesystem
    {
        return Storage::disk(StorageConfigRegistrar::resolveDisk($this->disk));
    }
}
