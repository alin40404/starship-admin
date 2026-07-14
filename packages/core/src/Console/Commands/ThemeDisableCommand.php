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
            $this->components->info('主题已禁用');
            $this->components->twoColumnDetail('Slug', $slug);

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
