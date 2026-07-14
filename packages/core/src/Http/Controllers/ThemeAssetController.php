<?php

namespace Starship\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Starship\Core\Support\StaticAssetResponse;
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

        return StaticAssetResponse::file($absolute);
    }
}
