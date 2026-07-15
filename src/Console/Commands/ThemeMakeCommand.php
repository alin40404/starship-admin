<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ThemeMakeCommand extends Command
{
    protected $signature = 'starship:theme:make
                            {name : 主题名称或 slug（如 Acme / acme-landing）}
                            {--slug= : 主题目录与 slug，默认由名称推导为 kebab-case}
                            {--title= : 展示标题}
                            {--description= : 主题描述}
                            {--force : 覆盖已存在的脚手架文件}';

    protected $description = '创建新的 Starship 主题脚手架（Blade + assets）';

    public function handle(): int
    {
        $raw = trim((string) $this->argument('name'));
        if ($raw === '') {
            $this->components->error('主题名称不能为空');

            return self::FAILURE;
        }

        $slug = trim((string) $this->option('slug'));
        if ($slug === '') {
            $slug = Str::kebab($raw);
        }
        $slug = Str::lower($slug);

        if (! preg_match('/^[a-z][a-z0-9\-]*$/', $slug)) {
            $this->components->error('slug 须为小写字母开头，仅含 a-z、0-9、-');

            return self::FAILURE;
        }

        $reserved = ['default']; // 可覆盖但提醒；corporate 也可新建
        if (in_array($slug, ['backend', 'public', 'vendor'], true)) {
            $this->components->error("保留 slug 不可用：{$slug}");

            return self::FAILURE;
        }

        $title = trim((string) $this->option('title'));
        if ($title === '') {
            $title = Str::headline(str_replace('-', ' ', $slug));
        }

        $name = Str::studly(str_replace('-', ' ', $slug));

        $description = trim((string) $this->option('description'));
        if ($description === '') {
            $description = "Starship {$title} 前台主题";
        }

        $themesPath = rtrim((string) config('starship.themes_path'), '/\\');
        $themePath = $themesPath.DIRECTORY_SEPARATOR.$slug;

        if (File::isDirectory($themePath) && ! $this->option('force')) {
            $this->components->error("主题目录已存在：{$themePath}，使用 --force 覆盖关键文件");

            return self::FAILURE;
        }

        try {
            $this->scaffoldTheme($themePath, $name, $slug, $title, $description);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('主题已创建');
        $this->line('  '.$themePath);
        $this->newLine();
        $this->components->twoColumnDetail('名称', $name);
        $this->components->twoColumnDetail('Slug', $slug);
        $this->components->twoColumnDetail('标题', $title);
        if (in_array($slug, $reserved, true)) {
            $this->components->warn('注意：正在脚手架内置主题目录 default');
        }
        $this->newLine();
        $this->components->info('建议下一步');
        $this->components->bulletList([
            "php artisan starship:theme:install {$slug}",
            "php artisan starship:theme:enable {$slug}",
            "php artisan starship:theme:link {$slug}",
            '在管理端「主题管理」中查看；访问前台 / 预览',
        ]);

        return self::SUCCESS;
    }

    protected function scaffoldTheme(
        string $themePath,
        string $name,
        string $slug,
        string $title,
        string $description,
    ): void {
        File::ensureDirectoryExists($themePath.'/assets/css');
        File::ensureDirectoryExists($themePath.'/assets/images');
        File::ensureDirectoryExists($themePath.'/lang/zh_CN');
        File::ensureDirectoryExists($themePath.'/lang/en');
        File::ensureDirectoryExists($themePath.'/views/layouts');
        File::ensureDirectoryExists($themePath.'/views/partials');
        File::ensureDirectoryExists($themePath.'/views/pages');

        $force = (bool) $this->option('force');

        $this->writeFile(
            "{$themePath}/theme.json",
            $this->themeJson($name, $slug, $title, $description),
            $force,
        );
        $this->writeFile("{$themePath}/cover.svg", $this->coverSvg($title), $force);
        $this->writeFile("{$themePath}/assets/images/logo.svg", $this->logoSvg(), $force);
        $this->writeFile("{$themePath}/assets/css/app.css", $this->appCss(), $force);
        $this->writeFile("{$themePath}/assets/css/header-footer.css", $this->headerFooterCss(), $force);
        $this->writeFile("{$themePath}/lang/zh_CN/ui.php", $this->langZh($title), $force);
        $this->writeFile("{$themePath}/lang/en/ui.php", $this->langEn($title), $force);
        $this->writeFile("{$themePath}/views/layouts/app.blade.php", $this->layoutBlade(), $force);
        $this->writeFile("{$themePath}/views/partials/header.blade.php", $this->headerBlade($title), $force);
        $this->writeFile("{$themePath}/views/partials/footer.blade.php", $this->footerBlade(), $force);
        $this->writeFile("{$themePath}/views/pages/home.blade.php", $this->homeBlade(), $force);
    }

    protected function writeFile(string $path, string $contents, bool $force): void
    {
        if (File::exists($path) && ! $force) {
            throw new InvalidArgumentException("文件已存在：{$path}");
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    protected function themeJson(string $name, string $slug, string $title, string $description): string
    {
        $payload = [
            'name' => $name,
            'slug' => $slug,
            'title' => $title,
            'version' => '1.0.0',
            'type' => 'frontend',
            'description' => $description,
            'cover' => 'cover.svg',
            'require' => [
                'modules' => [],
            ],
            'entry' => [
                'layout' => 'layouts.app',
                'home' => 'pages.home',
            ],
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    }

    protected function coverSvg(string $title): string
    {
        $safe = htmlspecialchars($title, ENT_QUOTES | ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360" role="img">
  <rect width="640" height="360" fill="#0f172a"/>
  <rect x="40" y="40" width="560" height="280" rx="16" fill="#1e293b"/>
  <text x="320" y="190" text-anchor="middle" fill="#e2e8f0" font-family="system-ui,sans-serif" font-size="28">{$safe}</text>
</svg>

SVG;
    }

    protected function logoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none">
  <rect width="28" height="28" rx="6" fill="#2563eb"/>
  <path d="M8 14h12M14 8v12" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
</svg>

SVG;
    }

    protected function appCss(): string
    {
        return <<<'CSS'
:root {
  --ss-bg: #f8fafc;
  --ss-fg: #0f172a;
  --ss-muted: #64748b;
  --ss-primary: #2563eb;
  --ss-card: #ffffff;
  --ss-border: #e2e8f0;
  --ss-max: 1140px;
}

* { box-sizing: border-box; }

body {
  margin: 0;
  font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
  color: var(--ss-fg);
  background: var(--ss-bg);
  line-height: 1.6;
}

.container {
  width: min(100% - 2rem, var(--ss-max));
  margin-inline: auto;
}

.site-content { min-height: 50vh; }

.page-hero {
  padding: 3.5rem 0 2.5rem;
  background: linear-gradient(180deg, #eef2ff 0%, var(--ss-bg) 100%);
}

.page-hero__eyebrow {
  margin: 0 0 0.5rem;
  color: var(--ss-primary);
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.page-hero h1 {
  margin: 0 0 0.75rem;
  font-size: clamp(1.75rem, 4vw, 2.5rem);
  line-height: 1.2;
}

.page-hero__lede {
  margin: 0 0 1.25rem;
  max-width: 40rem;
  color: var(--ss-muted);
}

.btn-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.55rem 1rem;
  border-radius: 0.5rem;
  border: 1px solid transparent;
  font: inherit;
  text-decoration: none;
  cursor: pointer;
}

.btn--primary {
  color: #fff;
  background: var(--ss-primary);
}

.btn--ghost {
  color: var(--ss-fg);
  background: transparent;
  border-color: var(--ss-border);
}

.panel {
  margin: 1.5rem 0 3rem;
  padding: 1.25rem 1.5rem;
  background: var(--ss-card);
  border: 1px solid var(--ss-border);
  border-radius: 0.75rem;
}

.meta-list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.meta-list li {
  padding: 0.35rem 0;
  color: var(--ss-muted);
  font-size: 0.925rem;
}

.meta-list strong { color: var(--ss-fg); }

CSS;
    }

    protected function headerFooterCss(): string
    {
        return <<<'CSS'
.site-header {
  border-bottom: 1px solid var(--ss-border);
  background: var(--ss-card);
}

.site-header__inner {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
  justify-content: space-between;
  min-height: 4rem;
}

.site-brand {
  display: inline-flex;
  gap: 0.6rem;
  align-items: center;
  color: inherit;
  text-decoration: none;
  font-weight: 650;
}

.site-brand__logo { display: block; border-radius: 6px; }

.site-brand__tagline {
  color: var(--ss-muted);
  font-size: 0.8rem;
  font-weight: 500;
}

.site-nav {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem 1rem;
  align-items: center;
  margin: 0;
  padding: 0;
  list-style: none;
}

.site-nav a,
.site-nav button {
  color: var(--ss-muted);
  background: none;
  border: 0;
  font: inherit;
  text-decoration: none;
  cursor: pointer;
}

.site-nav a.is-active,
.site-nav a:hover,
.site-nav button:hover {
  color: var(--ss-fg);
}

.site-nav__logout { display: inline; margin: 0; }

.site-footer {
  margin-top: 2rem;
  padding: 1.5rem 0;
  border-top: 1px solid var(--ss-border);
  color: var(--ss-muted);
  font-size: 0.875rem;
}

CSS;
    }

    protected function langZh(string $title): string
    {
        $titleExport = var_export($title, true);

        return <<<PHP
<?php

return [
    'nav_home' => '首页',
    'nav_login' => '登录',
    'nav_register' => '注册',
    'nav_account' => '会员中心',
    'nav_logout' => '退出',
    'member_login' => '会员登录',
    'member_register' => '注册账号',
    'locale_zh_CN' => '中文',
    'locale_en' => 'EN',
    'home_eyebrow' => 'Theme scaffold',
    'home_title' => {$titleExport},
    'home_lede' => '由 starship:theme:make 生成的最小 Blade 主题，可继续改布局与样式。',
    'meta_slug' => '主题 slug',
    'meta_directory' => '目录',
    'meta_type' => '类型',
    'footer_rights' => '版权所有',
    'auth_logout' => '退出登录',
];

PHP;
    }

    protected function langEn(string $title): string
    {
        $titleExport = var_export($title, true);

        return <<<PHP
<?php

return [
    'nav_home' => 'Home',
    'nav_login' => 'Login',
    'nav_register' => 'Register',
    'nav_account' => 'Account',
    'nav_logout' => 'Log out',
    'member_login' => 'Member login',
    'member_register' => 'Create account',
    'locale_zh_CN' => '中文',
    'locale_en' => 'EN',
    'home_eyebrow' => 'Theme scaffold',
    'home_title' => {$titleExport},
    'home_lede' => 'Minimal Blade theme from starship:theme:make. Customize layouts and styles as needed.',
    'meta_slug' => 'Theme slug',
    'meta_directory' => 'Directory',
    'meta_type' => 'Type',
    'footer_rights' => 'All rights reserved',
    'auth_logout' => 'Log out',
];

PHP;
    }

    protected function layoutBlade(): string
    {
        return <<<'BLADE'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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

BLADE;
    }

    protected function headerBlade(string $title): string
    {
        $tagline = htmlspecialchars(Str::limit($title, 24), ENT_QUOTES, 'UTF-8');

        return <<<BLADE
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
            <span>{{ \$theme['title'] ?? 'Starship' }}</span>
            <span class="site-brand__tagline">{$tagline}</span>
        </a>
        <ul class="site-nav">
            <li>
                <a href="{{ url('/') }}" @class(['is-active' => request()->is('/')])>{{ __('theme::ui.nav_home') }}</a>
            </li>
            @auth('member')
                <li>
                    <a
                        href="{{ route('member.account') }}"
                        @class(['is-active' => request()->is('member/account')])
                    >{{ __('theme::ui.nav_account') }}</a>
                </li>
                <li>
                    <form class="site-nav__logout" method="post" action="{{ route('member.logout') }}">
                        @csrf
                        <button type="submit">{{ __('theme::ui.nav_logout') }}</button>
                    </form>
                </li>
            @else
                <li>
                    <a href="{{ route('member.login') }}">{{ __('theme::ui.nav_login') }}</a>
                </li>
                <li>
                    <a href="{{ route('member.register') }}">{{ __('theme::ui.nav_register') }}</a>
                </li>
            @endauth
        </ul>
    </div>
</header>

BLADE;
    }

    protected function footerBlade(): string
    {
        return <<<'BLADE'
<footer class="site-footer">
    <div class="container">
        <p>&copy; {{ date('Y') }} {{ $theme['title'] ?? 'Starship' }}. {{ __('theme::ui.footer_rights') }}</p>
    </div>
</footer>

BLADE;
    }

    protected function homeBlade(): string
    {
        return <<<'BLADE'
@extends('theme::layouts.app')

@section('title', (__('theme::ui.home_title')).' — Starship')

@section('content')
    <section class="page-hero">
        <div class="container">
            <p class="page-hero__eyebrow">{{ __('theme::ui.home_eyebrow') }}</p>
            <h1>{{ __('theme::ui.home_title') }}</h1>
            <p class="page-hero__lede">
                {{ __('theme::ui.home_lede') }}
            </p>
            <div class="btn-row">
                @auth('member')
                    <a class="btn btn--primary" href="{{ route('member.account') }}">{{ __('theme::ui.nav_account') }}</a>
                    <form method="post" action="{{ route('member.logout') }}">
                        @csrf
                        <button class="btn btn--ghost" type="submit">{{ __('theme::ui.auth_logout') }}</button>
                    </form>
                @else
                    <a class="btn btn--primary" href="{{ route('member.login') }}">{{ __('theme::ui.member_login') }}</a>
                    <a class="btn btn--ghost" href="{{ route('member.register') }}">{{ __('theme::ui.member_register') }}</a>
                @endauth
            </div>
        </div>
    </section>

    <div class="container">
        <div class="panel">
            <ul class="meta-list">
                <li><span>{{ __('theme::ui.meta_slug') }}</span> · <strong data-theme-slug>{{ $theme['slug'] ?? '' }}</strong></li>
                <li><span>{{ __('theme::ui.meta_directory') }}</span> · <strong>{{ $theme['directory'] ?? '' }}</strong></li>
                <li><span>{{ __('theme::ui.meta_type') }}</span> · <strong>{{ $theme['type'] ?? 'frontend' }}</strong></li>
            </ul>
        </div>
    </div>
@endsection

BLADE;
    }
}
