<?php

namespace Modules\System\App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;

/**
 * 管理端密码登录失败阶梯锁定（IP + 登录账号；支持用户名或邮箱，同一管理员按用户名统一计数）。
 */
final class AdminLoginLockout
{
    private const FAIL_WINDOW_HOURS = 24;

    private const CACHE_PREFIX_FAILS = 'admin:login:fails:';

    private const CACHE_PREFIX_LOCK = 'admin:login:lock_until:';

    /**
     * 解析锁定主体：账号存在时统一用用户名，避免邮箱/用户名分开计数。
     */
    public static function resolveLockSubject(string $identifier, ?string $canonicalUsername = null): string
    {
        if ($canonicalUsername !== null && trim($canonicalUsername) !== '') {
            return strtolower(trim($canonicalUsername));
        }

        return strtolower(trim($identifier));
    }

    public static function lockedResponse(string $ip, string $accountSubject): ?JsonResponse
    {
        $until = self::lockedUntil($ip, $accountSubject);
        if ($until === null) {
            return null;
        }

        return ApiResponse::error(
            __('system::messages.login_locked', ['minutes' => self::remainingMinutes($until)]),
            429,
        );
    }

    public static function afterFailure(string $ip, string $accountSubject): JsonResponse
    {
        $tiers = AdminRuntimeConfig::loginLockoutTiers();
        if ($tiers === []) {
            return ApiResponse::error(__('system::messages.invalid_credentials'), 401);
        }

        $failKey = self::failKey($ip, $accountSubject);
        $count = (int) Cache::get($failKey, 0) + 1;
        Cache::put($failKey, $count, now()->addHours(self::FAIL_WINDOW_HOURS));

        $tier = self::resolveTier($count, $tiers);
        if ($tier === null) {
            return ApiResponse::error(__('system::messages.invalid_credentials'), 401);
        }

        $lockedUntil = time() + ($tier['minutes'] * 60);
        $lockKey = self::lockKey($ip, $accountSubject);
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

    public static function clear(string $ip, string $accountSubject): void
    {
        Cache::forget(self::failKey($ip, $accountSubject));
        Cache::forget(self::lockKey($ip, $accountSubject));
    }

    private static function lockedUntil(string $ip, string $accountSubject): ?int
    {
        $until = Cache::get(self::lockKey($ip, $accountSubject));
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

    private static function failKey(string $ip, string $accountSubject): string
    {
        return self::CACHE_PREFIX_FAILS.self::subjectHash($ip, $accountSubject);
    }

    private static function lockKey(string $ip, string $accountSubject): string
    {
        return self::CACHE_PREFIX_LOCK.self::subjectHash($ip, $accountSubject);
    }

    private static function subjectHash(string $ip, string $accountSubject): string
    {
        return hash('sha256', $ip.'|'.strtolower(trim($accountSubject)));
    }
}
