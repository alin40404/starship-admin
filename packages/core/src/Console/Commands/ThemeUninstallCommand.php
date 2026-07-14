<?php

namespace Starship\Core\Console\Commands;

use InvalidArgumentException;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeManager;

class ThemeUninstallCommand extends ThemeActionCommand
{
    protected $signature = 'starship:theme:uninstall {theme : 主题目录名或 slug}';

    protected $description = '卸载本地主题（须先禁用）';

    public function handle(ThemeManager $themeManager, ThemeLifecycle $themeLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($themeManager);
            $themeLifecycle->uninstall($slug);
            $this->info("主题 {$slug} 已卸载");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
