<footer class="site-footer">
    <div class="site-footer__inner">
        <div>© {{ date('Y') }} Starship · Corporate</div>
        <div>
            <a href="{{ url('/') }}#solutions">方案</a>
            ·
            <a href="{{ url('/') }}#cases">案例</a>
            ·
            <a href="{{ url('/') }}#about">关于</a>
            ·
            @auth('member')
                <a href="{{ route('member.account') }}">会员中心</a>
            @else
                <a href="{{ route('member.login') }}">会员登录</a>
            @endauth
        </div>
    </div>
</footer>
