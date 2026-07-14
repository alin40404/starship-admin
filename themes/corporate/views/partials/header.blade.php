<header class="site-header">
    <div class="site-header__inner">
        <a class="site-brand" href="{{ url('/') }}">
            <img
                class="site-brand__logo"
                src="{{ theme_asset('images/logo.svg') }}"
                width="28"
                height="28"
                alt=""
            >
            <span>Starship</span>
        </a>

        <ul class="site-nav">
            <li>
                <a href="{{ url('/') }}" @class(['is-active' => request()->is('/')])>{{ __('theme::ui.nav_home') }}</a>
            </li>
            <li>
                <a href="{{ url('/') }}#solutions">{{ __('theme::ui.nav_solutions') }}</a>
            </li>
            <li>
                <a href="{{ url('/') }}#cases">{{ __('theme::ui.nav_cases') }}</a>
            </li>
            <li>
                <a href="{{ url('/') }}#about">{{ __('theme::ui.nav_about') }}</a>
            </li>
        </ul>

        <div class="site-header__actions">
            <div class="site-locale" aria-label="Language">
                <a
                    href="{{ route('starship.locale', ['locale' => 'zh_CN']) }}"
                    @class(['is-active' => app()->getLocale() === 'zh_CN'])
                >{{ __('theme::ui.locale_zh') }}</a>
                <span class="site-locale__sep">/</span>
                <a
                    href="{{ route('starship.locale', ['locale' => 'en']) }}"
                    @class(['is-active' => app()->getLocale() === 'en'])
                >{{ __('theme::ui.locale_en') }}</a>
            </div>

            @auth('member')
                <a
                    class="site-header__login"
                    href="{{ route('member.account') }}"
                    @class(['is-active' => request()->is('member/account')])
                >{{ __('theme::ui.nav_account') }}</a>
                <form class="site-header__logout" method="post" action="{{ route('member.logout') }}">
                    @csrf
                    <button type="submit">{{ __('theme::ui.nav_logout') }}</button>
                </form>
            @else
                <a
                    class="site-header__login"
                    href="{{ route('member.login') }}"
                    @class(['is-active' => request()->is('member/login')])
                >
                    <svg class="site-header__login-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M10 17l5-5-5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 12H3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        <path d="M15 4h4a2 2 0 012 2v12a2 2 0 01-2 2h-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                    {{ __('theme::ui.nav_login') }}
                </a>
            @endauth
        </div>
    </div>
</header>
