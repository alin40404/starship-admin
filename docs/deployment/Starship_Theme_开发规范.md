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
>
> 语言列表与切换入口属于 System 模块（`config('system.locales')`、`GET /locale/{locale}`），不在 Core pack。Core 仅通过 `ThemeLoader` 注册当前主题的 `lang/` 为 `theme::` 命名空间；UI 文案放在各主题 `lang/{locale}/`。

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

```text
themes/default/
├── theme.json
├── cover.svg                 # 管理端封面（API 直读）
├── lang/                     # 可选：UI 文案（theme::），按 locale 分目录
│   ├── zh_CN/
│   └── en/
├── assets/                   # 前台 JS / CSS / 图片
│   ├── css/
│   │   ├── app.css
│   │   └── header-footer.css
│   ├── js/                   # 可选
│   └── images/
│       └── logo.svg
└── views/
    ├── layouts/
    │   └── app.blade.php
    ├── partials/
    │   ├── header.blade.php
    │   └── footer.blade.php
    └── pages/
        ├── home.blade.php
        └── member/…          # 可选：覆盖模块前台页
```

Blade 中引用资源（相对 `assets/`）：

```blade
<link rel="stylesheet" href="{{ theme_asset('css/app.css') }}">
<img src="{{ theme_asset('images/logo.svg') }}" alt="">
```

- URL 形如 `/themes/{slug}/assets/css/app.css`  
- 参数写 `css/app.css` 即可，不必带 `assets/` 或主题 slug  

### 3.1 开发 vs 生产（assets）

| 场景 | 磁盘 | 谁响应 `/themes/{slug}/assets/...` |
|------|------|-------------------------------------|
| 未 link | 仓库 `themes/{slug}/assets` | Laravel `ThemeAssetController` 回落 |
| 已 link（推荐） | `public/themes/{slug}/assets` → 软链到源目录 | Web 服务器按静态文件访问；没有链再回落 Laravel |

创建软链接（在 `backend/`，类似 `storage:link`）：

```bash
php artisan starship:theme:link default
php artisan starship:theme:link --all
```

效果：

```text
backend/public/themes/default/assets  →  themes/default/assets
```

改源码 assets 即时生效，无需复制。`public/themes` 已加入 `.gitignore`，各环境部署后执行一次 link。

> Windows 需开启「开发人员模式」，或以管理员身份运行，否则 `symlink` 可能失败。

生产环境将站点根目录指向 `backend/public`，并对 `/themes/` 优先按静态文件提供即可（未命中再交 Laravel）。  
本地 `php artisan serve`：即使已 link，也可能仍走 Laravel 回落，属正常。

### 3.2 CDN / 静态域名（可选）

`theme_asset()` / `plugin_asset()` 共用前缀：

| 配置 | 环境变量 | 效果 |
|------|----------|------|
| Theme/Plugin CDN | `STARSHIP_ASSET_URL` | `{该域名}/themes|plugins/{slug}/assets/...` |
| 全站静态域名 | Laravel `ASSET_URL` | `asset()` 自动加前缀（未设专用 CDN 时） |

示例：

```env
# 仅 Theme / Plugin 资源走 CDN（推荐）
STARSHIP_ASSET_URL=https://cdn.example.com

# 或全站静态前缀（后台 build、主题、插件等一并）
ASSET_URL=https://cdn.example.com
```

CDN 桶内路径须与本地一致：`themes/default/assets/css/app.css`。  
同步方式自定（CI 上传、OSS 镜像、对象存储绑定等）；应用侧只换域名，Blade 仍写 `theme_asset('css/app.css')`。

后续 Vue 前台可扩展 `components/` 等（仍以 `theme.json` entry 声明入口）。

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

    enable / switch

    ↓

    uninstall

跨站点搬运：`starship:theme:export` / `import`（见 [theme-cli.md](./theme-cli.md)）。

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
