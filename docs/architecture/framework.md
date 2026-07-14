# Starship Admin 框架架构

> 本文定义 Starship Admin 作为**可复用中后台底座**（对标 FastAdmin / BuildAdmin）的分层模型、扩展规范与演进路线。  
> 与 [README.md](../../README.md) 的关系：README 面向使用者总览，本文面向框架设计与二开边界。

---

## 1. 框架定位

Starship Admin 不是某个具体业务系统，而是一套：

- **可安装**：新项目基于脚手架快速启动
- **可扩展**：通过模块（Module）与插件（Plugin）增加能力
- **可生成**：Generator 根据数据表产出前后端 CRUD
- **可维护**：Laravel 壳保持薄，内核与业务边界清晰

目标用户场景：

| 场景 | 做法 |
|------|------|
| 企业内部 ERP | 复用 System（含监控日志），新增 `modules/Erp` |
| 商城运营后台 | 复用底座，新增 `modules/Shop` + 支付/短信插件 |
| SaaS 管理端 | 复用底座，独立业务仓库 + 引用 Starship 包 |
| 第三方服务接入 | 仅写 `plugins/StorageQiniu` 等适配器 |

---

## 2. 四层架构

```
Layer 4  业务实例     my-shop-admin、cms-admin（独立仓库或 solutions/）
    ↓
Layer 3  可选插件     plugins/SmsAliyun、plugins/StorageQiniu
    ↓
Layer 2  底座模块     modules/System、Generator（可开关）
    ↓
Layer 1  框架内核     packages/core（Module/Plugin 引擎、契约、ApiResponse）
    ↓
Layer 0  Laravel 壳  backend/（bootstrap、config、artisan，尽量保持官方骨架）
```

### 2.1 Layer 0 — Laravel 壳（`backend/`）

**职责**：HTTP 入口、环境配置、Laravel 生命周期。

**允许存放**：

- `bootstrap/app.php`、`config/*`、`routes/admin.php`（极薄入口）
- `AppServiceProvider`（仅注册 Core）
- `User` 模型、空 `Controller` 基类
- 宿主级迁移（`users`、`cache`、`jobs`、`personal_access_tokens`）

**禁止存放**：

- 业务 Controller / Service
- 插件运行时逻辑（应放在 `packages/core`）
- 与具体业务绑定的 Model

**Laravel 升级策略**：大版本时主要 diff 本层 + `composer.json` 框架约束。

### 2.2 Layer 1 — 框架内核（`packages/core`）✅ 已迁入

**职责**：模块扫描、插件引擎、统一响应、扩展契约、全局辅助函数。

| 原路径（已删除） | 现路径 |
|----------|----------|
| `backend/app/Support/Module/*` | `packages/core/src/Module/` |
| `backend/app/Foundation/Plugin/*` | `packages/core/src/Plugin/` |
| `backend/app/Support/ApiResponse.php` | `packages/core/src/Support/ApiResponse.php` |
| `backend/app/helpers.php` | `packages/core/src/helpers.php` |
| `backend/app/Console/Commands/Plugin*.php` | `packages/core/src/Console/Commands/` |
| `backend/app/Providers/ModuleServiceProvider.php` | `packages/core/src/Providers/CoreServiceProvider.php` |
| `backend/app/Providers/PluginServiceProvider.php` | ↑ 合并至 CoreServiceProvider |
| `modules/System/app/Models/Plugin*.php` | `packages/core/src/Models/` |

**内核依赖原则**：

- 可依赖 `illuminate/*`（Laravel 组件）
- **不得**依赖 `Modules\System\*` 等业务命名空间
- 对持久化需求通过 **Contract 接口** 注入（见 §4）

### 2.3 Layer 2 — 底座模块（`modules/`）

**职责**：框架自带的、可独立版本化的核心能力包。

| 模块 | Composer 包名 | 职责 |
|------|---------------|------|
| System | `starship/system` | 认证、RBAC、菜单、组织、字典、配置、附件、插件管理 UI、**操作/登录/异常日志（监控中心）** |
| Generator | `starship/generator` | 表结构读取、CRUD 代码生成 |

> **说明**：原独立的 `Monitor` 模块已合并进 `System`。对外 API 前缀仍为 `/admin-api/monitor/*`，权限码仍为 `monitor.*`，数据表仍为 `monitor_*`。

每个模块通过 `module.json` 声明启用状态、Provider、种子、菜单与权限绑定。

**规则**：

- 底座模块之间尽量减少循环依赖（Generator 依赖 System 能力）
- 新底座能力用新模块，不往 System 无限堆功能
- 业务模块（Shop、Cms）**不应**放在此目录（见 Layer 4）

### 2.4 Layer 3 — 插件（`plugins/`）

**职责**：可选安装的能力增强或第三方服务适配，**不修改内核与底座模块源码**。

生命周期：`发现 → 安装 → 启用 → 禁用 → 卸载`（CLI：`starship:plugin:*`）

插件可注册：ServiceProvider、路由、配置页、权限节点。

### 2.5 Layer 4 — 业务实例

完整业务系统（商城、CMS、SaaS）应：

- 使用**独立仓库**，或放在 monorepo 的 `solutions/` 下
- 通过 Composer 引用 `starship/core`、`starship/system` 等
- 自有的 `modules/Shop`、`plugins/PaymentWechat` 等

**不要**把完整业务系统塞进 `modules/System`。

---

## 3. 前端架构（`apps/admin/`）

```
apps/admin/
└── apps/web-antd/          # 官方管理端 UI 实现（Vben Admin 5.x）
    └── src/
        ├── views/          # 底座页面（随 System/Generator）
        ├── api/            # 底座 API 封装
        ├── components/     # 可复用组件（如 AttachmentField）
        └── plugins/        # 插件前端扩展点（规划）
```

**约定**：

- 底座页面路径与后端菜单 `path`、Generator 产出路径一致
- 插件前端放在 `plugins/{Name}/front/` 或 `apps/web-antd/src/plugins/{slug}/`
- 业务项目可 fork `web-antd` 或仅覆盖 `views/` 子集

---

## 4. 扩展契约（Contracts）

内核与 System 解耦的关键。建议在 `packages/core/src/Contracts/` 定义：

```php
// 插件注册表读写
interface PluginRepositoryInterface
{
    public function findBySlug(string $slug): ?object;
    public function allKeyedBySlug(): iterable;
    public function create(array $attributes): object;
    public function update(object $record, array $attributes): void;
}

// 插件配置项读写
interface PluginConfigStoreInterface
{
    public function get(string $slug, string $key, mixed $default = null): mixed;
    public function setMany(string $slug, array $values): void;
}
```

**当前问题**：`PluginManager`、`PluginConfigRepository` 直接 use `Modules\System\App\Models\Plugin`，导致内核无法独立发包。

**解法（二选一）**：

| 方案 | 做法 | 推荐度 |
|------|------|--------|
| A | 将 `Plugin`、`PluginConfig` 模型与迁移迁入 `packages/core` | ★★★ 简单直接 |
| B | 模型留 System，Core 只依赖 Contract，System Provider 绑定实现 | ★★☆ 更解耦 |

阶段二抽包时优先采用 **方案 A**。

---

## 5. 描述文件规范

### 5.1 `module.json`

```json
{
  "name": "system",
  "title": "系统管理",
  "version": "0.1.0",
  "enabled": true,
  "providers": ["Modules\\System\\App\\Providers\\SystemServiceProvider"],
  "seeders": ["Modules\\System\\Database\\Seeders\\StarshipFoundationSeeder"],
  "menus": {
    "paths": []
  },
  "permissions": {
    "prefixes": ["system.", "dashboard.", "monitor."],
    "exact": []
  },
  "require": {
    "php": ">=8.3",
    "modules": []
  }
}
```

| 字段 | 必填 | 说明 |
|------|------|------|
| `name` | 是 | 唯一标识，小写 |
| `enabled` | 是 | 是否加载 Provider |
| `providers` | 是 | ServiceProvider 类名列表 |
| `menus.paths` | 否 | 禁用时从侧栏过滤的路由 path |
| `permissions` | 否 | 禁用时过滤的权限码前缀/精确值 |
| `require.modules` | 否 | 依赖的其他模块 |

### 5.2 `plugin.json`

```json
{
  "name": "SmsAliyun",
  "slug": "sms-aliyun",
  "type": "adapter",
  "namespace": "Plugins\\SmsAliyun",
  "version": "1.0.0",
  "require": {
    "starship": ">=1.0.0",
    "php": ">=8.3",
    "modules": ["System"],
    "plugins": []
  },
  "providers": ["Plugins\\SmsAliyun\\Providers\\SmsAliyunServiceProvider"],
  "routes": ["routes/admin-api.php"],
  "migrations": false,
  "config": "config/sms-aliyun.php",
  "pages": [
    { "key": "config", "title": "配置", "permission": "system.plugins.config" }
  ]
}
```

| 字段 | 说明 |
|------|------|
| `slug` | 全局唯一，用于 DB、配置键 |
| `type` | `adapter` / `feature` / `theme` |
| `require.modules` | 安装前检查的模块依赖 |
| `pages` | 后台插件管理中的 Tab 页 |

---

## 6. API 与权限约定

与 README §14 保持一致，框架级约束：

| 项 | 约定 |
|----|------|
| 路由前缀 | `/admin-api` |
| 响应结构 | `{ code, message, data }`，`ApiResponse` 统一出口 |
| 认证 | Sanctum Bearer Token |
| 权限码 | `{模块}.{资源}.{操作}`，如 `system.admins.create`（按钮用 `query` 非 `index`） |
| 表前缀 | `DB_PREFIX`（默认 `sa_`），Model 用逻辑表名 `protected $table = 'admins'` |

权威清单：[permissions.md](../api/permissions.md)、[routes.md](../api/routes.md)。

---

## 7. 版本与发布策略

| 包 | 版本策略 | 说明 |
|----|----------|------|
| `starship/core` | SemVer；**Packagist** | monorepo 开发 path；发版 subtree → 独立仓 → Packagist |
| `starship/system` 等 | 源码模块（近期） | 留在 `modules/`，不强制 Packagist |
| `backend` Laravel 壳 | 跟随 Laravel | `laravel/framework: ^13.0` |
| 业务项目 | 自有版本 | `composer.json` 锁定底座版本范围 |

业务项目 `composer.json` 示例（目标态）：

```json
{
  "require": {
    "php": "^8.4",
    "laravel/framework": "^13.0",
    "starship/core": "^1.0",
    "starship/system": "^1.0",
    "starship/generator": "^1.0"
  }
}
```

---

## 8. 演进路线图

### 阶段一：底座夯实（当前 → 约 3～6 个月）

- [x] System（含监控日志）/ Generator 核心功能完整
- [x] Generator 端到端：表 → 后端 + 前端 + 菜单 + 权限
- [x] 插件全流程：发现 / 安装 / 启用 / 配置 / 路由
- [x] 文档：API、权限码、描述文件规范稳定
- [x] **纪律**：新业务不进 `backend/app/`、不污染 System 核心

### 阶段二：框架产品化（底座稳定后 → 约 1～2 个月）

- [x] 创建 `packages/core` 并迁移内核代码
- [x] Plugin 模型迁入 Core
- [x] `backend/bootstrap/providers.php` 仅注册 `CoreServiceProvider`
- [x] 脚手架：`php artisan starship:module:make`、`starship:plugin:make`
- [x] 项目模板约定：开发仓兼发布（根 `composer.json`；见 [release-as-template.md](./release-as-template.md)）
- [ ] 对外 `release` 分支 / 版本 tag
- [x] 框架级 Feature Test（启动、模块、插件、ping；`packages/core` 包测试已独立）
- [x] ThemeManager MVP：`theme.json` 发现 / 安装 / 启用 / 切换（渲染层 Demo 另计）

### 阶段三：生态与工具（长期）

- [ ] 官方插件库（短信、存储、支付）
- [ ] 在线文档站
- [ ] 前端插件动态加载
- [ ] 插件市场（可选）

---

## 9. 新业务项目启动流程（目标态）

```bash
# 1. 从本仓库发布分支创建（也可 Packagist create-project）
git clone -b release <本仓 URL> my-erp-admin
# 或：composer create-project starship/skeleton my-erp-admin

cd my-erp-admin/backend

# 2. 环境初始化
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed

# 3. 前端（同仓 apps/admin，见模板 README）
cd ../apps/admin && pnpm install && pnpm dev

# 4. 创建业务模块
cd ../../backend
php artisan starship:module:make Erp

# 5. 可选插件（安装会自动发现入库）
php artisan starship:plugin:install sms-aliyun
php artisan starship:plugin:enable sms-aliyun

# 6. Generator
php artisan starship:generator:crud erp_orders
```

发布约定见 [release-as-template.md](./release-as-template.md)；backend 清单见 [skeleton-backend.md](./skeleton-backend.md)。

---

## 10. 代码归属速查

| 你要做的事 | 放哪里 |
|------------|--------|
| 改登录、权限、菜单 | `modules/System/` |
| 加操作/登录/异常日志 | `modules/System/`（监控中心，`monitor.*` 权限） |
| 改 CRUD 生成逻辑 | `modules/Generator/` |
| 接阿里云短信 | `plugins/SmsAliyun/` |
| 开发商城订单管理 | 业务仓库 `modules/Shop/` |
| 改模块扫描、插件引擎 | `packages/core/` |
| 改 Laravel 配置 | `backend/config/` |
| 改管理端 UI 布局 | `apps/admin/apps/web-antd/` |

---

## 11. 与 Laravel 升级的关系

在本架构下：

- **Layer 0** 随 Laravel 官方 Upgrade Guide 同步
- **Layer 1～3** 使用稳定公共 API（ServiceProvider、Eloquent、Route、Artisan），不 fork 框架内部
- 文件物理位置不影响升级工作量；**边界清晰**才降低风险
- 定期 `composer update laravel/framework`（13.x 内）+ 框架级测试

---

## 12. 相关文档

- [README.md](../../README.md) — 项目总览与功能范围
- [public-storage-link.md](../deployment/public-storage-link.md) — 部署说明
- [generator/cli.md](../generator/cli.md) — 生成器 CLI
- [packages/core/README.md](../../packages/core/README.md) — 内核包说明
- [skeleton-backend.md](./skeleton-backend.md) — starship/skeleton 中 backend/ 文件清单
