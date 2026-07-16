<?php

namespace Plugins\StorageOss\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Starship\Core\Contracts\Storage\StorageInterface;
use Starship\Core\Plugin\PluginConfigRepository;

/**
 * 阿里云 OSS 存储（S3 兼容 API，复用 league/flysystem-aws-s3-v3）。
 */
final class AliOssStorage implements StorageInterface
{
    public const DISK = 'plugin-storage-oss';

    public function __construct(
        protected PluginConfigRepository $pluginConfigRepository,
    ) {}

    public function put(string $path, mixed $content): bool
    {
        return (bool) $this->filesystem()->put($this->objectPath($path), $content);
    }

    public function get(string $path): string
    {
        $content = $this->filesystem()->get($this->objectPath($path));
        if ($content === null) {
            throw new RuntimeException("文件不存在：{$path}");
        }

        return $content;
    }

    public function delete(string $path): bool
    {
        return (bool) $this->filesystem()->delete($this->objectPath($path));
    }

    public function url(string $path): string
    {
        $objectPath = $this->objectPath($path);
        $cdn = rtrim($this->config('cdn_url'), '/');
        if ($cdn !== '') {
            return $cdn.'/'.ltrim($objectPath, '/');
        }

        return $this->filesystem()->url($objectPath);
    }

    public function exists(string $path): bool
    {
        return $this->filesystem()->exists($this->objectPath($path));
    }

    protected function filesystem(): Filesystem
    {
        $this->applyDiskConfig();

        return Storage::disk(self::DISK);
    }

    protected function applyDiskConfig(): void
    {
        $accessKey = $this->config('access_key');
        $secretKey = $this->config('secret_key');
        $bucket = $this->config('bucket');
        $region = $this->config('region', 'cn-hangzhou') ?: 'cn-hangzhou';
        $endpoint = $this->normalizeEndpoint($this->config('endpoint'), $region);
        $cdn = rtrim($this->config('cdn_url'), '/');

        if ($accessKey === '' || $secretKey === '' || $bucket === '') {
            throw new RuntimeException(__('storage-oss::messages.config_incomplete'));
        }

        config([
            'filesystems.disks.'.self::DISK => [
                'driver' => 's3',
                'key' => $accessKey,
                'secret' => $secretKey,
                'region' => $region,
                'bucket' => $bucket,
                'url' => $cdn !== '' ? $cdn : null,
                'endpoint' => $endpoint,
                'use_path_style_endpoint' => false,
                'visibility' => 'public',
                'throw' => true,
                'report' => false,
            ],
        ]);
    }

    protected function objectPath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $prefix = trim($this->config('prefix'), '/');

        if ($prefix === '') {
            return $path;
        }

        return $prefix.'/'.$path;
    }

    protected function normalizeEndpoint(string $endpoint, string $region): string
    {
        if ($endpoint !== '') {
            return str_starts_with($endpoint, 'http') ? $endpoint : 'https://'.$endpoint;
        }

        return 'https://oss-'.$region.'.aliyuncs.com';
    }

    protected function config(string $key, string $default = ''): string
    {
        return trim((string) $this->pluginConfigRepository->get('storage-oss', $key, $default));
    }
}
