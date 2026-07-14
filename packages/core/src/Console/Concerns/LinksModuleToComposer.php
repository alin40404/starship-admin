<?php

namespace Starship\Core\Console\Concerns;

use Illuminate\Support\Facades\Process;
use Starship\Core\Module\ModuleComposerLinker;
use Throwable;

trait LinksModuleToComposer
{
    /**
     * @return array{
     *     skipped: bool,
     *     package: ?string,
     *     path_url: ?string,
     *     repository_added: bool,
     *     require_added: bool,
     *     changed: bool,
     *     update_ran: bool,
     *     update_ok: bool,
     *     update_output: string,
     *     error: ?string,
     * }
     */
    protected function linkModuleToComposer(string $moduleDirectory, bool $runUpdate = true): array
    {
        $empty = [
            'skipped' => false,
            'package' => null,
            'path_url' => null,
            'repository_added' => false,
            'require_added' => false,
            'changed' => false,
            'update_ran' => false,
            'update_ok' => false,
            'update_output' => '',
            'error' => null,
        ];

        try {
            $result = app(ModuleComposerLinker::class)->link(
                base_path('composer.json'),
                $moduleDirectory,
            );
        } catch (Throwable $e) {
            $empty['error'] = $e->getMessage();

            return $empty;
        }

        $merged = array_merge($empty, $result);

        if (! $runUpdate) {
            return $merged;
        }

        $package = (string) $result['package'];
        $process = Process::path(base_path())
            ->timeout(300)
            ->run(['composer', 'update', $package, '--no-interaction']);

        $merged['update_ran'] = true;
        $merged['update_ok'] = $process->successful();
        $merged['update_output'] = trim($process->output()."\n".$process->errorOutput());

        return $merged;
    }

    /**
     * @param  array{
     *     skipped?: bool,
     *     package: ?string,
     *     path_url: ?string,
     *     repository_added: bool,
     *     require_added: bool,
     *     changed: bool,
     *     update_ran: bool,
     *     update_ok: bool,
     *     update_output: string,
     *     error: ?string,
     * }  $link
     */
    protected function reportComposerLink(array $link): void
    {
        if (($link['error'] ?? null) !== null) {
            $this->components->warn('自动挂载 composer 失败：'.$link['error']);

            return;
        }

        $package = (string) ($link['package'] ?? '');
        $this->newLine();
        $this->components->info('Composer 挂载');

        if ($link['changed']) {
            if ($link['repository_added']) {
                $this->components->twoColumnDetail('path 仓库', (string) $link['path_url']);
            } else {
                $this->components->twoColumnDetail('path 仓库', '已存在，跳过');
            }
            if ($link['require_added']) {
                $this->components->twoColumnDetail('require', "{$package}: @dev");
            } else {
                $this->components->twoColumnDetail('require', '已存在，跳过');
            }
        } else {
            $this->components->twoColumnDetail($package, '已挂载，无需修改');
        }

        if (! $link['update_ran']) {
            $this->components->twoColumnDetail('composer update', '已跳过');

            return;
        }

        if ($link['update_ok']) {
            $this->components->twoColumnDetail('composer update', '成功');

            return;
        }

        $this->components->warn("composer update {$package} 失败，请在 backend/ 手动执行：");
        $this->line("  composer update {$package}");
        if ($link['update_output'] !== '') {
            $this->line($link['update_output']);
        }
    }
}
