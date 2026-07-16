<?php

namespace Plugins\PaymentWechat\Providers;

use Illuminate\Support\ServiceProvider;
use Plugins\PaymentWechat\Services\WechatPayment;
use Starship\Core\Contracts\Payment\PaymentInterface;

class PaymentWechatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentInterface::class, WechatPayment::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'payment-wechat');
    }
}
