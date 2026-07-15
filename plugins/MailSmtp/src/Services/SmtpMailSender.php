<?php

namespace Plugins\MailSmtp\Services;

use Illuminate\Support\Facades\Mail;
use Starship\Core\Contracts\Mail\MailSenderInterface;
use Starship\Core\Plugin\PluginConfigRepository;

final class SmtpMailSender implements MailSenderInterface
{
    public function __construct(
        protected PluginConfigRepository $pluginConfigRepository,
    ) {}

    public function send(string $to, string $subject, string $body, array $options = []): void
    {
        $to = trim($to);
        $subject = trim($subject);

        if ($to === '' || $subject === '') {
            throw new \InvalidArgumentException(__('mail-smtp::messages.send_invalid'));
        }

        $this->applyMailerConfig();

        $from = trim((string) ($options['from'] ?? $this->config('from_address')));
        $fromName = trim((string) ($options['from_name'] ?? $this->config('from_name')));
        $html = (bool) ($options['html'] ?? true);

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

    private function applyMailerConfig(): void
    {
        $host = $this->config('host');
        $username = $this->config('username');
        $password = $this->config('password');
        $port = (int) $this->config('port', '465');
        $encryption = strtolower($this->config('encryption', 'ssl'));
        $from = $this->config('from_address');
        $fromName = $this->config('from_name');

        if ($host === '' || $username === '' || $password === '') {
            throw new \RuntimeException(__('mail-smtp::messages.config_incomplete'));
        }

        if ($from === '') {
            $from = $username;
        }

        if ($fromName === '') {
            $fromName = (string) config('app.name', 'Starship Admin');
        }

        if ($port <= 0) {
            $port = $encryption === 'ssl' ? 465 : 587;
        }

        $scheme = match ($encryption) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'starttls' => 'smtp',
            default => $port === 465 ? 'smtps' : 'smtp',
        };

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => $password,
            'mail.from.address' => $from,
            'mail.from.name' => $fromName,
        ]);
    }

    private function config(string $key, string $default = ''): string
    {
        return trim((string) $this->pluginConfigRepository->get('mail-smtp', $key, $default));
    }
}
