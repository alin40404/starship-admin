<?php

namespace Modules\System\App\Support;

use Starship\Core\Support\DevEnvironment;

/**
 * 管理端运行参数（admin.*）读取与解析。
 */
final class AdminRuntimeConfig
{
    public const KEY_PAGE_SIZE = 'admin.page_size';

    public const KEY_ATTACHMENT_FLOW_PAGE_SIZE = 'admin.attachment.flow_page_size';

    public const KEY_API_TIMEOUT = 'admin.api.timeout';

    public const KEY_JWT_TTL = 'admin.jwt.ttl';

    public const KEY_LOGIN_CAPTCHA = 'admin.login.captcha';

    public const KEY_LOGIN_CODE = 'admin.login.code';

    public const KEY_LOGIN_QRCODE = 'admin.login.qrcode';

    public const KEY_LOGIN_FORGET_PASSWORD = 'admin.login.forget_password';

    public const KEY_LOGIN_REGISTER = 'admin.login.register';

    public const KEY_LOGIN_THIRD_PARTY = 'admin.login.third_party';

    public const KEY_LOGIN_LOCKOUT_TIERS = 'admin.login.lockout_tiers';

    public const KEY_MAINTENANCE = 'admin.maintenance';

    public const DEFAULT_LOGIN_LOCKOUT_TIERS_JSON = '[{"attempts":5,"minutes":15},{"attempts":10,"minutes":45},{"attempts":20,"minutes":1440}]';

    public const DEFAULT_PAGE_SIZE = 20;

    public const DEFAULT_ATTACHMENT_FLOW_PAGE_SIZE = 50;

    public const DEFAULT_API_TIMEOUT_SECONDS = 30;

    public const DEFAULT_JWT_TTL_MINUTES = 120;

    public static function pageSize(): int
    {
        return self::positiveInt(self::KEY_PAGE_SIZE, self::DEFAULT_PAGE_SIZE);
    }

    public static function attachmentFlowPageSize(): int
    {
        return self::positiveInt(
            self::KEY_ATTACHMENT_FLOW_PAGE_SIZE,
            self::DEFAULT_ATTACHMENT_FLOW_PAGE_SIZE,
        );
    }

    public static function apiTimeoutSeconds(): int
    {
        return self::positiveInt(self::KEY_API_TIMEOUT, self::DEFAULT_API_TIMEOUT_SECONDS);
    }

    public static function jwtTtlMinutes(): int
    {
        return self::positiveInt(self::KEY_JWT_TTL, self::DEFAULT_JWT_TTL_MINUTES);
    }

    public static function loginCaptchaEnabled(): bool
    {
        return self::toBool(SettingStore::get(self::KEY_LOGIN_CAPTCHA, 'false'));
    }

    public static function loginCodeEnabled(): bool
    {
        return self::toBool(SettingStore::get(self::KEY_LOGIN_CODE, 'false'));
    }

    public static function loginQrcodeEnabled(): bool
    {
        return self::toBool(SettingStore::get(self::KEY_LOGIN_QRCODE, 'false'));
    }

    public static function loginForgetPasswordEnabled(): bool
    {
        return self::toBool(SettingStore::get(self::KEY_LOGIN_FORGET_PASSWORD, 'true'));
    }

    public static function loginRegisterEnabled(): bool
    {
        return self::toBool(SettingStore::get(self::KEY_LOGIN_REGISTER, 'false'));
    }

    public static function loginThirdPartyEnabled(): bool
    {
        return self::toBool(SettingStore::get(self::KEY_LOGIN_THIRD_PARTY, 'false'));
    }

    public static function maintenanceEnabled(): bool
    {
        return self::toBool(SettingStore::get(self::KEY_MAINTENANCE, 'false'));
    }

    /**
     * @return list<array{attempts: int, minutes: int}>
     */
    public static function loginLockoutTiers(): array
    {
        $raw = trim((string) SettingStore::get(
            self::KEY_LOGIN_LOCKOUT_TIERS,
            self::DEFAULT_LOGIN_LOCKOUT_TIERS_JSON,
        ));

        if ($raw === '' || $raw === '[]') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $decoded = json_decode(self::DEFAULT_LOGIN_LOCKOUT_TIERS_JSON, true);
        }

        if (! is_array($decoded)) {
            return [];
        }

        $tiers = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $attempts = (int) ($item['attempts'] ?? 0);
            $minutes = (int) ($item['minutes'] ?? 0);
            if ($attempts > 0 && $minutes > 0) {
                $tiers[] = ['attempts' => $attempts, 'minutes' => $minutes];
            }
        }

        usort($tiers, fn (array $left, array $right): int => $left['attempts'] <=> $right['attempts']);

        $valid = [];
        $previousAttempts = 0;
        foreach ($tiers as $tier) {
            if ($tier['attempts'] > $previousAttempts) {
                $valid[] = $tier;
                $previousAttempts = $tier['attempts'];
            }
        }

        return $valid;
    }

    /**
     * @param  mixed  $requested
     */
    public static function perPage($requested): int
    {
        $size = (int) $requested;

        return $size > 0 ? $size : self::pageSize();
    }

    /**
     * @return array{
     *     pageSize: int,
     *     attachmentFlowPageSize: int,
     *     apiTimeoutSeconds: int,
     *     loginCaptcha: bool,
     *     loginCode: bool,
     *     loginQrcode: bool,
     *     loginForgetPassword: bool,
     *     loginRegister: bool,
     *     loginThirdParty: bool,
     *     maintenance: bool,
     *     isDevelopment: bool
     * }
     */
    public static function publicSnapshot(): array
    {
        return [
            'pageSize' => self::pageSize(),
            'attachmentFlowPageSize' => self::attachmentFlowPageSize(),
            'apiTimeoutSeconds' => self::apiTimeoutSeconds(),
            'loginCaptcha' => self::loginCaptchaEnabled(),
            'loginCode' => self::loginCodeEnabled(),
            'loginQrcode' => self::loginQrcodeEnabled(),
            'loginForgetPassword' => self::loginForgetPasswordEnabled(),
            'loginRegister' => self::loginRegisterEnabled(),
            'loginThirdParty' => self::loginThirdPartyEnabled(),
            'maintenance' => self::maintenanceEnabled(),
            'isDevelopment' => DevEnvironment::isDevelopment(),
        ];
    }

    private static function positiveInt(string $key, int $fallback): int
    {
        $parsed = (int) SettingStore::get($key, (string) $fallback);

        return $parsed > 0 ? $parsed : $fallback;
    }

    private static function toBool(?string $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}
