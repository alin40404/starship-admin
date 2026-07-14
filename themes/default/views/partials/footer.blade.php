<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>© {{ date('Y') }} {{ $theme['title'] ?? 'Starship' }}. All rights reserved.</div>
        <div>
            <a href="{{ url('/') }}">首页</a>
            ·
            @auth('member')
                <a href="{{ route('member.account') }}">会员中心</a>
            @else
                <a href="{{ route('member.login') }}">会员登录</a>
                ·
                <a href="{{ route('member.register') }}">注册</a>
            @endauth
        </div>
    </div>
</footer>
