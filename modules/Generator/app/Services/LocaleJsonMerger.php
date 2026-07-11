<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\File;

final class LocaleJsonMerger
{
    /**
     * @param  array<string, mixed>  $entityData
     */
    public function mergeEntity(string $path, string $entityKey, array $entityData): string
    {
        $existing = [];

        if (File::exists($path)) {
            $decoded = json_decode((string) File::get($path), true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $currentEntity = is_array($existing[$entityKey] ?? null) ? $existing[$entityKey] : [];
        $existing[$entityKey] = array_replace_recursive($currentEntity, $entityData);

        $encoded = json_encode(
            $existing,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return ($encoded !== false ? $encoded : '{}')."\n";
    }
}
