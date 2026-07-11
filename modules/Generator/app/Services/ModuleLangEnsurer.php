<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\File;
use Modules\Generator\App\Support\ModulePathResolver;

final class ModuleLangEnsurer
{
    public function __construct(
        protected ModulePathResolver $modulePathResolver,
    ) {}

    public function ensure(string $moduleSlug): void
    {
        $moduleDir = $this->modulePathResolver->resolveModuleDirectory($moduleSlug);
        $translationNamespace = strtolower($moduleSlug);

        foreach (['zh_CN', 'en'] as $locale) {
            $targetDir = $moduleDir.'/resources/lang/'.$locale;
            $targetFile = $targetDir.'/messages.php';

            if (File::exists($targetFile)) {
                continue;
            }

            File::ensureDirectoryExists($targetDir);
            File::put($targetFile, $this->defaultMessages($locale));
        }

        $this->ensureServiceProviderLoadsTranslations($moduleDir, $translationNamespace);
    }

    protected function ensureServiceProviderLoadsTranslations(string $moduleDir, string $translationNamespace): void
    {
        $providerDir = $moduleDir.'/app/Providers';
        if (! File::isDirectory($providerDir)) {
            return;
        }

        $providers = File::glob($providerDir.'/*ServiceProvider.php') ?: [];
        foreach ($providers as $providerFile) {
            $content = (string) File::get($providerFile);
            if (str_contains($content, 'loadTranslationsFrom')) {
                continue;
            }

            $needle = '$this->loadMigrationsFrom';
            if (! str_contains($content, $needle)) {
                continue;
            }

            $insert = "\n        \$this->loadTranslationsFrom(__DIR__.'/../../resources/lang', '{$translationNamespace}');";
            $updated = str_replace($needle, $insert.PHP_EOL.$needle, $content, $count);
            if ($count > 0) {
                File::put($providerFile, $updated);
            }
        }
    }

    protected function defaultMessages(string $locale): string
    {
        if ($locale === 'zh_CN') {
            return <<<'PHP'
<?php

return [
    'created' => '创建成功',
    'updated' => '更新成功',
    'deleted' => '已删除',
    'batch_deleted' => '已批量删除',
    'batch_status_updated' => '已批量更新状态',
];

PHP;
        }

        return <<<'PHP'
<?php

return [
    'created' => 'Created successfully',
    'updated' => 'Updated successfully',
    'deleted' => 'Deleted successfully',
    'batch_deleted' => 'Batch deleted successfully',
    'batch_status_updated' => 'Batch status updated',
];

PHP;
    }
}
