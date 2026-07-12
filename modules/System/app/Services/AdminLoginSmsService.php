<?php

namespace Modules\System\App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\System\App\Models\Admin;
use Modules\System\App\Support\AdminRuntimeConfig;
use Modules\System\App\Support\Sms\SmsGateway;
use Modules\System\App\Support\SmsConfigRegistrar;

class AdminLoginSmsService
{
    public const CODE_TTL_MINUTES = 5;

    public const SEND_COOLDOWN_SECONDS = 60;

    public function ensureReady(): void
    {
        if (! AdminRuntimeConfig::loginCodeEnabled()) {
            throw new \RuntimeException(__('system::messages.sms_login_disabled'));
        }

        if (! SmsConfigRegistrar::isEnabled()) {
            throw new \RuntimeException(__('system::messages.sms_not_enabled'));
        }

        if (! SmsConfigRegistrar::isConfigured()) {
            throw new \RuntimeException(__('system::messages.sms_not_configured'));
        }
    }

    public function sendLoginCode(string $mobile): void
    {
        $this->ensureReady();

        $mobile = trim($mobile);
        $admin = Admin::findByMobile($mobile);

        if ($admin === null) {
            throw new \RuntimeException(__('system::messages.sms_mobile_not_found'));
        }

        if ((int) $admin->status !== 1) {
            throw new \RuntimeException(__('system::messages.sms_mobile_disabled'));
        }

        $cooldownKey = $this->cooldownCacheKey($mobile);
        if (Cache::has($cooldownKey)) {
            throw new \RuntimeException(__('system::messages.sms_send_too_frequent'));
        }

        $code = (string) random_int(100000, 999999);

        try {
            app(SmsGateway::class)->sendVerificationCode($mobile, $code);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : __('system::messages.sms_send_failed'),
            );
        }

        Cache::put($this->codeCacheKey($mobile), Hash::make($code), now()->addMinutes(self::CODE_TTL_MINUTES));
        Cache::put($cooldownKey, 1, now()->addSeconds(self::SEND_COOLDOWN_SECONDS));
    }

    public function verifyLoginCode(string $mobile, string $code): Admin
    {
        $this->ensureReady();

        $mobile = trim($mobile);
        $code = trim($code);
        $cached = Cache::get($this->codeCacheKey($mobile));

        if (! is_string($cached) || ! Hash::check($code, $cached)) {
            throw new \RuntimeException(__('system::messages.sms_code_invalid'));
        }

        $admin = Admin::findByMobile($mobile);
        if ($admin === null || (int) $admin->status !== 1) {
            throw new \RuntimeException(__('system::messages.sms_code_invalid'));
        }

        Cache::forget($this->codeCacheKey($mobile));

        return $admin;
    }

    public function sendTestCode(string $mobile): void
    {
        if (! SmsConfigRegistrar::isEnabled()) {
            throw new \RuntimeException(__('system::messages.sms_not_enabled'));
        }

        if (! SmsConfigRegistrar::isConfigured()) {
            throw new \RuntimeException(__('system::messages.sms_not_configured'));
        }

        $mobile = trim($mobile);
        $cooldownKey = 'admin_sms_test_cooldown:'.$mobile;
        if (Cache::has($cooldownKey)) {
            throw new \RuntimeException(__('system::messages.sms_send_too_frequent'));
        }

        $code = (string) random_int(100000, 999999);

        try {
            app(SmsGateway::class)->sendVerificationCode($mobile, $code);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : __('system::messages.sms_send_failed'),
            );
        }

        Cache::put($cooldownKey, 1, now()->addSeconds(self::SEND_COOLDOWN_SECONDS));
    }

    private function codeCacheKey(string $mobile): string
    {
        return 'admin_sms_login_code:'.$mobile;
    }

    private function cooldownCacheKey(string $mobile): string
    {
        return 'admin_sms_login_cooldown:'.$mobile;
    }
}
