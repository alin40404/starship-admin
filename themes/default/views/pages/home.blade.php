@extends('theme::layouts.app')

@section('title', ($theme['title'] ?? '默认主题').' — Starship')

@section('content')
    <section class="page-hero">
        <div class="container">
            <p class="page-hero__eyebrow">Hello Elementor style</p>
            <h1>{{ $theme['title'] ?? '默认主题' }}</h1>
            <p class="page-hero__lede">
                轻量默认前台主题：白底、站点顶栏与页脚、内容宽约 1140px，视觉参考 Hello Elementor。
                样式与图片通过 <code>theme_asset()</code> 从 <code>themes/default/assets</code> 加载。
            </p>
            <div class="btn-row">
                @auth('member')
                    <a class="btn btn--primary" href="{{ route('member.account') }}">会员中心</a>
                    <form method="post" action="{{ route('member.logout') }}">
                        @csrf
                        <button class="btn btn--ghost" type="submit">退出登录</button>
                    </form>
                @else
                    <a class="btn btn--primary" href="{{ route('member.login') }}">会员登录</a>
                    <a class="btn btn--ghost" href="{{ route('member.register') }}">注册账号</a>
                @endauth
            </div>
        </div>
    </section>

    <div class="container">
        <div class="panel">
            <ul class="meta-list">
                <li><span>主题 slug</span> · <strong data-theme-slug>{{ $theme['slug'] ?? 'default' }}</strong></li>
                <li><span>目录</span> · <strong>{{ $theme['directory'] ?? 'default' }}</strong></li>
                <li><span>类型</span> · <strong>{{ $theme['type'] ?? 'frontend' }}</strong></li>
            </ul>
        </div>
    </div>
@endsection
