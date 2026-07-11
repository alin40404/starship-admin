<?php

namespace Modules\System\App\Support;

use Illuminate\Support\Facades\Http;

/**
 * 由站点 Logo 生成 favicon 文件（写入 site.favicon 配置指向的本地路径）。
 */
final class SiteFaviconGenerator
{
    public const DEFAULT_PUBLIC_PATH = '/favicon.ico';

    private const FAVICON_SIZE = 32;

    public static function generateFromLogo(string $logoUrl, string $faviconPath = self::DEFAULT_PUBLIC_PATH): bool
    {
        $logoUrl = trim($logoUrl);
        $faviconPath = trim($faviconPath) ?: self::DEFAULT_PUBLIC_PATH;

        if ($logoUrl === '' || ! extension_loaded('gd')) {
            return false;
        }

        if (str_starts_with($faviconPath, 'http://') || str_starts_with($faviconPath, 'https://')) {
            return false;
        }

        $binary = self::loadImageBinary($logoUrl);
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

        $ok = false;
        foreach (self::resolveLocalTargets($faviconPath) as $target) {
            $directory = dirname($target);
            if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
                continue;
            }

            if (@imagepng($resized, $target)) {
                $ok = true;
            }
        }

        imagedestroy($resized);

        return $ok;
    }

    /**
     * @return list<string>
     */
    private static function resolveLocalTargets(string $publicPath): array
    {
        $path = '/'.ltrim(parse_url($publicPath, PHP_URL_PATH) ?? $publicPath, '/');
        $targets = [];

        if (str_starts_with($path, '/storage/')) {
            $relative = substr($path, strlen('/storage/'));
            $storageTarget = storage_path('app/public/'.ltrim($relative, '/'));
            $targets[] = $storageTarget;
            $publicTarget = public_path('storage/'.ltrim($relative, '/'));
            if ($publicTarget !== $storageTarget) {
                $targets[] = $publicTarget;
            }

            return array_values(array_unique($targets));
        }

        $backendPublic = public_path(ltrim($path, '/'));
        $targets[] = $backendPublic;

        $adminWebPublic = self::adminWebPublicPath(ltrim($path, '/'));
        if ($adminWebPublic !== null && $adminWebPublic !== $backendPublic) {
            $targets[] = $adminWebPublic;
        }

        return array_values(array_unique($targets));
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

        return $full;
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

            return is_file($publicPath) ? $publicPath : null;
        }

        return null;
    }
}
