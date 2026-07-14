<?php

namespace Modules\System\App\Support;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;

/**
 * 组合各模块的「未登录访客跳转」策略（避免互相覆盖 Authenticate::redirectUsing）。
 *
 * handler 返回值：
 * - string：跳转到该 URL
 * - null：本路径已接管，且不跳转（如 admin-api）
 * - false：非本模块路径，继续下一个 handler
 */
final class GuestRedirect
{
    /** @var list<callable(Request): (string|null|false)> */
    private static array $handlers = [];

    private static bool $bound = false;

    /**
     * @param  callable(Request): (string|null|false)  $handler
     */
    public static function using(callable $handler): void
    {
        self::$handlers[] = $handler;
        self::ensureBound();
    }

    private static function ensureBound(): void
    {
        if (self::$bound) {
            return;
        }

        self::$bound = true;

        Authenticate::redirectUsing(static function (Request $request): ?string {
            foreach (self::$handlers as $handler) {
                $result = $handler($request);
                if ($result === false) {
                    continue;
                }

                return $result;
            }

            return null;
        });
    }
}
