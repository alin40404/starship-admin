<?php

namespace Modules\Generator\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Generator\App\Services\CodegenService;
use Modules\Generator\App\Support\ModulePathResolver;

class GeneratorCrudCommand extends Command
{
    protected $signature = 'starship:generator:crud
                            {table : 物理表名，如 sa_article 或 article}
                            {--module= : 目标模块，如 system（大小写不敏感）}
                            {--class= : 类名覆盖}
                            {--business= : 业务中文名}
                            {--route-group= : 前端 views 分组}
                            {--api-prefix= : API 路径段}
                            {--route-path= : 前端路由}
                            {--permission-prefix= : 权限前缀}
                            {--menu-parent= : 菜单父级路由}
                            {--preview : 仅预览，不写入文件}
                            {--force : 覆盖已存在文件 / 删除时跳过生成标记校验}
                            {--skip-menu : 跳过菜单/权限同步}
                            {--delete : 删除模式，移除 starship:generator:crud 生成的代码}
                            {--drop-table : 删除模式下同时 drop 物理表}';

    protected $description = '根据数据表生成或删除 CRUD 代码';

    public function handle(CodegenService $codegenService, ModulePathResolver $modulePathResolver): int
    {
        $table = (string) $this->argument('table');

        $available = $modulePathResolver->availableModules();
        $module = $codegenService->resolveCliModuleOption($table, $this->option('module'));
        if (! $modulePathResolver->moduleExists($module)) {
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
            'drop_table' => (bool) $this->option('drop-table'),
            'source' => 'cli',
            'operator_name' => 'cli',
        ];

        if ($this->option('delete')) {
            return $this->handleDelete($codegenService, $table, $options);
        }

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

        try {
            $result = $codegenService->generate($table, $options);
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

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

    /**
     * @param  array<string, mixed>  $options
     */
    protected function handleDelete(CodegenService $codegenService, string $table, array $options): int
    {
        try {
            if ($this->option('preview')) {
                return $this->renderDeletePreview($codegenService, $table, $options);
            }

            return $this->executeDelete($codegenService, $table, $options);
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function renderDeletePreview(CodegenService $codegenService, string $table, array $options): int
    {
        $result = $codegenService->previewDelete($table, $options);
        $this->info('删除预览：以下路径将被处理');
        foreach ($result['paths'] as $path) {
            $exists = is_file($path) ? ' [存在]' : ' [不存在]';
            $this->line('- '.$path.$exists);
        }

        if ($result['sync_menu']) {
            $this->line('- 菜单/权限：'.$result['config']->route_path.' / '.$result['config']->permission_prefix);
        }

        if ($result['drop_table']) {
            $this->line('- 物理表：'.$result['config']->table_name);
        }

        if (! $result['persisted']) {
            $this->warn('未找到 generator_tables 配置，将按当前参数推断路径（不会删除生成器 DB 配置）');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function executeDelete(CodegenService $codegenService, string $table, array $options): int
    {
        $preview = $codegenService->previewDelete($table, $options);

        $canDeleteMenu = (bool) ($options['sync_menu'] ?? true);
        $hasFileWork = $preview['existing'] !== [];
        $hasOtherWork = $preview['drop_table'] || $preview['persisted'] || $canDeleteMenu;

        if (! $hasFileWork && ! $hasOtherWork) {
            $this->warn('没有找到可删除的内容。');

            return self::SUCCESS;
        }

        if ($hasFileWork) {
            $this->warn('删除模式：以下文件将被删除');
            foreach ($preview['existing'] as $path) {
                $this->line('- '.$path);
            }
        } else {
            $this->warn('删除模式：未找到生成文件，将继续清理菜单/配置');
        }

        if ($options['sync_menu']) {
            $this->line('- 菜单/权限：'.$preview['config']->route_path.' / '.$preview['config']->permission_prefix);
        }

        if ($options['drop_table']) {
            $this->line('- 物理表：'.$preview['config']->table_name);
        }

        if (! $options['force'] && ! $this->confirm('确认删除？', false)) {
            $this->info('已取消');

            return self::SUCCESS;
        }

        $result = $codegenService->delete($table, $options);

        foreach ($result['deleted'] ?? [] as $path) {
            $this->info("[deleted] {$path}");
        }

        foreach ($result['skipped'] ?? [] as $item) {
            $this->warn("[skipped:{$item['reason']}] {$item['path']}");
        }

        foreach ($result['menu'] ?? [] as $item) {
            $this->line("[menu:{$item['action']}] {$item['path']}");
        }

        if ($result['table_dropped'] ?? false) {
            $this->info('[table] 已删除物理表');
        }

        if ($result['config_deleted'] ?? false) {
            $this->info('[config] 已删除 generator_tables 配置');
        }

        $this->newLine();
        $this->info('删除完成：'.$table);

        return self::SUCCESS;
    }
}
