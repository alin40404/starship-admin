<?php

namespace Starship\Core\Contracts\Sms;

/**
 * 短信发送契约。System 提供默认实现，插件可覆盖容器绑定。
 */
interface SmsSenderInterface
{
    public function sendVerificationCode(string $mobile, string $code): void;
}
