<?php

namespace Modules\System\App\Support\Mail;

use Illuminate\Support\Facades\Mail;
use Modules\System\App\Support\MailConfigRegistrar;
use Starship\Core\Contracts\Mail\MailSenderInterface;

class MailGateway implements MailSenderInterface
{
    public function send(string $to, string $subject, string $body, array $options = []): void
    {
        $to = trim($to);
        $subject = trim($subject);

        if ($to === '' || $subject === '') {
            throw new \InvalidArgumentException(__('system::messages.mail_send_invalid'));
        }

        MailConfigRegistrar::apply();

        if (! MailConfigRegistrar::isConfigured() && config('mail.default') !== 'log') {
            throw new \RuntimeException(__('system::messages.mail_not_configured'));
        }

        $html = (bool) ($options['html'] ?? true);
        $from = trim((string) ($options['from'] ?? ''));
        $fromName = trim((string) ($options['from_name'] ?? ''));

        $callback = function ($message) use ($to, $subject, $from, $fromName): void {
            $message->to($to)->subject($subject);
            if ($from !== '') {
                $message->from($from, $fromName !== '' ? $fromName : null);
            }
        };

        if ($html) {
            Mail::html($body, $callback);

            return;
        }

        Mail::raw($body, $callback);
    }
}
