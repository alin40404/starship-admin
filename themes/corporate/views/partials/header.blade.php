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
                <a href="{{ url('/') }}" @class(['is-active' => request()->is('/')])>首页</a>
            </li>
            <li>
                <a href="{{ url('/') }}#solutions">方案</a>
            </li>
            <li>
                <a href="{{ url('/') }}#cases">案例</a>
            </li>
            <li>
                <a href="{{ url('/') }}#about">关于</a>
            </li>
        </ul>

        <div class="site-header__actions">
            @auth('member')
                <a
                    class="site-header__login"
                    href="{{ route('member.account') }}"
                    @class(['is-active' => request()->is('member/account')])
                >会员中心</a>
                <form class="site-header__logout" method="post" action="{{ route('member.logout') }}">
                    @csrf
                    <button type="submit">退出</button>
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
                    会员登录
                </a>
            @endauth
        </div>
    </div>
</header>
