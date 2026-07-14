<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Repository Root
    |--------------------------------------------------------------------------
    |
    | Monorepo 根目录（含 modules/、apps/）。Laravel 应用在 backend/ 时默认为 dirname(base_path())。
    |
    */

    'repo_root' => env('STARSHIP_REPO_ROOT', dirname(base_path())),

    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    |
    | 底座模块目录，默认指向仓库根目录下的 modules/。
    |
    */

    'modules_path' => env('STARSHIP_MODULES_PATH', dirname(base_path()).'/modules'),

    /*
    |--------------------------------------------------------------------------
    | Plugins Path
    |--------------------------------------------------------------------------
    |
    | 插件目录，默认指向仓库根目录下的 plugins/。
    |
    */

    'plugins_path' => env('STARSHIP_PLUGINS_PATH', dirname(base_path()).'/plugins'),

    /*
    |--------------------------------------------------------------------------
    | Themes Path
    |--------------------------------------------------------------------------
    |
    | 主题目录，默认指向仓库根目录下的 themes/。
    |
    */

    'themes_path' => env('STARSHIP_THEMES_PATH', dirname(base_path()).'/themes'),

    /*
    |--------------------------------------------------------------------------
    | Admin Frontend src
    |--------------------------------------------------------------------------
    |
    | 管理端 Vue 源码根（含 features/）。默认 apps/admin/web-antd/src。
    |
    */

    'admin_frontend_src' => env(
        'STARSHIP_ADMIN_FRONTEND_SRC',
        dirname(base_path()).'/apps/admin/web-antd/src'
    ),

    /*
    |--------------------------------------------------------------------------
    | Default / Fallback Themes
    |--------------------------------------------------------------------------
    |
    | 无 DB 激活记录时的回退主题（slug）。生产以 themes.is_active 为准。
    |
    */

    'default_theme' => env('STARSHIP_DEFAULT_THEME', 'default'),

    'fallback_themes' => [
        'frontend' => env('STARSHIP_FRONTEND_THEME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Asset CDN / 静态域名
    |--------------------------------------------------------------------------
    |
    | 可选。填写后 theme_asset() 使用：
    |   {theme_asset_url}/themes/{slug}/assets/{path}
    | 例如：https://cdn.example.com 或 https://cdn.example.com/static
    |
    | 未填写时回退 Laravel asset()（自动尊重 APP 的 ASSET_URL）。
    | CDN 上须保持相同路径：themes/{slug}/assets/...
    |
    */

    'theme_asset_url' => env('STARSHIP_THEME_ASSET_URL'),

];
