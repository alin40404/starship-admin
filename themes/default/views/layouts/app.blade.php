<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $theme['title'] ?? 'Starship')</title>
    <link rel="stylesheet" href="{{ theme_asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/header-footer.css') }}">
    @stack('head')
</head>
<body class="@yield('body_class')">
    <div class="site">
        @include('theme::partials.header')

        <main class="site-content">
            @yield('content')
        </main>

        @include('theme::partials.footer')
    </div>
</body>
</html>
