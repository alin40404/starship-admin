<?php

namespace Starship\Core\Plugin;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Starship\Core\Models\Plugin as PluginModel;

class PluginManager
{
    public function __construct(
        protected ?string $pluginsPath = null,
    ) {}

    public function pluginsPath(): string
    {
        return $this->pluginsPath ?? (string) config('starship.plugins_path', dirname(base_path()).'/plugins');
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

        $records = $this->pluginRecordsBySlug();

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
     * @return Collection<string, PluginModel>
     */
    protected function pluginRecordsBySlug(): Collection
    {
        try {
            $connection = (string) config('database.default');
            $driver = (string) config("database.connections.{$connection}.driver", $connection);
            if ($driver !== '' && ! in_array($driver, \PDO::getAvailableDrivers(), true)) {
                return collect();
            }

            if (! Schema::hasTable('plugins')) {
                return collect();
            }

            return PluginModel::query()->get()->keyBy(static fn (PluginModel $plugin) => $plugin->slug);
        } catch (\Throwable) {
            return collect();
        }
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
