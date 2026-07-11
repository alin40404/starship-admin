<?php

namespace Modules\System\App\Services;

use Starship\Core\Module\Module;
use Starship\Core\Module\ModuleManifest;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;

class DevModuleService
{
    public function modulesPath(): string
    {
        return base_path('../modules');
    }

    /**
     * @return list<array{
     *   name: string,
     *   title: string,
     *   version: string|null,
     *   enabled: bool,
     *   directory: string,
     *   providers: list<string>,
     *   seeders: list<string>,
     *   providers_count: int,
     *   seeders_count: int,
     * }>
     */
    public function list(): array
    {
        $items = [];

        foreach (File::directories($this->modulesPath()) as $directory) {
            $manifestPath = $directory.DIRECTORY_SEPARATOR.'module.json';
            $data = ModuleManifest::load($manifestPath);
            if (! $data) {
                continue;
            }

            $items[] = $this->formatModule(new Module($data), $data, $directory);
        }

        usort($items, static fn (array $left, array $right): int => strcmp($left['name'], $right['name']));

        return $items;
    }

    /**
     * @return array{
     *   name: string,
     *   title: string,
     *   version: string|null,
     *   enabled: bool,
     *   directory: string,
     *   providers: list<string>,
     *   seeders: list<string>,
     *   providers_count: int,
     *   seeders_count: int,
     *   manifest: array<string, mixed>,
     *   manifest_path: string,
     * }
     */
    public function show(string $name): array
    {
        $found = $this->findModuleDirectory($name);
        $data = ModuleManifest::load($found['manifest_path']);
        if (! $data) {
            throw new InvalidArgumentException("模块 {$name} 的 module.json 无效");
        }

        return array_merge(
            $this->formatModule(new Module($data), $data, $found['directory']),
            [
                'manifest' => $data,
                'manifest_path' => $this->relativeManifestPath($found['manifest_path']),
            ],
        );
    }

    /**
     * @return array{
     *   name: string,
     *   title: string,
     *   version: string|null,
     *   enabled: bool,
     *   directory: string,
     *   providers: list<string>,
     *   seeders: list<string>,
     *   providers_count: int,
     *   seeders_count: int,
     * }
     */
    public function updateEnabled(string $name, bool $enabled): array
    {
        if (strtolower($name) === 'system' && ! $enabled) {
            throw new InvalidArgumentException('不能禁用 System 模块');
        }

        $found = $this->findModuleDirectory($name);
        $data = ModuleManifest::load($found['manifest_path']);
        if (! $data) {
            throw new InvalidArgumentException("模块 {$name} 的 module.json 无效");
        }

        $data['enabled'] = $enabled;
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        if ($json === false || file_put_contents($found['manifest_path'], $json.PHP_EOL) === false) {
            throw new RuntimeException('写入 module.json 失败');
        }

        return $this->formatModule(new Module($data), $data, $found['directory']);
    }

    /**
     * @return array{directory: string, manifest_path: string}
     */
    private function findModuleDirectory(string $name): array
    {
        $slug = strtolower($name);

        foreach (File::directories($this->modulesPath()) as $directory) {
            $manifestPath = $directory.DIRECTORY_SEPARATOR.'module.json';
            $data = ModuleManifest::load($manifestPath);
            if (! $data) {
                continue;
            }

            if (strtolower((string) ($data['name'] ?? '')) === $slug) {
                return [
                    'directory' => $directory,
                    'manifest_path' => $manifestPath,
                ];
            }
        }

        throw new InvalidArgumentException("模块不存在：{$name}");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *   name: string,
     *   title: string,
     *   version: string|null,
     *   enabled: bool,
     *   directory: string,
     *   providers: list<string>,
     *   seeders: list<string>,
     *   providers_count: int,
     *   seeders_count: int,
     * }
     */
    private function formatModule(Module $module, array $data, string $directory): array
    {
        return [
            'name' => $module->name(),
            'title' => $module->title(),
            'version' => $module->version(),
            'enabled' => $module->enabled(),
            'directory' => basename(str_replace('\\', '/', $directory)),
            'providers' => $module->providers(),
            'seeders' => $module->seeders(),
            'providers_count' => count($module->providers()),
            'seeders_count' => count($module->seeders()),
        ];
    }

    private function relativeManifestPath(string $manifestPath): string
    {
        $root = dirname(base_path());

        return str_replace('\\', '/', str_replace($root.DIRECTORY_SEPARATOR, '', $manifestPath));
    }
}
