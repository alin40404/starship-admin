<?php

namespace Plugins\SmsAliyun\Services;

use Plugins\SmsAliyun\Support\AliyunSmsClient;
use Starship\Core\Contracts\Sms\SmsSenderInterface;
use Starship\Core\Plugin\PluginConfigRepository;

final class AliyunSmsSender implements SmsSenderInterface
{
    public function __construct(
        protected PluginConfigRepository $pluginConfigRepository,
        protected AliyunSmsClient $aliyunSmsClient,
    ) {}

    public function sendVerificationCode(string $mobile, string $code): void
    {
        $accessKey = (string) $this->pluginConfigRepository->get('sms-aliyun', 'access_key', '');
        $secretKey = (string) $this->pluginConfigRepository->get('sms-aliyun', 'secret_key', '');
        $signName = (string) $this->pluginConfigRepository->get('sms-aliyun', 'sign_name', '');
        $templateCode = (string) $this->pluginConfigRepository->get('sms-aliyun', 'template_code', '');

        if ($accessKey === '' || $secretKey === '' || $signName === '' || $templateCode === '') {
            throw new \RuntimeException(__('sms-aliyun::messages.config_incomplete'));
        }

        $this->aliyunSmsClient->sendVerificationCode([
            'access_key' => $accessKey,
            'secret_key' => $secretKey,
            'sign_name' => $signName,
            'template_code' => $templateCode,
        ], $mobile, $code);
    }
}
