<footer class="site-footer">
    <div class="site-footer__inner">
        <div>© {{ date('Y') }} {{ __('theme::ui.footer_brand') }}</div>
        <div>
            <a href="{{ url('/') }}#solutions">{{ __('theme::ui.nav_solutions') }}</a>
            ·
            <a href="{{ url('/') }}#cases">{{ __('theme::ui.nav_cases') }}</a>
            ·
            <a href="{{ url('/') }}#about">{{ __('theme::ui.nav_about') }}</a>
            ·
            @auth('member')
                <a href="{{ route('member.account') }}">{{ __('theme::ui.nav_account') }}</a>
            @else
                <a href="{{ route('member.login') }}">{{ __('theme::ui.nav_login') }}</a>
            @endauth
        </div>
    </div>
</footer>
