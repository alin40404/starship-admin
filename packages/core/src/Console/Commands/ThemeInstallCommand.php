<?php

namespace Starship\Core\Console\Commands;

use InvalidArgumentException;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeManager;

class ThemeInstallCommand extends ThemeActionCommand
{
    protected $signature = 'starship:theme:install {theme : 主题目录名或 slug}';

    protected $description = '安装本地主题';

    public function handle(ThemeManager $themeManager, ThemeLifecycle $themeLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($themeManager);
            $themeLifecycle->install($slug);
            $this->info("主题 {$slug} 安装成功");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
