<?php

namespace Starship\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $header = (string) $request->header('Accept-Language', '');
        $primary = trim(explode(',', $header)[0] ?? '');

        return match ($primary) {
            'zh-CN', 'zh_CN', 'zh' => 'zh_CN',
            'en-US', 'en_US', 'en' => 'en',
            default => (string) config('app.locale', 'zh_CN'),
        };
    }
}
