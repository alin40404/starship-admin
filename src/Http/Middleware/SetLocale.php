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
        $allowed = $this->allowedLocales();

        $cookieLocale = $request->cookie('locale');
        if (is_string($cookieLocale) && in_array($cookieLocale, $allowed, true)) {
            return $cookieLocale;
        }

        if ($request->hasSession()) {
            $sessionLocale = $request->session()->get('locale');
            if (is_string($sessionLocale) && in_array($sessionLocale, $allowed, true)) {
                return $sessionLocale;
            }
        }

        $header = (string) $request->header('Accept-Language', '');
        $primary = trim(explode(',', $header)[0] ?? '');
        $primary = trim(explode(';', $primary)[0] ?? '');

        $fromHeader = match ($primary) {
            'zh-CN', 'zh_CN', 'zh' => 'zh_CN',
            'en-US', 'en_US', 'en' => 'en',
            default => null,
        };

        if ($fromHeader !== null && in_array($fromHeader, $allowed, true)) {
            return $fromHeader;
        }

        $fallback = (string) config('app.locale', 'zh_CN');

        return in_array($fallback, $allowed, true) ? $fallback : ($allowed[0] ?? 'zh_CN');
    }

    /**
     * @return list<string>
     */
    protected function allowedLocales(): array
    {
        $locales = config('starship.locales', ['zh_CN', 'en']);

        return array_values(array_filter(
            is_array($locales) ? $locales : ['zh_CN', 'en'],
            static fn ($item) => is_string($item) && $item !== '',
        ));
    }
}
