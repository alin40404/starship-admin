<?php

namespace Modules\Generator\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Generator\App\Services\TableConfigService;
use Modules\Generator\App\Support\GeneratorNaming;
use Modules\Generator\App\Support\ModulePathResolver;

class GeneratorImportCommand extends Command
{
    protected $signature = 'generator:import
                            {table : 物理表名}
                            {--module= : 目标模块，如 System}
                            {--class= : 类名覆盖}
                            {--business= : 业务中文名}
                            {--route-group= : 前端 views 分组，如 org}
                            {--api-prefix= : API 路径段}
                            {--route-path= : 前端路由}
                            {--permission-prefix= : 权限前缀}
                            {--menu-parent= : 菜单父级路由}';

    protected $description = '从数据库导入表结构到生成器配置';

    public function handle(TableConfigService $tableConfigService, ModulePathResolver $modulePathResolver): int
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

        $config = $tableConfigService->import($table, [
            'module' => $module,
            'class' => $this->option('class'),
            'business' => $this->option('business'),
            'route_group' => $this->option('route-group'),
            'api_prefix' => $this->option('api-prefix'),
            'route_path' => $this->option('route-path'),
            'permission_prefix' => $this->option('permission-prefix'),
            'menu_parent_path' => $this->option('menu-parent'),
        ]);

        $this->info("已导入配置 #{$config->id}：{$config->table_name}");
        $this->line("模块：{$config->module_slug}");
        $this->line("类名：{$config->class_name}");
        $this->line("路由：{$config->route_path}");
        $this->line("权限：{$config->permission_prefix}");
        $this->line('字段数：'.$config->columns->count());

        return self::SUCCESS;
    }
}
