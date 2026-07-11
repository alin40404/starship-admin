<?php

namespace Plugins\SmsAliyun\Providers;

use Illuminate\Support\ServiceProvider;

class SmsAliyunServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'sms-aliyun');
    }
}
