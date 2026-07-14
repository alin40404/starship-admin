<?php

namespace Starship\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Starship\Core\Theme\ThemeManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThemeAssetController extends Controller
{
    public function __invoke(ThemeManager $themes, string $slug, string $path = ''): BinaryFileResponse
    {
        $theme = $themes->findBySlug($slug) ?? $themes->findByName($slug);
        if ($theme === null) {
            abort(404);
        }

        $absolute = $theme->resolveAssetPath($path);
        if ($absolute === null) {
            abort(404);
        }

        return response()->file($absolute, [
            'Content-Type' => $this->mimeTypeFor($absolute),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    protected function mimeTypeFor(string $absolutePath): string
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
