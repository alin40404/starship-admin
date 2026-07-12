<?php

namespace Modules\System\App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

/**
 * 管理端密码登录失败阶梯锁定（IP + 用户名）。
 */
final class AdminLoginLockout
{
    private const FAIL_WINDOW_HOURS = 24;

    private const CACHE_PREFIX_FAILS = 'admin:login:fails:';

    private const CACHE_PREFIX_LOCK = 'admin:login:lock_until:';

    public static function lockedResponse(string $ip, string $username): ?JsonResponse
    {
        $until = self::lockedUntil($ip, $username);
        if ($until === null) {
            return null;
        }

        return ApiResponse::error(
            __('system::messages.login_locked', ['minutes' => self::remainingMinutes($until)]),
            429,
        );
    }

    public static function afterFailure(string $ip, string $username): JsonResponse
    {
        $tiers = AdminRuntimeConfig::loginLockoutTiers();
        if ($tiers === []) {
            return ApiResponse::error(__('system::messages.invalid_credentials'), 401);
        }

        $failKey = self::failKey($ip, $username);
        $count = (int) Cache::get($failKey, 0) + 1;
        Cache::put($failKey, $count, now()->addHours(self::FAIL_WINDOW_HOURS));

        $tier = self::resolveTier($count, $tiers);
        if ($tier === null) {
            return ApiResponse::error(__('system::messages.invalid_credentials'), 401);
        }

        $lockedUntil = time() + ($tier['minutes'] * 60);
        $lockKey = self::lockKey($ip, $username);
        $existingUntil = Cache::get($lockKey);
        if (is_numeric($existingUntil)) {
            $lockedUntil = max((int) $existingUntil, $lockedUntil);
        }

        $ttlMinutes = max(1, self::remainingMinutes($lockedUntil));
        Cache::put($lockKey, $lockedUntil, now()->addMinutes($ttlMinutes));

        return ApiResponse::error(
            __('system::messages.login_locked', ['minutes' => $tier['minutes']]),
            429,
        );
    }

    public static function clear(string $ip, string $username): void
    {
        Cache::forget(self::failKey($ip, $username));
        Cache::forget(self::lockKey($ip, $username));
    }

    private static function lockedUntil(string $ip, string $username): ?int
    {
        $until = Cache::get(self::lockKey($ip, $username));
        if (! is_int($until) && ! is_numeric($until)) {
            return null;
        }

        $until = (int) $until;

        return $until > time() ? $until : null;
    }

    private static function remainingMinutes(int $lockedUntil): int
    {
        return max(1, (int) ceil(($lockedUntil - time()) / 60));
    }

    /**
     * @param  list<array{attempts: int, minutes: int}>  $tiers
     * @return array{attempts: int, minutes: int}|null
     */
    private static function resolveTier(int $failCount, array $tiers): ?array
    {
        $matched = null;
        foreach ($tiers as $tier) {
            if ($failCount >= $tier['attempts']) {
                $matched = $tier;
            }
        }

        return $matched;
    }

    private static function failKey(string $ip, string $username): string
    {
        return self::CACHE_PREFIX_FAILS.self::subjectHash($ip, $username);
    }

    private static function lockKey(string $ip, string $username): string
    {
        return self::CACHE_PREFIX_LOCK.self::subjectHash($ip, $username);
    }

    private static function subjectHash(string $ip, string $username): string
    {
        return hash('sha256', $ip.'|'.strtolower(trim($username)));
    }
}
