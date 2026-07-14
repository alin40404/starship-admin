<?php

namespace Modules\System\App\Http\Controllers\Frontend;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $allowed = config('system.locales', ['zh_CN', 'en']);
        if (! is_array($allowed) || ! in_array($locale, $allowed, true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        $minutes = 60 * 24 * 365;

        return redirect()
            ->to($this->safeBackUrl($request))
            ->withCookie(Cookie::make('locale', $locale, $minutes));
    }

    protected function safeBackUrl(Request $request): string
    {
        $fallback = url('/');
        $previous = url()->previous();

        if (! is_string($previous) || $previous === '') {
            return $fallback;
        }

        if (! str_starts_with($previous, $request->getSchemeAndHttpHost())) {
            return $fallback;
        }

        // 避免切完语言又停在 /locale/...
        if (str_contains(parse_url($previous, PHP_URL_PATH) ?? '', '/locale/')) {
            return $fallback;
        }

        return $previous;
    }
}
