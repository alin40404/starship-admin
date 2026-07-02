<?php

namespace Modules\System\App\Support;

/**
 * 从系统配置注册附件存储磁盘（public / local / oss / cos / s3）。
 */
final class StorageConfigRegistrar
{
    private static bool $registered = false;

    /** @var list<string> */
    public const LOCAL_DISKS = ['public', 'local'];

    /** @var list<string> */
    public const CLOUD_DISKS = ['oss', 'cos', 's3'];

    /** @var list<string> */
    public const ALL_DISKS = ['public', 'local', 'oss', 'cos', 's3'];

    public static function reset(): void
    {
        self::$registered = false;
        SettingStore::forget();
    }

    public static function apply(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        $publicUrl = trim((string) SettingStore::get('storage.public_url', ''));

        if ($publicUrl !== '') {
            config(['filesystems.disks.public.url' => rtrim($publicUrl, '/')]);
        }

        foreach (self::CLOUD_DISKS as $driver) {
            config(['filesystems.disks.'.$driver => self::buildCloudDiskConfig($driver, $publicUrl)]);
        }
    }

    public static function defaultDisk(): string
    {
        self::apply();

        $driver = (string) SettingStore::get('app.storage.driver', 'public');

        return in_array($driver, self::ALL_DISKS, true) ? $driver : 'public';
    }

    public static function maxUploadSizeKb(): int
    {
        $mb = (int) SettingStore::get('app.upload.max_size', '20');

        return max(1, $mb) * 1024;
    }

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        $raw = SettingStore::get('app.upload.allowed_ext', '[]');
        $decoded = json_decode($raw ?? '[]', true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($ext) => is_string($ext) ? strtolower(trim($ext)) : '',
            $decoded,
        )));
    }

    public static function isCloudDiskReady(string $disk): bool
    {
        if (! in_array($disk, self::CLOUD_DISKS, true)) {
            return true;
        }

        $credentials = self::cloudCredentials($disk);

        return $credentials['key'] !== ''
            && $credentials['secret'] !== ''
            && $credentials['bucket'] !== '';
    }

    public static function resolveDisk(?string $disk): string
    {
        self::apply();

        if (is_string($disk) && $disk !== '' && in_array($disk, self::ALL_DISKS, true)) {
            return $disk;
        }

        return self::defaultDisk();
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildCloudDiskConfig(string $driver, string $publicUrl): array
    {
        $credentials = self::cloudCredentials($driver);
        $endpoint = self::normalizeEndpoint(
            $credentials['endpoint'],
            $driver,
            $credentials['region'],
        );

        $url = $publicUrl !== '' ? rtrim($publicUrl, '/') : null;

        return [
            'driver' => 's3',
            'key' => $credentials['key'],
            'secret' => $credentials['secret'],
            'region' => $credentials['region'] !== '' ? $credentials['region'] : self::defaultRegion($driver),
            'bucket' => $credentials['bucket'],
            'url' => $url,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => false,
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ];
    }

    /**
     * @return array{key: string, secret: string, bucket: string, region: string, endpoint: string}
     */
    private static function cloudCredentials(string $driver): array
    {
        if ($driver === 'cos') {
            return [
                'key' => trim((string) SettingStore::get('storage.cos.secret_id', '')),
                'secret' => trim((string) SettingStore::get('storage.cos.secret_key', '')),
                'bucket' => trim((string) SettingStore::get('storage.cos.bucket', '')),
                'region' => trim((string) SettingStore::get('storage.cos.region', '')),
                'endpoint' => trim((string) SettingStore::get('storage.cos.endpoint', '')),
            ];
        }

        if ($driver === 'oss') {
            return [
                'key' => trim((string) SettingStore::get('storage.oss.access_key', '')),
                'secret' => trim((string) SettingStore::get('storage.oss.secret_key', '')),
                'bucket' => trim((string) SettingStore::get('storage.oss.bucket', '')),
                'region' => trim((string) SettingStore::get('storage.oss.region', '')),
                'endpoint' => trim((string) SettingStore::get('storage.oss.endpoint', '')),
            ];
        }

        return [
            'key' => trim((string) SettingStore::get('storage.s3.access_key', '')),
            'secret' => trim((string) SettingStore::get('storage.s3.secret_key', '')),
            'bucket' => trim((string) SettingStore::get('storage.s3.bucket', '')),
            'region' => trim((string) SettingStore::get('storage.s3.region', '')),
            'endpoint' => trim((string) SettingStore::get('storage.s3.endpoint', '')),
        ];
    }

    private static function defaultRegion(string $driver): string
    {
        return match ($driver) {
            'oss' => 'cn-hangzhou',
            'cos' => 'ap-guangzhou',
            default => 'us-east-1',
        };
    }

    private static function normalizeEndpoint(
        string $endpoint,
        string $driver,
        string $region,
    ): ?string {
        if ($endpoint !== '') {
            return str_starts_with($endpoint, 'http') ? $endpoint : 'https://'.$endpoint;
        }

        $region = $region !== '' ? $region : self::defaultRegion($driver);

        return match ($driver) {
            'oss' => 'https://oss-'.$region.'.aliyuncs.com',
            'cos' => 'https://cos.'.$region.'.myqcloud.com',
            default => null,
        };
    }
}
