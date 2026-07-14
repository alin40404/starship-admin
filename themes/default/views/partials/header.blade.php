<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-brand" href="{{ url('/') }}">
            <img
                class="site-brand__logo"
                src="{{ theme_asset('images/logo.svg') }}"
                width="28"
                height="28"
                alt=""
            >
            <span>{{ $theme['title'] ?? 'Starship' }}</span>
            <span class="site-brand__tagline">Hello · Default</span>
        </a>
        <ul class="site-nav">
            <li>
                <a href="{{ url('/') }}" @class(['is-active' => request()->is('/')])>{{ __('theme::ui.nav_home') }}</a>
            </li>
            @auth('member')
                <li>
                    <a
                        href="{{ route('member.account') }}"
                        @class(['is-active' => request()->is('member/account')])
                    >{{ __('theme::ui.nav_account') }}</a>
                </li>
                <li>
                    <form class="site-nav__logout" method="post" action="{{ route('member.logout') }}">
                        @csrf
                        <button type="submit">{{ __('theme::ui.nav_logout') }}</button>
                    </form>
                </li>
            @else
                <li>
                    <a
                        href="{{ route('member.login') }}"
                        @class(['is-active' => request()->is('member/login')])
                    >{{ __('theme::ui.nav_login') }}</a>
                </li>
                <li>
                    <a
                        href="{{ route('member.register') }}"
                        @class(['is-active' => request()->is('member/register')])
                    >{{ __('theme::ui.nav_register') }}</a>
                </li>
            @endauth
            <li class="site-locale" aria-label="Language">
                @foreach (config('system.locales', []) as $i => $locale)
                    @if ($i > 0)
                        <span class="site-locale__sep">/</span>
                    @endif
                    <a
                        href="{{ route('system.locale', ['locale' => $locale]) }}"
                        @class(['is-active' => app()->getLocale() === $locale])
                    >{{ __('theme::ui.locale_'.$locale) }}</a>
                @endforeach
            </li>
        </ul>
    </div>
</header>
