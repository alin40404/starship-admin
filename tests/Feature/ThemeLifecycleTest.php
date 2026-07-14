<?php

namespace Starship\Core\Tests\Feature;

use Starship\Core\Models\Theme as ThemeModel;
use Starship\Core\Tests\TestCase;
use Starship\Core\Theme\Theme;
use Starship\Core\Theme\ThemeManager;

class ThemeLifecycleTest extends TestCase
{
    protected function tearDown(): void
    {
        try {
            ThemeModel::query()->whereIn('slug', ['default', 'corporate'])->delete();
        } catch (\Throwable) {
            // 数据库未配置时忽略清理
        }

        parent::tearDown();
    }

    public function test_theme_manager_lists_seed_themes_from_filesystem(): void
    {
        $manager = $this->app->make(ThemeManager::class);

        $default = $manager->findBySlug('default');
        $this->assertNotNull($default);
        $this->assertSame('frontend', $default->type());
        $this->assertSame('默认主题', $default->title());

        $corporate = $manager->findBySlug('corporate');
        $this->assertNotNull($corporate);
    }

    public function test_theme_full_lifecycle_and_switch_mutex(): void
    {
        $this->migrateStarshipTables();

        $this->artisan('starship:theme:install', ['theme' => 'default'])->assertSuccessful();
        $this->artisan('starship:theme:enable', ['theme' => 'default'])->assertSuccessful();
        $this->artisan('starship:theme:switch', ['theme' => 'default'])->assertSuccessful();

        $default = ThemeModel::query()->where('slug', 'default')->first();
        $this->assertNotNull($default);
        $this->assertSame(Theme::STATUS_ENABLED, (int) $default->status);
        $this->assertSame(1, (int) $default->is_active);

        $this->artisan('starship:theme:switch', ['theme' => 'corporate'])->assertSuccessful();

        $default->refresh();
        $corporate = ThemeModel::query()->where('slug', 'corporate')->first();
        $this->assertNotNull($corporate);
        $this->assertSame(0, (int) $default->is_active);
        $this->assertSame(1, (int) $corporate->is_active);
        $this->assertSame(Theme::STATUS_ENABLED, (int) $corporate->status);

        $current = $this->app->make(ThemeManager::class)->current('frontend');
        $this->assertNotNull($current);
        $this->assertSame('corporate', $current->slug());

        $this->artisan('starship:theme:disable', ['theme' => 'corporate'])->assertSuccessful();
        $corporate->refresh();
        $this->assertSame(Theme::STATUS_DISABLED, (int) $corporate->status);
        $this->assertSame(0, (int) $corporate->is_active);

        $this->artisan('starship:theme:uninstall', ['theme' => 'corporate'])->assertSuccessful();
        $corporate->refresh();
        $this->assertSame(Theme::STATUS_DISCOVERED, (int) $corporate->status);
        $this->assertNull($corporate->installed_at);
    }

    public function test_cannot_uninstall_enabled_theme(): void
    {
        $this->migrateStarshipTables();

        $this->artisan('starship:theme:enable', ['theme' => 'default'])->assertSuccessful();
        $this->artisan('starship:theme:uninstall', ['theme' => 'default'])->assertFailed();
    }

    public function test_install_auto_discovers_missing_record(): void
    {
        $this->migrateStarshipTables();

        $this->assertNull(ThemeModel::query()->where('slug', 'default')->first());

        $this->artisan('starship:theme:install', ['theme' => 'default'])->assertSuccessful();

        $record = ThemeModel::query()->where('slug', 'default')->first();
        $this->assertNotNull($record);
        $this->assertSame(Theme::STATUS_INSTALLED, (int) $record->status);
        $this->assertNotNull($record->installed_at);
    }
}
