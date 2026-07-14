<?php

namespace Starship\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Support\StaticAssetResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PluginAssetController extends Controller
{
    public function __invoke(PluginManager $plugins, string $slug, string $path = ''): BinaryFileResponse
    {
        $plugin = $plugins->findBySlug($slug) ?? $plugins->findByName($slug);
        if ($plugin === null) {
            abort(404);
        }

        $absolute = $plugin->resolveAssetPath($path);
        if ($absolute === null) {
            abort(404);
        }

        return StaticAssetResponse::file($absolute);
    }
}
