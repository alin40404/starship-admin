<?php

namespace Starship\Core\Plugin;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use RuntimeException;
use Starship\Core\Models\Plugin as PluginModel;
use Starship\Core\Module\ModuleManager;

class PluginLifecycle
{
    public function __construct(
        protected PluginManager $pluginManager,
    ) {}

    /**
     * @return array{discovered: int, created: int, updated: int}
     */
    public function discover(): array
    {
        $created = 0;
        $updated = 0;

        foreach ($this->pluginManager->allFromFilesystem() as $plugin) {
            if ($plugin->slug() === '') {
                continue;
            }

            $result = $this->upsertRecord($plugin);
            if ($result === 'created') {
                $created++;
            } else {
                $updated++;
            }
        }

        return [
            'discovered' => $this->pluginManager->allFromFilesystem()->count(),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    public function install(string $slug): Plugin
    {
        $plugin = $this->requirePlugin($slug);
        $this->assertRequirements($plugin);

        $record = $this->ensureDiscovered($slug);
        if ($record->status === Plugin::STATUS_ENABLED) {
            throw new InvalidArgumentException("插件 {$slug} 已启用");
        }

        if ($record->status === Plugin::STATUS_INSTALLED || $record->status === Plugin::STATUS_DISABLED) {
            return $this->refreshPlugin($slug);
        }

        if ($plugin->hasMigrations()) {
            $this->runMigrations($plugin);
        }

        $record->update([
            'status' => Plugin::STATUS_INSTALLED,
            'installed_at' => now(),
        ]);

        return $this->refreshPlugin($slug);
    }

    public function enable(string $slug): Plugin
    {
        $plugin = $this->requirePlugin($slug);
        $record = $this->ensureDiscovered($slug);

        if ($record->status < Plugin::STATUS_INSTALLED) {
            $plugin = $this->install($slug);
            $record = $this->requireRecord($slug);
        }

        $record->update([
            'status' => Plugin::STATUS_ENABLED,
            'enabled_at' => now(),
        ]);

        return $this->refreshPlugin($slug);
    }

    public function disable(string $slug): Plugin
    {
        $record = $this->requireRecord($slug);

        if ($record->status !== Plugin::STATUS_ENABLED) {
            throw new InvalidArgumentException("插件 {$slug} 当前未启用");
        }

        $record->update([
            'status' => Plugin::STATUS_DISABLED,
        ]);

        return $this->refreshPlugin($slug);
    }

    public function uninstall(string $slug): Plugin
    {
        $record = $this->requireRecord($slug);

        if ($record->status === Plugin::STATUS_DISCOVERED) {
            throw new InvalidArgumentException("插件 {$slug} 尚未安装");
        }

        if ($record->status === Plugin::STATUS_ENABLED) {
            throw new InvalidArgumentException("请先禁用插件后再卸载：{$slug}");
        }

        $record->update([
            'status' => Plugin::STATUS_DISCOVERED,
            'installed_at' => null,
            'enabled_at' => null,
        ]);

        return $this->refreshPlugin($slug);
    }

    protected function requirePlugin(string $slug): Plugin
    {
        $plugin = $this->pluginManager->findBySlug($slug);
        if ($plugin === null) {
            throw new InvalidArgumentException("插件不存在：{$slug}");
        }

        if ($plugin->slug() === '') {
            throw new InvalidArgumentException('plugin.json 缺少 slug');
        }

        return $plugin;
    }

    protected function requireRecord(string $slug): PluginModel
    {
        $record = PluginModel::query()->where('slug', $slug)->first();
        if ($record === null) {
            throw new InvalidArgumentException("插件尚未发现，请先执行 discover：{$slug}");
        }

        return $record;
    }

    /**
     * 安装/启用时若尚未入库，自动从文件系统写入发现记录。
     */
    protected function ensureDiscovered(string $slug): PluginModel
    {
        $record = PluginModel::query()->where('slug', $slug)->first();
        if ($record !== null) {
            return $record;
        }

        $this->upsertRecord($this->requirePlugin($slug));

        return $this->requireRecord($slug);
    }

    /**
     * @return 'created'|'updated'
     */
    protected function upsertRecord(Plugin $plugin): string
    {
        $record = PluginModel::query()->where('slug', $plugin->slug())->first();

        if ($record === null) {
            PluginModel::query()->create([
                'name' => $plugin->name(),
                'slug' => $plugin->slug(),
                'type' => $plugin->type(),
                'version' => $plugin->version(),
                'description' => $plugin->description(),
                'author' => $plugin->author(),
                'namespace' => $plugin->namespace(),
                'path' => $plugin->directoryName(),
                'status' => Plugin::STATUS_DISCOVERED,
                'config' => $plugin->manifest(),
            ]);

            return 'created';
        }

        $record->fill([
            'name' => $plugin->name(),
            'type' => $plugin->type(),
            'version' => $plugin->version(),
            'description' => $plugin->description(),
            'author' => $plugin->author(),
            'namespace' => $plugin->namespace(),
            'path' => $plugin->directoryName(),
            'config' => $plugin->manifest(),
        ])->save();

        return 'updated';
    }

    protected function refreshPlugin(string $slug): Plugin
    {
        $plugin = $this->pluginManager->findBySlug($slug);
        if ($plugin === null) {
            throw new RuntimeException("插件不存在：{$slug}");
        }

        return $plugin;
    }

    protected function assertRequirements(Plugin $plugin): void
    {
        $require = $plugin->require();
        $php = (string) ($require['php'] ?? '');
        if ($php !== '' && ! $this->versionSatisfied(PHP_VERSION, $php)) {
            throw new InvalidArgumentException("PHP 版本不满足要求：{$php}");
        }

        $modules = Arr::wrap($require['modules'] ?? []);
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

    protected function runMigrations(Plugin $plugin): void
    {
        $migrationPath = $plugin->directory().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        if (! is_dir($migrationPath)) {
            return;
        }

        Artisan::call('migrate', [
            '--path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $migrationPath),
            '--force' => true,
        ]);
    }

    protected function versionSatisfied(string $current, string $constraint): bool
    {
        $constraint = trim($constraint);
        if (str_starts_with($constraint, '>=')) {
            return version_compare($current, substr($constraint, 2), '>=');
        }

        return version_compare($current, $constraint, '>=');
    }
}
