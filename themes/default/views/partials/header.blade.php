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
                <a
                    href="{{ route('starship.locale', ['locale' => 'zh_CN']) }}"
                    @class(['is-active' => app()->getLocale() === 'zh_CN'])
                >{{ __('theme::ui.locale_zh') }}</a>
                <span class="site-locale__sep">/</span>
                <a
                    href="{{ route('starship.locale', ['locale' => 'en']) }}"
                    @class(['is-active' => app()->getLocale() === 'en'])
                >{{ __('theme::ui.locale_en') }}</a>
            </li>
        </ul>
    </div>
</header>
