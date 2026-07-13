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

    /**
     * @param  array<string, string>  $menuEntries
     */
    public function mergeMenuKeys(string $path, array $menuEntries): string
    {
        $existing = [];

        if (File::exists($path)) {
            $decoded = json_decode((string) File::get($path), true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        return $this->mergeMenuKeysIntoArray($existing, $menuEntries);
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, string>  $menuEntries
     */
    public function mergeMenuKeysIntoArray(array $existing, array $menuEntries): string
    {
        $menu = is_array($existing['menu'] ?? null) ? $existing['menu'] : [];
        $existing['menu'] = array_replace($menu, $menuEntries);

        $encoded = json_encode(
            $existing,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return ($encoded !== false ? $encoded : '{}')."\n";
    }

    /**
     * @param  array<string, string>  $menuEntries
     */
    public function mergeMenuKeysIntoContent(string $jsonContent, array $menuEntries): string
    {
        $decoded = json_decode($jsonContent, true);

        return $this->mergeMenuKeysIntoArray(is_array($decoded) ? $decoded : [], $menuEntries);
    }

    /**
     * @param  array<string, mixed>  $entityData
     */
    public function encodeEntityFile(array $entityData): string
    {
        $encoded = json_encode(
            $entityData,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return ($encoded !== false ? $encoded : '{}')."\n";
    }

    /**
     * @param  list<string>  $menuKeys
     */
    public function removeMenuKeys(string $path, array $menuKeys): void
    {
        if (! File::exists($path)) {
            return;
        }

        $decoded = json_decode((string) File::get($path), true);
        if (! is_array($decoded) || ! is_array($decoded['menu'] ?? null)) {
            return;
        }

        foreach ($menuKeys as $key) {
            unset($decoded['menu'][$key]);
        }

        $encoded = json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        if ($encoded !== false) {
            File::put($path, $encoded."\n");
        }
    }
}
