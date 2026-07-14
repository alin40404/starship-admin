# Starship Theme 开发规范

## 1. Theme 定位

Theme 是展示层扩展。

负责：

-   页面
-   布局
-   样式
-   资源

不负责：

-   业务逻辑
-   数据处理
-   权限

> MVP 渲染使用 Blade（`ThemeLoader` + `theme::` 命名空间）；Vue 前台属后续能力，不在当前 Demo 范围。

------------------------------------------------------------------------

# 2. 使用场景

适合：

## CMS

    新闻主题

    政府主题

    企业官网主题

## 商城

    服装商城

    电子商城

    教育商城

------------------------------------------------------------------------

# 3. Theme目录结构

MVP（Blade）示例：

    themes/default/


    ├── theme.json


    ├── cover.svg


    └── views/

        ├── layouts/

        │   └── app.blade.php

        └── pages/

            └── home.blade.php

后续 Vue 前台可扩展 `layouts/`、`pages/`、`components/`、`assets/` 等（仍以 `theme.json` entry 声明入口）。

------------------------------------------------------------------------

# 4. theme.json规范

示例：

``` json
{
"name":"shop-fashion",

"title":"时尚商城主题",

"version":"1.0.0",

"type":"frontend",

"require":{

"modules":[
"Shop"
]

},

"entry":{

"layout":"layouts.app",

"home":"pages.home"

}

}
```

------------------------------------------------------------------------

# 5. Theme生命周期

    discover

    ↓

    install

    ↓

    activate

    ↓

    switch

    ↓

    uninstall

------------------------------------------------------------------------

# 6. Theme加载机制

流程：

    用户选择主题

    ↓

    ThemeManager

    ↓

    读取theme.json

    ↓

    加载Layout

    ↓

    加载页面

    ↓

    渲染

------------------------------------------------------------------------

# 7. 页面覆盖机制

推荐：

    Theme

    优先

    Module默认页面

例如：

Module：

    Shop/pages/product.vue

Theme覆盖：

    themes/fashion/pages/product.vue

加载：

    fashion主题页面

------------------------------------------------------------------------

# 8. Theme配置

例如：

    logo

    banner

    颜色

    字体

    首页模块

保存：

    sa_theme_configs

------------------------------------------------------------------------

# 9. Theme和Module关系

正确：

    Shop Module

    提供：

    商品API

    订单API


            |

    Shop Theme

    提供：

    商品展示

    首页展示

------------------------------------------------------------------------

# 10. 最终原则

Theme：

负责展示。

Module：

负责业务。

Plugin：

负责外部能力。

Core：

负责扩展规则。
