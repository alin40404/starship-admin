<?php

namespace Starship\Core\Theme;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use RuntimeException;
use Starship\Core\Models\Theme as ThemeModel;
use Starship\Core\Module\ModuleManager;

class ThemeLifecycle
{
    public function __construct(
        protected ThemeManager $themeManager,
    ) {}

    /**
     * @return array{discovered: int, created: int, updated: int}
     */
    public function discover(): array
    {
        $created = 0;
        $updated = 0;

        foreach ($this->themeManager->allFromFilesystem() as $theme) {
            if ($theme->slug() === '') {
                continue;
            }

            $result = $this->upsertRecord($theme);
            if ($result === 'created') {
                $created++;
            } else {
                $updated++;
            }
        }

        return [
            'discovered' => $this->themeManager->allFromFilesystem()->count(),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    public function install(string $slug): Theme
    {
        $theme = $this->requireTheme($slug);
        $this->assertRequirements($theme);

        $record = $this->ensureDiscovered($slug);
        if ($record->status === Theme::STATUS_ENABLED) {
            throw new InvalidArgumentException("主题 {$slug} 已启用");
        }

        if ($record->status === Theme::STATUS_INSTALLED || $record->status === Theme::STATUS_DISABLED) {
            return $this->refreshTheme($slug);
        }

        $record->update([
            'status' => Theme::STATUS_INSTALLED,
            'installed_at' => now(),
        ]);

        return $this->refreshTheme($slug);
    }

    public function enable(string $slug): Theme
    {
        $this->requireTheme($slug);
        $record = $this->ensureDiscovered($slug);

        if ($record->status < Theme::STATUS_INSTALLED) {
            $this->install($slug);
            $record = $this->requireRecord($slug);
        }

        $record->update([
            'status' => Theme::STATUS_ENABLED,
            'enabled_at' => now(),
        ]);

        return $this->refreshTheme($slug);
    }

    /**
     * 同 type 仅允许一个 is_active；目标主题须已启用（未启用则先 enable）。
     */
    public function switch(string $slug, ?string $type = null): Theme
    {
        $theme = $this->requireTheme($slug);
        $type = $type !== null && $type !== '' ? $type : $theme->type();

        if ($theme->type() !== $type) {
            throw new InvalidArgumentException("主题 {$slug} 类型为 {$theme->type()}，无法切换为 {$type}");
        }

        $record = $this->ensureDiscovered($slug);
        if ($record->status !== Theme::STATUS_ENABLED) {
            $this->enable($slug);
            $record = $this->requireRecord($slug);
        }

        ThemeModel::query()
            ->where('type', $type)
            ->where('is_active', 1)
            ->update(['is_active' => 0]);

        $record->update([
            'is_active' => 1,
            'status' => Theme::STATUS_ENABLED,
            'enabled_at' => $record->enabled_at ?? now(),
        ]);

        return $this->refreshTheme($slug);
    }

    public function disable(string $slug): Theme
    {
        $record = $this->requireRecord($slug);

        if ($record->status !== Theme::STATUS_ENABLED) {
            throw new InvalidArgumentException("主题 {$slug} 当前未启用");
        }

        $record->update([
            'status' => Theme::STATUS_DISABLED,
            'is_active' => 0,
        ]);

        return $this->refreshTheme($slug);
    }

    public function uninstall(string $slug): Theme
    {
        $record = $this->requireRecord($slug);

        if ($record->status === Theme::STATUS_DISCOVERED) {
            throw new InvalidArgumentException("主题 {$slug} 尚未安装");
        }

        if ($record->status === Theme::STATUS_ENABLED) {
            throw new InvalidArgumentException("请先禁用主题后再卸载：{$slug}");
        }

        $record->update([
            'status' => Theme::STATUS_DISCOVERED,
            'is_active' => 0,
            'installed_at' => null,
            'enabled_at' => null,
        ]);

        return $this->refreshTheme($slug);
    }

    protected function requireTheme(string $slug): Theme
    {
        $theme = $this->themeManager->findBySlug($slug);
        if ($theme === null) {
            throw new InvalidArgumentException("主题不存在：{$slug}");
        }

        if ($theme->slug() === '') {
            throw new InvalidArgumentException('theme.json 缺少 slug');
        }

        return $theme;
    }

    protected function requireRecord(string $slug): ThemeModel
    {
        $record = ThemeModel::query()->where('slug', $slug)->first();
        if ($record === null) {
            throw new InvalidArgumentException("主题尚未发现，请先执行 discover：{$slug}");
        }

        return $record;
    }

    /**
     * 安装/启用/切换时若尚未入库，自动从文件系统写入发现记录。
     */
    protected function ensureDiscovered(string $slug): ThemeModel
    {
        $record = ThemeModel::query()->where('slug', $slug)->first();
        if ($record !== null) {
            return $record;
        }

        $this->upsertRecord($this->requireTheme($slug));

        return $this->requireRecord($slug);
    }

    /**
     * @return 'created'|'updated'
     */
    protected function upsertRecord(Theme $theme): string
    {
        $record = ThemeModel::query()->where('slug', $theme->slug())->first();

        if ($record === null) {
            ThemeModel::query()->create([
                'name' => $theme->name(),
                'slug' => $theme->slug(),
                'type' => $theme->type(),
                'version' => $theme->version(),
                'title' => $theme->title(),
                'description' => $theme->description(),
                'author' => $theme->author(),
                'path' => $theme->directoryName(),
                'status' => Theme::STATUS_DISCOVERED,
                'is_active' => 0,
                'config' => $theme->manifest(),
            ]);

            return 'created';
        }

        $record->fill([
            'name' => $theme->name(),
            'type' => $theme->type(),
            'version' => $theme->version(),
            'title' => $theme->title(),
            'description' => $theme->description(),
            'author' => $theme->author(),
            'path' => $theme->directoryName(),
            'config' => $theme->manifest(),
        ])->save();

        return 'updated';
    }

    protected function refreshTheme(string $slug): Theme
    {
        $theme = $this->themeManager->findBySlug($slug);
        if ($theme === null) {
            throw new RuntimeException("主题不存在：{$slug}");
        }

        return $theme;
    }

    protected function assertRequirements(Theme $theme): void
    {
        $modules = Arr::wrap($theme->require()['modules'] ?? []);
        $available = collect(app(ModuleManager::class)->enabled())
            ->map(static fn ($module) => $module->name())
            ->map(static fn (string $name) => strtolower($name))
            ->all();

        foreach ($modules as $module) {
            if (! is_string($module) || $module === '') {
                continue;
            }

            if (! in_array(strtolower($module), $available, true)) {
                throw new InvalidArgumentException("依赖模块未启用：{$module}");
            }
        }
    }
}
