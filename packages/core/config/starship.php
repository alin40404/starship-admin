<?php

return [

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

];
