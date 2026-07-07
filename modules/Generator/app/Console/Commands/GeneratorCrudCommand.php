<?php

namespace Modules\Generator\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Generator\App\Services\CodegenService;
use Modules\Generator\App\Support\GeneratorNaming;
use Modules\Generator\App\Support\ModulePathResolver;

class GeneratorCrudCommand extends Command
{
    protected $signature = 'generator:crud
                            {table : 物理表名，如 sa_system_demo_article}
                            {--module= : 目标模块，如 System}
                            {--class= : 类名覆盖}
                            {--business= : 业务中文名}
                            {--route-group= : 前端 views 分组}
                            {--api-prefix= : API 路径段}
                            {--route-path= : 前端路由}
                            {--permission-prefix= : 权限前缀}
                            {--menu-parent= : 菜单父级路由}
                            {--preview : 仅预览，不写入文件}
                            {--force : 覆盖已存在文件}
                            {--skip-menu : 跳过菜单/权限同步}';

    protected $description = '根据数据表生成 CRUD 代码（参考 fastadmin/buildadmin 命令行模式）';

    public function handle(CodegenService $codegenService, ModulePathResolver $modulePathResolver): int
    {
        $table = (string) $this->argument('table');
        $naming = GeneratorNaming::fromTableName($table, $this->option('module'));

        $available = $modulePathResolver->availableModules();
        $module = $this->option('module') ?? $naming['module_slug'];
        if (! in_array(strtolower((string) $module), array_map('strtolower', $available), true)) {
            $this->error('目标模块不存在或未启用：'.$module);
            $this->line('可用模块：'.implode(', ', $available));

            return self::FAILURE;
        }

        $options = [
            'module' => $module,
            'class' => $this->option('class'),
            'business' => $this->option('business'),
            'route_group' => $this->option('route-group'),
            'api_prefix' => $this->option('api-prefix'),
            'route_path' => $this->option('route-path'),
            'permission_prefix' => $this->option('permission-prefix'),
            'menu_parent_path' => $this->option('menu-parent'),
            'force' => (bool) $this->option('force'),
            'sync_menu' => ! $this->option('skip-menu'),
            'source' => 'cli',
            'operator_name' => 'cli',
        ];

        if ($this->option('preview')) {
            $result = $codegenService->preview($table, $options);
            $this->info('预览模式：以下文件将被生成');
            foreach ($result['files'] as $file) {
                $exists = is_file($file['path']) ? ' [已存在]' : '';
                $this->line('- '.$file['path'].$exists);
            }

            if ($result['conflicts'] !== []) {
                $this->newLine();
                $this->warn('冲突文件（默认将跳过，可用 --force 覆盖）：');
                foreach ($result['conflicts'] as $conflict) {
                    $this->line('- '.$conflict['path']);
                }
            }

            return self::SUCCESS;
        }

        $result = $codegenService->generate($table, $options);

        if (($result['conflicts'] ?? []) !== [] && ! $options['force']) {
            $this->warn('部分文件已存在，已跳过。使用 --force 可覆盖：');
            foreach ($result['conflicts'] as $conflict) {
                $this->line('- '.$conflict['path']);
            }
        }

        foreach ($result['written'] ?? [] as $item) {
            $this->info("[{$item['action']}] {$item['path']}");
        }

        if (isset($result['menu'])) {
            foreach ($result['menu'] as $item) {
                $this->line("[menu] {$item['path']} ({$item['action']})");
            }
        }

        $this->newLine();
        $this->info('生成完成：'.$table);

        return self::SUCCESS;
    }
}
