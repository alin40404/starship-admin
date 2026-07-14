<?php

namespace Starship\Core\Console\Commands;

use InvalidArgumentException;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeManager;

class ThemeEnableCommand extends ThemeActionCommand
{
    protected $signature = 'starship:theme:enable {theme : 主题目录名或 slug}';

    protected $description = '启用本地主题';

    public function handle(ThemeManager $themeManager, ThemeLifecycle $themeLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($themeManager);
            $themeLifecycle->enable($slug);
            $this->info("主题 {$slug} 已启用");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
