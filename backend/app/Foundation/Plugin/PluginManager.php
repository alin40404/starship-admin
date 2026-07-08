<?php

namespace App\Foundation\Plugin;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\System\App\Models\Plugin as PluginModel;

class PluginManager
{
    public function __construct(
        protected ?string $pluginsPath = null,
    ) {}

    public function pluginsPath(): string
    {
        return $this->pluginsPath ?? base_path('../plugins');
    }

    /**
     * @return Collection<int, Plugin>
     */
    public function allFromFilesystem(): Collection
    {
        $path = $this->pluginsPath();
        if (! File::isDirectory($path)) {
            return collect();
        }

        $records = Schema::hasTable('plugins')
            ? PluginModel::query()->get()->keyBy(static fn (PluginModel $plugin) => $plugin->slug)
            : collect();

        $plugins = [];

        foreach (File::directories($path) as $directory) {
            $manifestPath = $directory.DIRECTORY_SEPARATOR.'plugin.json';
            $data = PluginManifest::load($manifestPath);
            if (! $data) {
                continue;
            }

            $slug = (string) ($data['slug'] ?? '');
            $record = $slug !== '' ? $records->get($slug) : null;

            $plugins[] = new Plugin(
                $data,
                $directory,
                $record?->toArray(),
            );
        }

        return collect($plugins)->sortBy(static fn (Plugin $plugin) => $plugin->slug())->values();
    }

    /**
     * @return Collection<int, Plugin>
     */
    public function enabled(): Collection
    {
        return $this->allFromFilesystem()
            ->filter(static fn (Plugin $plugin) => $plugin->isEnabled())
            ->values();
    }

    public function findBySlug(string $slug): ?Plugin
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        return $this->allFromFilesystem()
            ->first(static fn (Plugin $plugin) => $plugin->slug() === $slug);
    }

    public function findByName(string $name): ?Plugin
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return $this->allFromFilesystem()
            ->first(static fn (Plugin $plugin) => strcasecmp($plugin->name(), $name) === 0
                || strcasecmp($plugin->directoryName(), $name) === 0);
    }
}
