@extends('theme::layouts.app')

@section('content')
    <div class="brand">Starship · Corporate</div>
    <h1>{{ $theme['title'] ?? '企业主题' }}</h1>
    <p class="lede">
        这是企业前台主题首页（Blade Demo）。深色布局与绿色强调色，用于区分「默认主题」。
    </p>
    <div class="panel">
        <ul class="meta">
            <li><span>主题 slug</span> · <strong data-theme-slug>{{ $theme['slug'] ?? 'corporate' }}</strong></li>
            <li><span>目录</span> · <strong>{{ $theme['directory'] ?? 'corporate' }}</strong></li>
            <li><span>类型</span> · <strong>{{ $theme['type'] ?? 'frontend' }}</strong></li>
        </ul>
        <p style="margin: 1.25rem 0 0; font-size: 0.95rem;">
            <a href="{{ url('/member/login') }}" style="color: var(--theme-accent);">会员登录</a>
            ·
            <a href="{{ url('/member/register') }}" style="color: var(--theme-accent);">注册</a>
        </p>
    </div>
@endsection
