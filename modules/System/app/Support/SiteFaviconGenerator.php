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

    private const FAVICON_SIZE = 32;

    /**
     * @return string|null 可访问 URL，失败返回 null
     */
    public static function generateFromLogo(string $logoUrl): ?string
    {
        $logoUrl = trim($logoUrl);

        if ($logoUrl === '' || ! extension_loaded('gd')) {
            return null;
        }

        $binary = self::loadImageBinary($logoUrl);
        if ($binary === null) {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return null;
        }

        $resized = imagescale($image, self::FAVICON_SIZE, self::FAVICON_SIZE, IMG_BILINEAR_FIXED);
        imagedestroy($image);

        if ($resized === false) {
            return null;
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        ob_start();
        $written = @imagepng($resized);
        $pngData = ob_get_clean();
        imagedestroy($resized);

        if (! $written || ! is_string($pngData) || $pngData === '') {
            return null;
        }

        StorageConfigRegistrar::apply();
        $disk = StorageConfigRegistrar::defaultDisk();

        if (! Storage::disk($disk)->put(self::UPLOAD_RELATIVE_PATH, $pngData)) {
            return null;
        }

        return Storage::disk($disk)->url(self::UPLOAD_RELATIVE_PATH);
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
