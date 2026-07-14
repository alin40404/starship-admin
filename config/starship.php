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
    | Theme / Plugin 静态资源 CDN
    |--------------------------------------------------------------------------
    |
    |
    | 可选。填写后 theme_asset() / plugin_asset() 共用此前缀：
    |   {asset_url}/themes/{slug}/assets/{path}
    |   {asset_url}/plugins/{slug}/assets/{path}
    |
    | 未填写时回退 Laravel asset()（自动尊重 APP 的 ASSET_URL）。
    | CDN 桶内须保持相同路径前缀 themes/...、plugins/...。
    |
    | 兼容旧环境变量 STARSHIP_THEME_ASSET_URL（仅作回落）。
    |
    */

    'asset_url' => env('STARSHIP_ASSET_URL', env('STARSHIP_THEME_ASSET_URL')),

];
