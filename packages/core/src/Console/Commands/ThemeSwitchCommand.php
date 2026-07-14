<?php

namespace Starship\Core\Console\Commands;

use InvalidArgumentException;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeManager;

class ThemeSwitchCommand extends ThemeActionCommand
{
    protected $signature = 'starship:theme:switch {theme : 主题目录名或 slug} {--type= : 主题类型，默认取 theme.json 的 type}';

    protected $description = '切换当前主题（同 type 互斥）';

    public function handle(ThemeManager $themeManager, ThemeLifecycle $themeLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($themeManager);
            $type = $this->option('type');
            $theme = $themeLifecycle->switch($slug, is_string($type) && $type !== '' ? $type : null);
            $this->info("已切换当前 {$theme->type()} 主题为 {$slug}");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
