<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Starship\Core\Plugin\PluginAssetLinker;
use Throwable;

class PluginLinkCommand extends Command
{
    protected $signature = 'starship:plugin:link
                            {plugin? : 插件 slug / 目录名}
                            {--all : 为所有含 assets/ 的插件创建软链接}';

    protected $description = '将 public/plugins/{slug}/assets 软链接到插件 assets/，供 Web 服务器按静态文件访问';

    public function handle(PluginAssetLinker $linker): int
    {
        $force = true;

        try {
            if ($this->option('all')) {
                $results = $linker->linkAll($force);
                if ($results === []) {
                    $this->components->warn('没有可链接的插件 assets');

                    return self::SUCCESS;
                }
                $this->components->info('插件资源软链接已创建');
                foreach ($results as $result) {
                    $this->components->twoColumnDetail($result['slug'], $result['target']);
                }

                return self::SUCCESS;
            }

            $name = (string) ($this->argument('plugin') ?? '');
            if ($name === '') {
                $this->components->error('请指定插件 slug，或使用 --all');

                return self::FAILURE;
            }

            $result = $linker->link($name, $force);
            $this->components->info('插件资源软链接已创建');
            $this->components->twoColumnDetail('Slug', $result['slug']);
            $this->components->twoColumnDetail('链接', $result['target']);
            $this->components->twoColumnDetail('指向', $result['source']);
            $this->newLine();
            $this->components->bulletList([
                'URL：/plugins/{slug}/assets/...',
                '改源码 plugins/.../assets 即时生效，无需再复制',
            ]);

            return self::SUCCESS;
        } catch (InvalidArgumentException|Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
