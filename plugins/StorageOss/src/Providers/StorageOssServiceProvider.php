<?php

namespace Plugins\StorageOss\Providers;

use Illuminate\Support\ServiceProvider;
use Plugins\StorageOss\Services\AliOssStorage;
use Starship\Core\Contracts\Storage\StorageInterface;

class StorageOssServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StorageInterface::class, AliOssStorage::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'storage-oss');
    }
}
