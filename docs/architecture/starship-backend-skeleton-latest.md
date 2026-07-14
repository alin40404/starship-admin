# starship/skeleton backend 文件清单

> backend 是 Laravel 薄壳。
>
> 原则： backend 只负责入口、配置、生命周期。 业务代码全部进入 Module。

------------------------------------------------------------------------

# 1. 目标结构

    my-admin/

    backend/
    modules/
    plugins/
    themes/
    apps/admin/

    composer.json
    README.md

------------------------------------------------------------------------

# 2. backend 职责

负责：

-   Laravel 生命周期
-   HTTP入口
-   配置
-   注册 Core

不负责：

-   业务Controller
-   业务Service
-   Module代码
-   Plugin代码

------------------------------------------------------------------------

# 3. backend 保留

## bootstrap

    bootstrap/app.php

    bootstrap/providers.php

注册：

    CoreServiceProvider

------------------------------------------------------------------------

## app

只保留：

    app/

    Http/Controllers/Controller.php

    Models/User.php

    Providers/AppServiceProvider.php

------------------------------------------------------------------------

## routes

保留：

    routes/web.php

    routes/console.php

    routes/admin.php

业务路由：

    modules/*/routes/admin.php
    plugins/*/routes/admin.php

------------------------------------------------------------------------

# 4. Module / Plugin / Theme边界

结构：

    backend

        |
        + modules
        |
        + plugins
        |
        + themes

Module：

业务能力。

Plugin：

外部扩展。

Theme：

前台展示。

------------------------------------------------------------------------

# 5. 数据库归属

backend：

只放 Laravel 宿主表：

    users
    cache
    jobs
    personal_access_tokens

Module：

自己的迁移。

Plugin：

自己的迁移。

Theme：

通常无迁移。

------------------------------------------------------------------------

# 6. 初始化

    composer install

    php artisan migrate --seed

    php artisan storage:link

插件：

    php artisan starship:plugin:install xxx

主题：

    php artisan starship:theme:install xxx

------------------------------------------------------------------------

# 7. Laravel升级策略

升级Laravel：

主要关注：

-   backend
-   composer.json

Core、Module、Plugin保持独立。

------------------------------------------------------------------------

# 8. 最终原则

backend保持薄。

业务进入Module。

外部能力进入Plugin。

展示进入Theme。
