<?php

namespace Starship\Core\Contracts\Mail;

/**
 * 邮件发送契约。System 提供默认实现，插件可覆盖容器绑定。
 */
interface MailSenderInterface
{
    /**
     * @param  array{html?: bool, from?: string, from_name?: string}  $options
     */
    public function send(string $to, string $subject, string $body, array $options = []): void;
}
