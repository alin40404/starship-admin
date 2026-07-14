<?php

namespace Starship\Core\Theme;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Starship\Core\Models\Theme as ThemeModel;

class ThemeManager
{
    public function __construct(
        protected ?string $themesPath = null,
    ) {}

    public function themesPath(): string
    {
        return $this->themesPath ?? (string) config('starship.themes_path', dirname(base_path()).'/themes');
    }

    /**
     * @return Collection<int, Theme>
     */
    public function allFromFilesystem(): Collection
    {
        $path = $this->themesPath();
        if (! File::isDirectory($path)) {
            return collect();
        }

        $records = $this->themeRecordsBySlug();

        $themes = [];

        foreach (File::directories($path) as $directory) {
            $manifestPath = $directory.DIRECTORY_SEPARATOR.'theme.json';
            $data = ThemeManifest::load($manifestPath);
            if (! $data) {
                continue;
            }

            $slug = (string) ($data['slug'] ?? '');
            $record = $slug !== '' ? $records->get($slug) : null;

            $themes[] = new Theme(
                $data,
                $directory,
                $record?->toArray(),
            );
        }

        return collect($themes)->sortBy(static fn (Theme $theme) => $theme->slug())->values();
    }

    /**
     * @return Collection<string, ThemeModel>
     */
    protected function themeRecordsBySlug(): Collection
    {
        try {
            $connection = (string) config('database.default');
            $driver = (string) config("database.connections.{$connection}.driver", $connection);
            if ($driver !== '' && ! in_array($driver, \PDO::getAvailableDrivers(), true)) {
                return collect();
            }

            if (! Schema::hasTable('themes')) {
                return collect();
            }

            return ThemeModel::query()->get()->keyBy(static fn (ThemeModel $theme) => $theme->slug);
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * @return Collection<int, Theme>
     */
    public function enabled(): Collection
    {
        return $this->allFromFilesystem()
            ->filter(static fn (Theme $theme) => $theme->isEnabled())
            ->values();
    }

    public function findBySlug(string $slug): ?Theme
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        return $this->allFromFilesystem()
            ->first(static fn (Theme $theme) => $theme->slug() === $slug);
    }

    public function findByName(string $name): ?Theme
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return $this->allFromFilesystem()
            ->first(static fn (Theme $theme) => strcasecmp($theme->name(), $name) === 0
                || strcasecmp($theme->directoryName(), $name) === 0
                || strcasecmp($theme->slug(), $name) === 0);
    }

    /**
     * 同 type 下当前激活主题；无 DB 激活记录时回退 default_theme / fallback_themes。
     */
    public function current(string $type = 'frontend'): ?Theme
    {
        $active = $this->allFromFilesystem()
            ->first(static fn (Theme $theme) => $theme->type() === $type && $theme->isActive());

        if ($active !== null) {
            return $active;
        }

        $fallback = (string) (config("starship.fallback_themes.{$type}")
            ?: config('starship.default_theme', 'default'));

        $theme = $this->findBySlug($fallback);
        if ($theme !== null && $theme->type() === $type) {
            return $theme;
        }

        return $this->allFromFilesystem()
            ->first(static fn (Theme $item) => $item->type() === $type);
    }
}
