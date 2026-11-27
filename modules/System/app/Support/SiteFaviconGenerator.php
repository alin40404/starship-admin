<?php

namespace Modules\System\App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * 由站点 Logo 生成 favicon，写入上传目录（不覆盖 public/favicon.ico）。
 */
final class SiteFaviconGenerator
{
    public const DEFAULT_PUBLIC_PATH = '/favicon.ico';

    /** 固定写入 public disk 的相对路径 */
    public const UPLOAD_RELATIVE_PATH = 'uploads/favicon.ico';

    /** 本地 public 磁盘下的固定访问路径 */
    public const FIXED_STORAGE_PUBLIC_PATH = '/storage/uploads/favicon.ico';

    private const FAVICON_SIZE = 512;

    public static function isFixedUploadPath(string $url): bool
    {
        return self::resolveStorageRelativePath($url) === self::UPLOAD_RELATIVE_PATH;
    }

    /**
     * 固定上传路径对应的可访问 URL（与附件上传 file_url 格式一致）。
     */
    public static function fixedUploadPublicUrl(): string
    {
        StorageConfigRegistrar::apply();
        $disk = StorageConfigRegistrar::defaultDisk();

        return Storage::disk($disk)->url(self::UPLOAD_RELATIVE_PATH);
    }

    /**
     * 将任意站点图标归并到 uploads/favicon.ico，并返回固定路径 URL。
     *
     * @return string|null 已处于固定路径或归并成功时返回 URL，失败返回 null
     */
    public static function normalizeToFixedUploadPath(string $sourceUrl): ?string
    {
        $sourceUrl = trim($sourceUrl);
        if ($sourceUrl === '') {
            return null;
        }

        if (self::isFixedUploadPath($sourceUrl)) {
            return self::fixedUploadPublicUrl();
        }

        if (extension_loaded('gd') && self::writeResizedImageToFixedPath($sourceUrl)) {
            return self::fixedUploadPublicUrl();
        }

        if (self::copyRawBinaryToFixedPath($sourceUrl)) {
            return self::fixedUploadPublicUrl();
        }

        return null;
    }

    /**
     * @return string|null 固定路径，失败返回 null
     */
    public static function generateFromLogo(string $logoUrl): ?string
    {
        $logoUrl = trim($logoUrl);

        if ($logoUrl === '' || ! extension_loaded('gd')) {
            return null;
        }

        if (! self::writeResizedImageToFixedPath($logoUrl)) {
            return null;
        }

        return self::fixedUploadPublicUrl();
    }

    /**
     * 站点图标配置为空时，将默认 /favicon.ico 复制到 uploads/favicon.ico。
     */
    public static function restoreDefaultFaviconToUploads(): bool
    {
        $source = self::resolveDefaultFaviconLocalPath();
        if ($source === null) {
            return false;
        }

        $contents = @file_get_contents($source);
        if (! is_string($contents) || $contents === '') {
            return false;
        }

        StorageConfigRegistrar::apply();
        $disk = StorageConfigRegistrar::defaultDisk();

        return Storage::disk($disk)->put(self::UPLOAD_RELATIVE_PATH, $contents);
    }

    private static function writeResizedImageToFixedPath(string $sourceUrl): bool
    {
        $binary = self::loadImageBinary($sourceUrl);
        if ($binary === null) {
            return false;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return false;
        }

        $resized = imagescale($image, self::FAVICON_SIZE, self::FAVICON_SIZE, IMG_BILINEAR_FIXED);
        imagedestroy($image);

        if ($resized === false) {
            return false;
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        ob_start();
        $written = @imagepng($resized);
        $pngData = ob_get_clean();
        imagedestroy($resized);

        if (! $written || ! is_string($pngData) || $pngData === '') {
            return false;
        }

        StorageConfigRegistrar::apply();
        $disk = StorageConfigRegistrar::defaultDisk();

        return Storage::disk($disk)->put(self::UPLOAD_RELATIVE_PATH, $pngData);
    }

    private static function copyRawBinaryToFixedPath(string $sourceUrl): bool
    {
        $binary = self::loadImageBinary($sourceUrl);
        if ($binary === null) {
            return false;
        }

        StorageConfigRegistrar::apply();
        $disk = StorageConfigRegistrar::defaultDisk();

        return Storage::disk($disk)->put(self::UPLOAD_RELATIVE_PATH, $binary);
    }

    private static function resolveDefaultFaviconLocalPath(): ?string
    {
        foreach ([self::moduleResourcePath('assets/favicon.ico'), public_path('favicon.ico')] as $path) {
            if (is_string($path) && is_file($path) && filesize($path) > 0) {
                return $path;
            }
        }

        return null;
    }

    private static function moduleResourcePath(string $relativePath): ?string
    {
        $full = dirname(__DIR__, 2)
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return is_file($full) ? $full : null;
    }

    private static function resolveStorageRelativePath(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return null;
        }

        return ltrim(substr($path, strlen('/storage/')), '/');
    }

    private static function loadImageBinary(string $logoUrl): ?string
    {
        $localPath = self::resolveLogoLocalPath($logoUrl);
        if ($localPath !== null && is_file($localPath)) {
            $contents = @file_get_contents($localPath);

            return is_string($contents) && $contents !== '' ? $contents : null;
        }

        if (! str_starts_with($logoUrl, 'http://') && ! str_starts_with($logoUrl, 'https://')) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get($logoUrl);
            if (! $response->successful()) {
                return null;
            }
            $body = $response->body();

            return $body !== '' ? $body : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function resolveLogoLocalPath(string $logoUrl): ?string
    {
        $path = parse_url($logoUrl, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return is_file($logoUrl) ? $logoUrl : null;
        }

        if (str_starts_with($path, '/storage/')) {
            $relative = substr($path, strlen('/storage/'));
            $storagePath = storage_path('app/public/'.ltrim($relative, '/'));
            if (is_file($storagePath)) {
                return $storagePath;
            }

            $publicPath = public_path('storage/'.ltrim($relative, '/'));

            return is_file($publicPath) ? $publicPath : null;
        }

        if (str_starts_with($path, '/')) {
            $publicPath = public_path(ltrim($path, '/'));
            if (is_file($publicPath)) {
                return $publicPath;
            }

            return self::adminWebPublicPath(ltrim($path, '/'));
        }

        return null;
    }

    private static function adminWebPublicPath(string $relativePath): ?string
    {
        $root = dirname(base_path());
        $full = $root.DIRECTORY_SEPARATOR.'apps'
            .DIRECTORY_SEPARATOR.'admin'
            .DIRECTORY_SEPARATOR.'apps'
            .DIRECTORY_SEPARATOR.'web-antd'
            .DIRECTORY_SEPARATOR.'public'
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return is_file($full) ? $full : null;
    }
}
