<?php

namespace Plugins\SmsAliyun\Providers;

use Illuminate\Support\ServiceProvider;
use Plugins\SmsAliyun\Services\AliyunSmsSender;
use Starship\Core\Contracts\Sms\SmsSenderInterface;

class SmsAliyunServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsSenderInterface::class, AliyunSmsSender::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'sms-aliyun');
    }
}
