<?php

namespace Modules\System\App\Services;

use Starship\Core\Plugin\Plugin;
use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginLifecycle;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Plugin\PluginTypeCatalog;

class PluginService
{
    public function __construct(
        protected PluginManager $pluginManager,
        protected PluginLifecycle $pluginLifecycle,
        protected PluginConfigRepository $pluginConfigRepository,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, types: list<array{value: string, label: string}>}
     */
    public function list(): array
    {
        $items = $this->pluginManager->allFromFilesystem()
            ->map(fn (Plugin $plugin) => $this->formatPlugin($plugin))
            ->values()
            ->all();

        return [
            'items' => $items,
            'types' => $this->resolveTypeOptions($items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $slug): array
    {
        $plugin = $this->requirePlugin($slug);

        return array_merge($this->formatPlugin($plugin), [
            'manifest' => $plugin->manifest(),
            'manifest_path' => $this->relativeManifestPath($plugin->manifestPath()),
            'pages' => $plugin->pages(),
            'config' => $this->pluginConfigRepository->all($slug, masked: true),
            'config_schema' => $this->pluginConfigRepository->schemaKeys($slug),
        ]);
    }

    /**
     * @return array{discovered: int, created: int, updated: int}
     */
    public function discover(): array
    {
        return $this->pluginLifecycle->discover();
    }

    /**
     * @return array<string, mixed>
     */
    public function install(string $slug): array
    {
        $plugin = $this->pluginLifecycle->install($slug);

        return $this->formatPlugin($plugin);
    }

    /**
     * @return array<string, mixed>
     */
    public function enable(string $slug): array
    {
        $plugin = $this->pluginLifecycle->enable($slug);

        return $this->formatPlugin($plugin);
    }

    /**
     * @return array<string, mixed>
     */
    public function disable(string $slug): array
    {
        $plugin = $this->pluginLifecycle->disable($slug);

        return $this->formatPlugin($plugin);
    }

    /**
     * @return array<string, mixed>
     */
    public function uninstall(string $slug): array
    {
        $plugin = $this->pluginLifecycle->uninstall($slug);

        return $this->formatPlugin($plugin);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(string $slug): array
    {
        $this->requirePlugin($slug);

        return $this->pluginConfigRepository->all($slug, masked: true);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function updateConfig(string $slug, array $values): array
    {
        $this->requirePlugin($slug);

        return $this->pluginConfigRepository->save($slug, $values);
    }

    protected function requirePlugin(string $slug): Plugin
    {
        $plugin = $this->pluginManager->findBySlug($slug);
        if ($plugin === null) {
            throw new \InvalidArgumentException("插件不存在：{$slug}");
        }

        return $plugin;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatPlugin(Plugin $plugin): array
    {
        return [
            'name' => $plugin->name(),
            'slug' => $plugin->slug(),
            'title' => $plugin->title(),
            'type' => $plugin->type(),
            'type_label' => $plugin->typeLabel(),
            'version' => $plugin->version(),
            'description' => $plugin->description(),
            'author' => $plugin->author(),
            'directory' => $plugin->directoryName(),
            'namespace' => $plugin->namespace(),
            'status' => $plugin->status(),
            'status_label' => $plugin->statusLabel(),
            'enabled' => $plugin->isEnabled(),
            'installed' => $plugin->isInstalled(),
            'has_config' => $plugin->configPath() !== null,
            'providers' => $plugin->providers(),
            'providers_count' => count($plugin->providers()),
            'pages' => $plugin->pages(),
            'cover' => $plugin->coverRelativePath(),
            'cover_url' => $plugin->coverAbsolutePath() !== null
                ? '/dev/plugins/'.$plugin->slug().'/cover'
                : null,
            'installed_at' => $plugin->record()['installed_at'] ?? null,
            'enabled_at' => $plugin->record()['enabled_at'] ?? null,
        ];
    }

    public function coverAbsolutePath(string $slug): string
    {
        $plugin = $this->requirePlugin($slug);
        $path = $plugin->coverAbsolutePath();
        if ($path === null) {
            throw new \InvalidArgumentException("插件封面不存在：{$slug}");
        }

        return $path;
    }

    protected function relativeManifestPath(string $manifestPath): string
    {
        $root = dirname(base_path());

        return str_replace('\\', '/', str_replace($root.DIRECTORY_SEPARATOR, '', $manifestPath));
    }

    /**
     * 侧栏类型：完整目录（文案由后端语言包提供）+ 扫描到的未知 type。
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array{value: string, label: string}>
     */
    protected function resolveTypeOptions(array $items): array
    {
        $result = PluginTypeCatalog::options();
        $known = array_fill_keys(PluginTypeCatalog::keys(), true);

        foreach ($items as $item) {
            $type = is_string($item['type'] ?? null) ? trim((string) $item['type']) : '';
            if ($type === '' || isset($known[strtolower($type)])) {
                continue;
            }

            $known[strtolower($type)] = true;
            $result[] = [
                'value' => $type,
                'label' => PluginTypeCatalog::label($type),
            ];
        }

        return $result;
    }
}
