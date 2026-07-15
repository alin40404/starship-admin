<?php

namespace Starship\Core\Console\Commands;

use InvalidArgumentException;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeManager;

class ThemeDeleteCommand extends ThemeActionCommand
{
    protected $signature = 'starship:theme:delete
                            {theme : 主题目录名或 slug}
                            {--force : 跳过确认}';

    protected $description = '删除未安装主题（磁盘目录 + 数据库记录；须先卸载；启用中/当前激活/受保护主题除外）';

    public function handle(ThemeManager $themeManager, ThemeLifecycle $themeLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($themeManager);
            if (! $this->option('force')
                && ! $this->confirm("确定删除主题 {$slug}？此操作不可恢复。", false)) {
                $this->components->warn('已取消');

                return self::SUCCESS;
            }
            $themeLifecycle->delete($slug);
            $this->components->info('主题已删除');
            $this->components->twoColumnDetail('Slug', $slug);

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
