<?php

namespace Modules\System\App\Support;

/**
 * 从系统配置（短信配置 Tab）读取短信网关参数。
 */
final class SmsConfigRegistrar
{
    public static function isEnabled(): bool
    {
        return SettingStore::get('sms.enabled', '0') === '1';
    }

    public static function provider(): string
    {
        $provider = trim((string) SettingStore::get('sms.provider', 'aliyun'));

        return in_array($provider, ['aliyun', 'tencent', 'huawei'], true)
            ? $provider
            : 'aliyun';
    }

    public static function accessKey(): string
    {
        return trim((string) SettingStore::get('sms.access_key', ''));
    }

    public static function secretKey(): string
    {
        return trim((string) SettingStore::get('sms.secret_key', ''));
    }

    public static function signName(): string
    {
        return trim((string) SettingStore::get('sms.sign_name', ''));
    }

    public static function templateCode(): string
    {
        return trim((string) SettingStore::get('sms.template_code', ''));
    }

    public static function region(): string
    {
        $region = trim((string) SettingStore::get('sms.region', ''));

        return $region !== '' ? $region : 'cn-hangzhou';
    }

    /** 腾讯云 SdkAppId / 华为云短信通道号 */
    public static function appId(): string
    {
        return trim((string) SettingStore::get('sms.app_id', ''));
    }

    public static function isConfigured(): bool
    {
        if (self::accessKey() === '' || self::secretKey() === '' || self::signName() === '' || self::templateCode() === '') {
            return false;
        }

        return match (self::provider()) {
            'tencent', 'huawei' => self::appId() !== '',
            default => true,
        };
    }
}
