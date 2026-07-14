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
                <a href="{{ url('/') }}" @class(['is-active' => request()->is('/')])>首页</a>
            </li>
            <li>
                <a href="{{ url('/member/login') }}" @class(['is-active' => request()->is('member/login')])>登录</a>
            </li>
            <li>
                <a href="{{ url('/member/register') }}" @class(['is-active' => request()->is('member/register')])>注册</a>
            </li>
        </ul>
    </div>
</header>
