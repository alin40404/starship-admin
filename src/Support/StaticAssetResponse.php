<?php

namespace Starship\Core\Support;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Theme / Plugin 静态资源回落响应（MIME + Cache-Control）。
 */
class StaticAssetResponse
{
    public static function file(string $absolutePath): BinaryFileResponse
    {
        return response()->file($absolutePath, [
            'Content-Type' => self::mimeTypeFor($absolutePath),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public static function mimeTypeFor(string $absolutePath): string
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'css' => 'text/css; charset=UTF-8',
            'js', 'mjs' => 'text/javascript; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'json', 'map' => 'application/json',
            default => 'application/octet-stream',
        };
    }
}
