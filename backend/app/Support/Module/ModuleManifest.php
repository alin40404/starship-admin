<?php

namespace App\Support\Module;

class ModuleManifest
{
    public static function load(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        if (! is_array($data)) {
            return null;
        }

        return $data;
    }
}
