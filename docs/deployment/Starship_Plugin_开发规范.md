# Starship Plugin 开发规范

## 1. Plugin 定位

Plugin 是 Starship 的可插拔扩展能力。

主要解决：

-   第三方服务接入
-   可替换实现
-   平台能力扩展

典型：

    plugins/

    PaymentWechat

    PaymentAlipay

    StorageOSS

    SmsAliyun

    OpenAI

    OAuthGoogle

原则：

Plugin 不承载大型业务。

不要：

    plugins/Shop
    plugins/CRM

业务系统应该使用 Module。

------------------------------------------------------------------------

# 2. Plugin 类型规范

plugin.json 中 `type` 为英文标识；**中文文案由后端** `core::plugin.types.*` 维护（见 `packages/core/src/Plugin/PluginTypeCatalog.php`），接口下发 `type_label` / `types`。

约定类型示例：

    payment（支付方式）
    shipping（配送方式）
    marketing（营销推广）
    sms（短信）
    email（邮件）
    oauth（社交登录）
    storage（存储）
    ai（人工智能）
    theme（主题模板）
    analysis（数据分析）
    service（客户服务）
    feature（功能模块）
    other（其他功能）

示例：

    PaymentWechat

    type=payment

------------------------------------------------------------------------

# 3. Plugin 目录结构

**开发态（本仓库）：**

```text
plugins/MailSmtp/                      # PHP + 前台
├── plugin.json
├── cover.svg
├── config.schema.php
├── frontend/                          # 前台前端（站点 / 主题侧，可选）
├── backend/README.md                  # 仅占位：后台前端不在此开发
├── assets/
├── src/
├── config/
├── routes/
├── database/
├── resources/                         # 含 lang（后端语言包）
└── README.md

apps/admin/web-antd/src/plugins/       # 后台前端（与 features 同级）
├── registry.ts                        # glob('./*/panel.ts')
└── mail-smtp/
    ├── panel.ts
    ├── *.vue
    └── locales/{zh-CN,en-US}/
```

**导出包：** `plugin/` ← `plugins/{Studly}`；`backend/` ← `src/plugins/{slug}`。见 [plugin-cli.md](./plugin-cli.md)。

原则：

-   **要浏览器直接访问的** → 插件本体 `assets/`
-   **仅服务端使用的** → `resources/`
-   **管理端自定义 Vue** → `apps/admin/.../src/plugins/{slug}/`（可用 `#/` / `@vben`）；导出时进包内 `backend/`
-   **站点 / 主题侧 UI** → 插件内 `frontend/`
-   密钥、配置、源码不要放进 `assets/`

### 3.1 静态资源 URL

Blade / 后端生成 URL（相对 `assets/`）：

```php
plugin_asset('payment-wechat', 'images/logo.svg');
plugin_asset('payment-wechat', 'js/checkout.js');
```

-   URL 形如 `/plugins/{slug}/assets/images/logo.svg`
-   参数写 `images/logo.svg` 即可，不必带 `assets/` 前缀

| 场景 | 磁盘 | 谁响应 `/plugins/{slug}/assets/...` |
|------|------|-------------------------------------|
| 未 link | 仓库 `plugins/{slug}/assets` | Laravel `PluginAssetController` 回落 |
| 已 link（推荐） | `public/plugins/{slug}/assets` → 软链到源目录 | Web 服务器按静态文件访问；没有链再回落 Laravel |

创建软链接（在 `backend/`，类似 `starship:theme:link`）：

```bash
php artisan starship:plugin:link payment-wechat
php artisan starship:plugin:link --all
```

效果：

```text
backend/public/plugins/payment-wechat/assets  →  plugins/PaymentWechat/assets
```

`public/plugins` 已加入 `.gitignore`，各环境部署后执行一次 link。

> Windows 需开启「开发人员模式」，或以管理员身份运行，否则 `symlink` 可能失败（也会尝试 `mklink /J`）。

### 3.2 CDN / 静态域名（可选）

与 Theme 共用同一配置：

| 配置 | 环境变量 | 效果 |
|------|----------|------|
| Theme/Plugin CDN | `STARSHIP_ASSET_URL` | `{该域名}/plugins/{slug}/assets/...` |
| 全站静态域名 | Laravel `ASSET_URL` | `asset()` 自动加前缀（未设专用 CDN 时） |

```env
STARSHIP_ASSET_URL=https://cdn.example.com
```

CDN 桶内路径须与本地一致：`plugins/payment-wechat/assets/...`。

------------------------------------------------------------------------

# 4. plugin.json规范

示例：

``` json
{
"name":"PaymentWechat",

"slug":"payment-wechat",

"title":"微信支付",

"version":"1.0.0",

"type":"payment",

"cover":"cover.svg",

"namespace":"Plugins\\PaymentWechat",

"require":{

"modules":[
"System"
]

},

"providers":[

"Plugins\\PaymentWechat\\Providers\\ServiceProvider"

],

"migration":true

}
```

------------------------------------------------------------------------

# 5. Plugin生命周期

## Discover

扫描：

    plugins/*

读取：

    plugin.json

------------------------------------------------------------------------

## Install

执行：

-   创建插件记录
-   执行migration
-   发布配置

------------------------------------------------------------------------

## Enable

加载：

-   Provider
-   Route
-   Service

------------------------------------------------------------------------

## Disable

停止：

-   Provider加载
-   Route注册

------------------------------------------------------------------------

## Upgrade

执行：

    upgrade.php

------------------------------------------------------------------------

## Uninstall

执行：

-   清理配置
-   删除插件记录
-   执行卸载脚本

------------------------------------------------------------------------

# 6. Plugin 与 Contract 覆盖约定

能力替换走 **Core Contract + 容器绑定**，不要在 Module 里 `new` 具体插件类。

## 6.1 标准链路

```text
Module / System 业务代码
        │
        ▼
  app(XxxInterface::class)     ← 只依赖接口
        │
   ┌────┴────┐
   ▼         ▼
System     已启用 Plugin
默认绑定    ServiceProvider::register 里 singleton 覆盖
```

1. **System** 在 `SystemServiceProvider::register` 中 `singleton(Contract::class, DefaultImpl::class)`。
2. **Plugin** 启用后加载 Provider，再次 `singleton(Contract::class, PluginImpl::class)` **覆盖**默认实现。
3. **禁用 / 卸载** 后 Provider 不再加载；下一请求由 System 默认实现承接（无残留绑定）。
4. 业务发送入口（短信、邮件、存储等）一律注入 Contract，禁止写死 `Plugins\Foo\...`。

参考：`SmsSenderInterface` + `SmsGateway` + `plugins/SmsAliyun`；`MailSenderInterface` + `MailGateway` + `plugins/MailSmtp`。

## 6.2 plugin.json

- `type` 与能力一致（如 `sms` / `email` / `storage`）。
- `providers` 中完成 Contract 绑定。
- 需要管理端配置 / 试发时声明 `config` 与 `pages`（`config` / `test`）。
- `pages[].ui`：`schema`（默认）| `link` | `component` | `slots`（预留）。
- `frontend`（可选，L3 预留）：`{ "entry": "#/plugins/{slug}", "routes": [] }`。与导出包目录 `backend/`、插件内目录 `frontend/`（前台）不是同一概念。

## 6.3 脚手架提示

`starship:plugin:make` 生成的 ServiceProvider `register()` 内含 Contract 绑定注释示例，并生成 `config.schema.php` 模板。

------------------------------------------------------------------------

# 6A. 配置 Schema（对齐 FastAdmin / BeikeShop）

默认值仍在 `config/{slug}.php`；表单描述放在插件根目录：

- 优先：`config.schema.php`
- 兼容：`columns.php`（BeikeShop 命名）

无 Schema 文件时，管理端按默认配置的 **key 列表**回退为 `string` 输入框。

字段形状示例：

```php
[
    'key' => 'host',
    'label' => __('mail-smtp::messages.fields.host_label'),
    'type' => 'string', // string|password|number|select|switch|textarea
    'options' => [],
    'default' => '',
    'rules' => ['required'],
    'help' => __('mail-smtp::messages.fields.host_help'),
    'group' => 'connection',
    'encrypted' => false,
]
```

文案放在 `resources/lang/{locale}/messages.php`。加载 `config.schema.php` **之前**会 `addNamespace(插件slug)`，因此可直接写 `__('slug::messages...')`。仍兼容语言包键字符串与内联 `['zh_CN'=>..., 'en'=>...]`。

API `config_schema` 返回**对象数组**（`label`/`help` 已是当前语言文案）。保存仍写入 `plugin_configs` 表。

参考：`plugins/MailSmtp/config.schema.php`、`plugins/SmsAliyun/config.schema.php`。

------------------------------------------------------------------------

# 6B. 自定义配置逃逸（SPA）

| ui | 行为 |
|----|------|
| `schema` | 抽屉内按 Schema 渲染（默认） |
| `link` | 抽屉内「打开链接」→ `pages[].href` |
| `component` | 抽屉内渲染 `pages[].component`；实现放在 **`src/plugins/{slug}/`**，由 [`registry.ts`](../../apps/admin/web-antd/src/plugins/registry.ts) glob 自动登记；导出包目录名为 `backend/` |
| `slots` | 预留菜单注入 |

路径约定：

```text
apps/admin/web-antd/src/plugins/{slug}/
├── panel.ts
├── *.vue
└── locales/{zh-CN,en-US}/{slug}.json
```

`id` 必须等于 `plugin.json` 的 `pages[].component`。样例：MailSmtp → `src/plugins/mail-smtp/`。

**语言包：**

| 侧 | 路径 | 用法 |
|----|------|------|
| 后端 PHP | `plugins/{Studly}/resources/lang/{zh_CN\|en}/messages.php` | `__('{slug}::messages.x')` |
| 后台前端 | `src/plugins/{slug}/locales/{zh-CN\|en-US}/{slug}.json` | `$t('{slug}.x')` / `#/locales` |
| 前台前端 | `plugins/{Studly}/frontend/` | 按站点 / 主题约定 |

------------------------------------------------------------------------

# 6C. Event（主干，对齐设计方案）

扩展业务解耦以 **Laravel Event** 为主（见 [Starship_Event_Hook_事件扩展体系设计方案.md](./Starship_Event_Hook_事件扩展体系设计方案.md)）。

Core：

- `EventDispatcherInterface` → `EventDispatcher`（对接框架事件总线）
- 事件类目录：`packages/core/src/Events/`（过去式命名，表示事实；Resolving 类用于可改写载荷）

| 场景 | 用法 |
|------|------|
| 换短信/邮件/存储实现 | **Contract** 容器覆盖 |
| 业务事实发生后通知多方 | **Event** + Listener |
| 链式改写中间结果（如价格） | 可先用「可变载荷 Event」；独立 Filter Hook 延后 |

系统示例：组装插件描述时派发 `PluginDescriptionResolving`。  
样例插件：`plugins/EventSample`（启用后监听该事件，给 MailSmtp 描述追加标记）。

插件注册监听：

```php
Event::listen(PluginDescriptionResolving::class, function (PluginDescriptionResolving $event): void {
    // 可修改 $event->description
});
```

------------------------------------------------------------------------

# 6D. 前端插件动态加载（约定预留）

- `plugin.json.frontend.entry` / `routes` 已可声明。
- 管理端 [`frontend-registry.ts`](../../apps/admin/web-antd/src/plugins/frontend-registry.ts) 记录约定；`PLUGIN_FRONTEND_DYNAMIC_ENABLED = false`。
- **当前 L2**：`src/plugins/*/panel.ts` 经 `registry.ts` glob 登记；导出进包内 `backend/`（见 [plugin-cli.md](./plugin-cli.md)）。
- **远期 L3**：按已启用插件的 `frontend.entry` 动态 import（插件市场另议）。

------------------------------------------------------------------------

# 7. Plugin接口设计

例如支付：

定义：

    PaymentInterface

Core：

``` php
interface PaymentInterface
{

public function pay();

public function refund();

public function query();

}
```

插件实现：

    WechatPayment

    AlipayPayment

    StripePayment

------------------------------------------------------------------------

# 8. Plugin配置管理

统一存储：`plugin_configs` 表（见 §6A Schema）。

例如：`mail-smtp` / `host`、`password`（敏感字段可加密）。

不要：插件自己另起一套配置持久化。

管理端 UI：优先 Schema 自动表单；复杂页用 §6B 逃逸。

------------------------------------------------------------------------

# 9. Plugin事件通信

不要：

    OrderService 直接调用 SmsPlugin 具体类

应该：

    通道实现 → Contract；业务解耦 → Event / Listener

------------------------------------------------------------------------

# 10. Plugin安全规范

第三方插件：

需要：

-   权限声明
-   版本声明
-   依赖声明
-   配置隔离

------------------------------------------------------------------------

# 11. 最终原则

Plugin：

负责连接世界。

Module：

负责业务。

Core：

负责规则。
