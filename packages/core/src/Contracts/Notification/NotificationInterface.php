<?php

namespace Starship\Core\Contracts\Notification;

/**
 * 统一消息投递契约（短信 / 邮件 / 站内信等通道聚合）。
 * 接口已入库；与 Sms / Mail 细粒度 Contract 并存，聚合实现延后。
 */
interface NotificationInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(string $receiver, string $template, array $data = []): void;
}
