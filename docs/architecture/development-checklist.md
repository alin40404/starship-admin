# Starship Admin 开发清单

> 更新日期：2026-07-15  
> 目录约定：**保留 `modules/`**，近期不改为 `starship/`。  
> **当前优先**：完善 Plugin / Contract（Mail 等），**暂缓** Packagist / `release` 分支。

---

## 1. 架构共识（已确认）

| 项 | 决策 |
|----|------|
| 内核 | `packages/core` → Packagist `starship/core`（业务只读）；monorepo 开发期仍可用 path |
| 底座模块 | `modules/System`、`modules/Generator`，源码在仓库内，**近期不走 Packagist** |
| 业务模块 | 新建目录放 `modules/{Name}/`，通过 `module.json` 注册 |
| 插件 | `plugins/`，完整生命周期（发现 / 安装 / 启用 / 禁用 / 卸载） |
| 主题 | ThemeManager MVP 已落地；渲染层 / CMS Demo 待做 |
| Monitor | 已并入 System，API `/admin-api/monitor/*`，权限 `monitor.*` |
| 前端 feature 目录 | `apps/admin/web-antd/src/features/{module}`，与后端 Module 对应 |
| 模块导出/导入 | 先手工验收再做 CLI；清单见 [module-export-import-acceptance.md](../deployment/module-export-import-acceptance.md) |
| 暂不做 | 多租户 Module、Module/Plugin 市场、`modules/` 改名 |

---

## 2. 当前实现状态

| 能力 | 状态 | 说明 |
|------|------|------|
| ModuleManager | ✅ | 扫描 `module.json`、注册 Provider |
| Module 依赖校验 | ✅ | `require.modules` 启动时校验 |
| Plugin 发现 / 安装 / 启用 / 禁用 | ✅ | CLI + System 管理端 |
| Plugin 卸载 CLI | ✅ | `starship:plugin:uninstall` |
| `starship:module:make` | ✅ | 模块脚手架 |
| `starship:plugin:make` | ✅ | 插件脚手架 |
| Generator 端到端 Demo | ✅ | `sa_demo_products` 表 + CLI 全流程已验收 |
| Contract 体系 | ✅ | `SmsSenderInterface`、`StorageInterface` 已落地；System 默认实现可被插件覆盖 |
| Event / Hook | ✅ Event 主干 | `EventDispatcherInterface` + `EventDispatcher`；样例 `plugins/EventSample`。独立 Filter Hook 延后 |
| ThemeManager | ✅ | 发现/安装/启用/切换/卸载 + CLI |
| 脚手架 `starship/skeleton` | 🚧 | 开发仓兼发布：根 `composer.json` 已备；待维护 `release` 分支 |
| 框架 Feature Test | ✅ | `FrameworkSmokeTest`：ping / 模块 / 插件扫描 / Contract 绑定 |
| `packages/core` 包测试 | ✅ | `cd packages/core && composer test` |

图例：✅ 已完成 · 🚧 进行中 · ⚠️ 部分可用 · ❌ 未开始

---

## 3. 阶段划分

### 阶段一：底座夯实（当前，约 3～6 个月）

**目标**：Core + System + Generator + Plugin 生命周期闭环，新业务不进 `backend/app/`。

#### P0 — 插件与模块基础

- [x] Monitor 合并进 System
- [x] `starship:plugin:uninstall` CLI
- [x] SmsAliyun 插件完整闭环（发现 → 安装 → 启用 → 配置 → 禁用 → 卸载）
- [x] Module `require.modules` 启动依赖校验
- [x] `starship:module:make {Name}` 脚手架
- [x] Generator 一张表端到端 Demo 验收（`sa_demo_products`）

#### P1 — 文档与规范对齐

- [x] `starship:plugin:make` 脚手架
- [x] 同步 `docs/deployment/Starship_Module_开发规范.md` 与真实目录（Providers 在 `app/Providers/`）
- [x] API / 权限码文档与 `module.json` 保持一致（`docs/api/permissions.md`、`docs/api/routes.md`；System/Generator `menus.paths` 已补全）
- [x] System 插件管理页对接 uninstall
- [x] 插件静态资源 URL（`plugin_asset` / `starship:plugin:link`，对齐 Theme assets）

#### P2 — 框架产品化前置

- [x] 最小 Contract（`StorageInterface` ✅；`SmsSenderInterface` ✅；`MailSenderInterface` ✅）
- [x] 框架级 Feature Test（`php artisan test` 冒烟，仅 backend 应用壳）
- [x] `packages/core` 包测试（`cd packages/core && composer test`）
- [x] 项目模板约定：开发仓兼发布（根 `composer.json` + [release-as-template.md](./release-as-template.md)）
- [x] `starship/core` Packagist 约定（[packagist.md](./packagist.md)；待独立仓上架与首个 tag）
- [ ] 对外发布分支 `release`：core 改版本约束 + clone 可起步 — **暂缓**（先完善插件 Contract）

### 阶段一补充：插件 / Contract 完善（当前优先，暂缓 Packagist）

- [x] Sms：`SmsSenderInterface` + System `SmsGateway` + `plugins/SmsAliyun` 覆盖
- [x] Storage：`StorageInterface` + System `LocalStorage`
- [x] Mail：`MailSenderInterface` + System 默认实现 + 样例插件覆盖（本轮）
- [x] Contract 覆盖约定文档落地（Plugin 开发规范专节）
- [x] 插件配置 Schema（`config.schema.php` + 抽屉按类型渲染）
- [x] 插件配置自定义逃逸（pages.ui = schema/link/component；MailSmtp 高级页样例）
- [x] Event 主干（`EventDispatcherInterface` + `PluginDescriptionResolving`；`plugins/EventSample`）
- [ ] Filter Hook 体系（改变价格等链式改写；延后，可用可变载荷 Event 过渡）
- [x] 前端插件动态加载约定预留（`frontend` + `frontend-registry.ts`；运行时加载暂关）
- [ ] Payment / OAuth 等（延后）

### 阶段二：主题体系（底座稳定后，约 1～2 个月）

- [x] ThemeManager + `theme.json` 解析
- [x] 主题发现 / 启用 / 切换（`starship:theme:*` CLI；`themes` 表 `is_active`）
- [x] 后台主题管理页（`/dev/themes`，镜像插件管理）
- [x] 前台主题最小 Blade Demo（ThemeLoader + `GET /`；CMS/商城业务另计）
- [x] `themes/` 目录规范落地（`default` / `corporate` 种子 `theme.json`）

### 阶段二补充：会员模块（ThemeLoader 就绪后）

- [x] 独立 `modules/Member` MVP（不进 System）：`members` 表、`member` session guard、后台会员 CRUD（查询/启停/增删改）、前台登录/注册 Blade（Theme 可覆盖）
- [x] 模块导出/导入验收（Member 试点）：[module-export-import-acceptance.md](../deployment/module-export-import-acceptance.md)（2026-07-15：B 环境 `export` → `import --force --migrate --seed` 后台通过）
- [x] `starship:module:export` / `import` CLI：[module-cli.md](../deployment/module-cli.md)
- [ ] 第三方登录、会员等级/积分、商城订单绑定（明确延后）

### 阶段三：生态扩展（长期）

- [x] EventBus 主干（`EventDispatcher`；样例 `EventSample`）
- [ ] Filter Hook（辅助，延后）
- [ ] Contract 完整目录（`packages/core/Contract/`）
- [ ] 官方插件库（支付、存储、短信）
- [ ] 前端插件动态加载（约定已预留；`PLUGIN_FRONTEND_DYNAMIC_ENABLED` 默认关闭）
- [ ] 插件市场（可选）

### 明确不做（近期）

- [ ] `modules/` → `starship/` 目录改名
- [ ] Module Composer 包发布 / Module 市场
- [ ] 多租户 Tenant Module
- [ ] Plugin 承载大型业务模块

---

## 4. Sprint 任务明细（阶段一 P0）

### 4.1 Plugin 卸载 CLI

```
php artisan starship:plugin:uninstall {plugin}
```

- 调用 `PluginLifecycle::uninstall`
- 前置：插件须已禁用
- 输出：成功 / 失败原因

### 4.2 Module 依赖校验

`module.json` 示例：

```json
{
  "require": {
    "modules": ["system"]
  }
}
```

- 启动时校验所有 **enabled** 模块的 `require.modules`
- 缺失依赖抛 `InvalidArgumentException`，阻止静默启动

### 4.3 模块脚手架

```
php artisan starship:module:make Shop
```

生成：

```
modules/Shop/
├── composer.json
├── module.json
├── app/Providers/ShopServiceProvider.php
├── routes/admin.php
├── database/migrations/.gitkeep
└── resources/lang/zh-CN/messages.php
```

命名空间：`Modules\Shop\App\...`  
Composer 包名：`starship/shop`（需在 `backend/composer.json` 添加 path 仓库）

### 4.4 Generator 端到端 Demo

验收路径（已执行）：

1. 迁移建表 `sa_demo_products`（`modules/System/database/migrations/2026_07_13_100000_create_demo_products_table.php`）
2. `starship:generator:import demo_products --module=System --business=演示产品`
3. `starship:generator:crud demo_products --module=System --business=演示产品 --force`
4. 菜单 / 权限自动注册（menu:131～136）
5. 管理端访问 `/dev/products`（超管 `admin` 登录后可见）

> 修复：`SchemaReader::physicalTableName()` 解决 DB 前缀下 `inspect`/字段推断为空的问题。

### 4.5 SmsAliyun 插件闭环

验收路径（CLI + 自动化测试）：

1. `php artisan starship:plugin:discover`
2. `php artisan starship:plugin:install sms-aliyun`
3. `php artisan starship:plugin:enable sms-aliyun`（新 Web 请求自动加载路由）
4. 管理端 `/dev/plugins` 配置并测试发送
5. `php artisan starship:plugin:disable sms-aliyun`
6. `php artisan starship:plugin:uninstall sms-aliyun`

自动化：`cd packages/core && composer test`（`SmsAliyunPluginLifecycleTest`）。

---

## 5. 目录速查

```
starship-admin/
├── packages/core/          # 内核（ModuleManager、PluginEngine、CLI）
├── backend/                # Laravel 应用壳
├── modules/
│   ├── System/             # 系统 + 监控日志
│   └── Generator/          # 代码生成
├── plugins/
│   └── SmsAliyun/          # 短信插件示例
├── themes/                 # 占位
└── apps/admin/             # 管理端前端
```

---

## 6. 常用命令

```bash
# 模块
php artisan starship:module:make Erp

# 插件
php artisan starship:plugin:make SmsAliyun --type=sms
php artisan starship:plugin:discover
php artisan starship:plugin:install sms-aliyun
php artisan starship:plugin:enable sms-aliyun
php artisan starship:plugin:disable sms-aliyun
php artisan starship:plugin:uninstall sms-aliyun

# 代码生成
php artisan starship:generator:tables
php artisan starship:generator:inspect {table}
php artisan starship:generator:import {table}
php artisan starship:generator:crud {table}
```

---

## 7. 参考文档

- [framework.md](./framework.md) — 总体架构与演进路线
- [Starship_Module_Plugin_Theme_实现方案.md](./Starship_Module_Plugin_Theme_实现方案.md)
- [starship-framework-latest.md](./starship-framework-latest.md)
- [Starship_Module_开发规范.md](../deployment/Starship_Module_开发规范.md)
- [Starship_Plugin_开发规范.md](../deployment/Starship_Plugin_开发规范.md)
- [permissions.md](../api/permissions.md) — 权限码与 module.json 绑定
- [routes.md](../api/routes.md) — 管理端 API 路由参考
- [release-as-template.md](./release-as-template.md) — 开发仓兼发布仓约定
- [packagist.md](./packagist.md) — starship/core Packagist 与 subtree
