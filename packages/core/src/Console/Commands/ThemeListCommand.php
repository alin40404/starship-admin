<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Starship\Core\Theme\Theme;
use Starship\Core\Theme\ThemeManager;

class ThemeListCommand extends Command
{
    protected $signature = 'starship:theme:list';

    protected $description = '列出所有主题及状态';

    public function handle(ThemeManager $themeManager): int
    {
        $themes = $themeManager->allFromFilesystem();

        if ($themes->isEmpty()) {
            $this->warn('未发现任何主题');

            return self::SUCCESS;
        }

        $rows = $themes->map(static fn (Theme $theme) => [
            $theme->slug(),
            $theme->title(),
            $theme->type(),
            $theme->version(),
            $theme->statusLabel(),
            $theme->directoryName(),
        ])->all();

        $this->table(['Slug', 'Title', 'Type', 'Version', 'Status', 'Directory'], $rows);

        return self::SUCCESS;
    }
}
