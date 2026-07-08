<?php

namespace Modules\System\App\Services;

use App\Foundation\Plugin\Plugin;
use App\Foundation\Plugin\PluginConfigRepository;
use App\Foundation\Plugin\PluginLifecycle;
use App\Foundation\Plugin\PluginManager;

class PluginService
{
    public function __construct(
        protected PluginManager $pluginManager,
        protected PluginLifecycle $pluginLifecycle,
        protected PluginConfigRepository $pluginConfigRepository,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->pluginManager->allFromFilesystem()
            ->map(fn (Plugin $plugin) => $this->formatPlugin($plugin))
            ->values()
            ->all();
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
            'version' => $plugin->version(),
            'description' => $plugin->description(),
            'author' => $plugin->author(),
            'directory' => $plugin->directoryName(),
            'namespace' => $plugin->namespace(),
            'status' => $plugin->status(),
            'status_label' => $plugin->statusLabel(),
            'enabled' => $plugin->isEnabled(),
            'installed' => $plugin->isInstalled(),
            'providers' => $plugin->providers(),
            'providers_count' => count($plugin->providers()),
            'pages' => $plugin->pages(),
            'installed_at' => $plugin->record()['installed_at'] ?? null,
            'enabled_at' => $plugin->record()['enabled_at'] ?? null,
        ];
    }

    protected function relativeManifestPath(string $manifestPath): string
    {
        $root = dirname(base_path());

        return str_replace('\\', '/', str_replace($root.DIRECTORY_SEPARATOR, '', $manifestPath));
    }
}
