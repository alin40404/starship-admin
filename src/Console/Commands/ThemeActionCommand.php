<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Starship\Core\Theme\ThemeManager;

abstract class ThemeActionCommand extends Command
{
    protected function resolveSlug(ThemeManager $themeManager): string
    {
        $name = (string) $this->argument('theme');
        $theme = $themeManager->findByName($name) ?? $themeManager->findBySlug($name);

        if ($theme === null) {
            throw new InvalidArgumentException("主题不存在：{$name}");
        }

        return $theme->slug();
    }
}
