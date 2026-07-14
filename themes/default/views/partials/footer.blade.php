<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>© {{ date('Y') }} {{ $theme['title'] ?? 'Starship' }}. All rights reserved.</div>
        <div>
            <a href="{{ url('/') }}">首页</a>
            ·
            <a href="{{ url('/member/account') }}">会员中心</a>
        </div>
    </div>
</footer>
