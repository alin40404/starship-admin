<?php

namespace Plugins\MailSmtp\Providers;

use Illuminate\Support\ServiceProvider;
use Plugins\MailSmtp\Services\SmtpMailSender;
use Starship\Core\Contracts\Mail\MailSenderInterface;

class MailSmtpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailSenderInterface::class, SmtpMailSender::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'mail-smtp');
    }
}
