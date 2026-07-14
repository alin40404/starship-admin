<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Starship\Core\Theme\ThemeAssetLinker;
use Starship\Core\Theme\ThemeManager;
use Throwable;

class ThemeLinkCommand extends Command
{
    protected $signature = 'starship:theme:link
                            {theme? : 主题 slug/目录名；默认当前前台主题}
                            {--all : 为所有含 assets/ 的主题创建软链接}';

    protected $description = '将 public/themes/{slug}/assets 软链接到主题 assets/，供 Web 服务器按静态文件访问';

    public function handle(ThemeManager $themes, ThemeAssetLinker $linker): int
    {
        $force = true;

        try {
            if ($this->option('all')) {
                $results = $linker->linkAll($force);
                if ($results === []) {
                    $this->components->warn('没有可链接的主题 assets');

                    return self::SUCCESS;
                }
                $this->components->info('主题资源软链接已创建');
                foreach ($results as $result) {
                    $this->components->twoColumnDetail($result['slug'], $result['target']);
                }

                return self::SUCCESS;
            }

            $name = (string) ($this->argument('theme') ?? '');
            if ($name === '') {
                $current = $themes->current('frontend');
                $name = $current?->slug()
                    ?: (string) config('starship.fallback_themes.frontend', config('starship.default_theme', 'default'));
            }

            $result = $linker->link($name, $force);
            $this->components->info('主题资源软链接已创建');
            $this->components->twoColumnDetail('Slug', $result['slug']);
            $this->components->twoColumnDetail('链接', $result['target']);
            $this->components->twoColumnDetail('指向', $result['source']);
            $this->newLine();
            $this->components->bulletList([
                'URL：/themes/{slug}/assets/...',
                '改源码 themes/.../assets 即时生效，无需再复制',
            ]);

            return self::SUCCESS;
        } catch (InvalidArgumentException|Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
