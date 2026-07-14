<?php

namespace Starship\Core\Console\Commands;

use InvalidArgumentException;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeManager;

class ThemeDisableCommand extends ThemeActionCommand
{
    protected $signature = 'starship:theme:disable {theme : 主题目录名或 slug}';

    protected $description = '禁用本地主题';

    public function handle(ThemeManager $themeManager, ThemeLifecycle $themeLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($themeManager);
            $themeLifecycle->disable($slug);
            $this->info("主题 {$slug} 已禁用");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
