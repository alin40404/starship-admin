@extends('theme::layouts.app')

@section('content')
    <div class="brand">Starship · Default</div>
    <h1>{{ $theme['title'] ?? '默认主题' }}</h1>
    <p class="lede">
        这是默认前台主题首页（Blade Demo）。后台切换主题后刷新本页即可看到差异。
    </p>
    <div class="panel">
        <ul class="meta">
            <li><span>主题 slug</span> · <strong data-theme-slug>{{ $theme['slug'] ?? 'default' }}</strong></li>
            <li><span>目录</span> · <strong>{{ $theme['directory'] ?? 'default' }}</strong></li>
            <li><span>类型</span> · <strong>{{ $theme['type'] ?? 'frontend' }}</strong></li>
        </ul>
    </div>
@endsection
