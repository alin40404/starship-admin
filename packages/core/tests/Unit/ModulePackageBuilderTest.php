<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use Starship\Core\Module\ModuleLocator;
use Starship\Core\Module\ModulePackageBuilder;
use Starship\Core\Tests\TestCase;

class ModulePackageBuilderTest extends TestCase
{
    private string $modulesPath = '';

    private string $outputPath = '';

    protected function tearDown(): void
    {
        if ($this->modulesPath !== '' && File::isDirectory($this->modulesPath)) {
            File::deleteDirectory($this->modulesPath);
        }
        if ($this->outputPath !== '' && File::isDirectory($this->outputPath)) {
            File::deleteDirectory($this->outputPath);
        }

        parent::tearDown();
    }

    public function test_export_builds_manifest_backend_and_admin_ui(): void
    {
        $this->modulesPath = storage_path('framework/testing/modules-'.uniqid('', true));
        $this->outputPath = storage_path('framework/testing/module-export-'.uniqid('', true));
        $frontendSrc = storage_path('framework/testing/frontend-src-'.uniqid('', true));

        config([
            'starship.modules_path' => $this->modulesPath,
            'starship.admin_frontend_src' => $frontendSrc,
            'starship.repo_root' => storage_path('framework/testing/repo-'.uniqid('', true)),
        ]);

        $backend = $this->modulesPath.DIRECTORY_SEPARATOR.'DemoShop';
        File::ensureDirectoryExists($backend.DIRECTORY_SEPARATOR.'app');
        File::put($backend.DIRECTORY_SEPARATOR.'module.json', json_encode([
            'name' => 'demo-shop',
            'title' => '演示商城',
            'version' => '1.2.3',
            'enabled' => true,
            'providers' => ['Modules\\DemoShop\\App\\Providers\\DemoShopServiceProvider'],
            'seeders' => ['Modules\\DemoShop\\Database\\Seeders\\DemoMenuSeeder'],
            'require' => ['modules' => ['system']],
            'menus' => ['paths' => ['/demo-shop', '/demo-shop/goods']],
            'permissions' => ['prefixes' => ['demo-shop'], 'exact' => []],
        ], JSON_THROW_ON_ERROR));

        $locator = new ModuleLocator($this->modulesPath);
        $feature = $locator->frontendFeatureRoot('demo-shop');
        File::ensureDirectoryExists($feature.DIRECTORY_SEPARATOR.'api');
        File::put($feature.DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'index.ts', "export {}\n");

        $builder = new ModulePackageBuilder($locator);
        $found = $locator->find('demo-shop');
        $this->assertNotNull($found);

        $result = $builder->export($found, $this->outputPath, false);

        $root = $result['package_root'];
        $this->assertDirectoryExists($root.DIRECTORY_SEPARATOR.'backend');
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'module.json');
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'admin-ui'.DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'index.ts');
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'manifest.json');

        $manifest = json_decode((string) File::get($root.DIRECTORY_SEPARATOR.'manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('demo-shop', $manifest['name']);
        $this->assertSame('1.2.3', $manifest['version']);
        $this->assertSame('modules/DemoShop', $manifest['backend_target']);
        $this->assertSame('apps/admin/web-antd/src/features/demo-shop', $manifest['admin_ui_target']);
        $this->assertTrue($manifest['has_admin_ui']);
        $this->assertSame(['system'], $manifest['require']['modules']);

        if (File::isDirectory($frontendSrc)) {
            File::deleteDirectory($frontendSrc);
        }
    }
}
