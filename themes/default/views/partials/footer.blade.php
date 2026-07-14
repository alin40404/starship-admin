<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>© {{ date('Y') }} {{ $theme['title'] ?? 'Starship' }}. {{ __('theme::ui.footer_rights') }}.</div>
        <div>
            <a href="{{ url('/') }}">{{ __('theme::ui.nav_home') }}</a>
            ·
            @auth('member')
                <a href="{{ route('member.account') }}">{{ __('theme::ui.nav_account') }}</a>
            @else
                <a href="{{ route('member.login') }}">{{ __('theme::ui.member_login') }}</a>
                ·
                <a href="{{ route('member.register') }}">{{ __('theme::ui.nav_register') }}</a>
            @endauth
        </div>
    </div>
</footer>
