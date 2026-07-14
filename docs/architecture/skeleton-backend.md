# starship/admin 中 backend/ 文件清单

> 本文说明：从 monorepo 抽取 `starship/admin` 时，`backend/` 应保留什么、可删什么、与标准 Laravel 的差异在哪里。  
> 原则：**backend 是模板复制，不是命令生成。**

---

## 1. 目标结构

别人执行 `composer create-project starship/admin my-admin` 后，应得到：

```
my-admin/
├── backend/                 ← 本文范围：Laravel 薄壳
├── modules/                 ← 底座模块（另文）
├── plugins/                 ← 空目录
├── apps/admin/              ← 前端（另文）
├── composer.json            ← 可选：根级编排脚本
└── README.md
```

`backend/` 职责：**HTTP 入口 + 环境配置 + 注册 Starship Core**，不含业务逻辑。

### 1.1 交付形态（替代 monorepo sync）

| 方式 | 说明 |
|------|------|
| **独立模板仓库**（推荐现阶段） | 新建 `starship-admin`（或同名）Git 仓，内容即上表整棵树；用户 `git clone` 或 `composer create-project`（VCS / Packagist） |
| Packagist | 上架 `starship/admin` 后：`composer create-project starship/admin my-admin` |
| **不要** | 在框架 monorepo 内用 sync 脚本再生成一份重复骨架 |

现阶段 **做成独立仓库就够用**；Packagist 可后补，不影响下游开工。

### 1.2 前端 `apps/admin` 怎么处理

| 策略 | 适用 |
|------|------|
| **同仓交付（推荐）** | 模板仓内放入裁剪后的 `apps/admin`（去掉 playground / docs / backend-mock）；业务项目一 clone 前后端齐全 |
| 子模块 / 双仓 | 前端独立仓，模板 README 写 `git clone` / submodule；升级解耦但上手成本高 |
| 仅后端模板 | 最快，但下游必须自己接前端，体验差 |

从本 monorepo 抽前端时建议保留：`apps/admin/apps/web-antd` + 工作区必要包；去掉演示与文档站。CORS 默认指向前端 dev 端口即可。

---

## 2. 文件清单总览

| 分类 | 数量级 | 策略 |
|------|--------|------|
| Laravel 标准骨架 | ~40 文件 | 保留，随 Laravel 大版本 diff 同步 |
| Starship 定制 | ~10 处改动 | 模板中固定，文档标注 |
| 开发期冗余 | 若干 | skeleton 中删除或精简 |

---

## 3. 必须保留（✅）

### 3.1 根目录

| 文件 | 说明 |
|------|------|
| `artisan` | Laravel CLI |
| `composer.json` | 依赖与 scripts（见 §5 差异） |
| `.env.example` | 环境模板（见 §6） |
| `.gitignore` | 含 `vendor/`、`storage/`、`.env` |
| `phpunit.xml` | 测试配置 |
| `.editorconfig` | 可选，团队统一格式 |

### 3.2 bootstrap/

| 文件 | 说明 |
|------|------|
| `bootstrap/app.php` | **Starship 定制**：加载 `routes/admin.php` |
| `bootstrap/providers.php` | **Starship 定制**：注册 `CoreServiceProvider` |
| `bootstrap/cache/.gitignore` | 保留空目录占位 |

### 3.3 app/（极薄）

| 文件 | 说明 |
|------|------|
| `app/Http/Controllers/Controller.php` | Laravel 基类 |
| `app/Models/User.php` | Laravel 默认 User（宿主用，非 Admin 业务用户） |
| `app/Providers/AppServiceProvider.php` | 仅 `Schema::defaultStringLength(191)` 等通用 boot |

**禁止出现在 skeleton app/ 中：**

- 业务 Controller / Service
- Module / Plugin 引擎代码（已在 `vendor/starship/core`）
- `helpers.php`（已迁入 core 包）

### 3.4 routes/

| 文件 | 说明 |
|------|------|
| `routes/web.php` | Laravel 默认（可保留 welcome 或改健康检查） |
| `routes/console.php` | Artisan 调度 |
| `routes/admin.php` | **Starship 定制**：`/admin-api` 入口，至少保留 `ping` |

业务 API 路由由 `modules/*/routes/admin.php` 注册，**不进** skeleton 的 `backend/routes/`。

### 3.5 config/

| 文件 | Starship 相关 |
|------|---------------|
| `config/app.php` | 标准 |
| `config/auth.php` | 标准 |
| `config/cache.php` | 标准 |
| `config/cors.php` | **定制**：`admin-api/*` + `CORS_ALLOWED_ORIGINS` |
| `config/database.php` | **定制**：`DB_PREFIX` 默认 `sa_` |
| `config/filesystems.php` | 标准（附件 public 磁盘） |
| `config/logging.php` | 标准 |
| `config/mail.php` | 标准 |
| `config/queue.php` | 标准 |
| `config/services.php` | 标准 |
| `config/session.php` | 标准 |

`config/starship.php` 由 **core 包** `mergeConfigFrom` 注入，**不必**在 skeleton 重复一份（除非需要覆盖默认值）。

### 3.6 database/

| 文件 | 说明 |
|------|------|
| `migrations/0001_01_01_000000_create_users_table.php` | Laravel 宿主 users |
| `migrations/0001_01_01_000001_create_cache_table.php` | 缓存 |
| `migrations/0001_01_01_000002_create_jobs_table.php` | 队列 |
| `migrations/*_create_personal_access_tokens_table.php` | Sanctum |
| `seeders/DatabaseSeeder.php` | **Starship 定制**：按 module.json 跑模块 seed |
| `factories/UserFactory.php` | 可选 |

**不在 backend 迁移：**

- `plugins` / `plugin_configs` 表 → `packages/core` 包内迁移
- System/Generator 业务表 → 各 `modules/*` 包内迁移；System 包内亦含 `monitor_*` 监控表

### 3.7 public/

| 文件 | 说明 |
|------|------|
| `public/index.php` | 入口 |
| `public/.htaccess` | Apache |
| `public/favicon.ico` | 可选 |
| `public/robots.txt` | 可选 |

部署后需执行 `php artisan storage:link`（附件 URL）。

### 3.8 tests/（建议保留最小集）

| 文件 | 说明 |
|------|------|
| `tests/TestCase.php` | 标准 |
| `tests/Feature/FrameworkSmokeTest.php` | 应用壳冒烟：`/admin-api/ping`、模块、插件清单、Contract 绑定 |
| `tests/Unit/ExampleTest.php` | 可选 |

> **Core / 模块测试不放 backend**：`packages/core/tests/`、`modules/*/tests/` 各自独立运行；backend 仅保留应用壳冒烟。

---

## 4. 建议删除或精简（🗑️ skeleton 不需要）

| 文件/目录 | 原因 |
|-----------|------|
| `backend/README.md` | Laravel 默认说明，改用项目根 README |
| `backend/package.json` | 若前端在 `apps/admin/`，backend 可不跑 Vite |
| `backend/vite.config.js` | 同上 |
| `backend/resources/js/*` | 无 Blade 前端时可删 |
| `backend/resources/css/app.css` | 同上 |
| `backend/resources/views/welcome.blade.php` | API 项目可删或改简单 JSON 页 |
| `composer.json` 中 `npm run build` | skeleton setup 脚本可去掉 frontend 步骤 |
| `.phpunit.result.cache` | 不应进仓库 |
| `bootstrap/cache/packages.php` | 生成物，仅 `.gitignore` 占位 |

---

## 5. composer.json 差异（monorepo vs skeleton）

### 5.1 当前 monorepo（开发）

```json
{
  "repositories": [
    { "type": "path", "url": "../packages/core" },
    { "type": "path", "url": "../modules/System" },
    { "type": "path", "url": "../modules/Generator" }
  ],
  "require": {
    "starship/core": "@dev",
    "starship/system": "@dev",
    "starship/generator": "@dev"
  }
}
```

### 5.2 未来 skeleton（Packagist）

```json
{
  "name": "starship/admin",
  "type": "project",
  "require": {
    "php": "^8.3",
    "laravel/framework": "^13.0",
    "laravel/sanctum": "^4.3",
    "starship/core": "^1.0",
    "starship/system": "^1.0",
    "starship/generator": "^1.0"
  }
}
```

无 `repositories` path，全部从 Packagist 拉。

下游安装 `starship/core` 的逐步说明见 [packages/core/README.md](../../packages/core/README.md)。

### 5.3 可选依赖

| 包 | skeleton 默认 | 说明 |
|----|---------------|------|
| `league/flysystem-aws-s3-v3` | 可选 | 用 S3 附件时再加 |
| `overtrue/pinyin` | 随 system 模块 | 可留在 system 包 require，不必写进 skeleton |

---

## 6. .env.example Starship 必含项

```env
APP_URL=http://127.0.0.1:8000

# 表前缀
DB_PREFIX=sa_

# 后台前端 CORS（逗号分隔）
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173

# 种子默认超管密码（生产务必修改）
# SYSTEM_ADMIN_PASSWORD=admin123

# 可选：非默认目录时
# STARSHIP_MODULES_PATH=
# STARSHIP_PLUGINS_PATH=
```

---

## 7. Starship 定制文件速查（与 laravel/laravel diff）

仅这几处是「Starship 版本」与 vanilla Laravel 的本质区别：

```
bootstrap/app.php          → then: 加载 routes/admin.php
bootstrap/providers.php    → CoreServiceProvider
routes/admin.php           → /admin-api 前缀
config/cors.php            → admin-api/* 
config/database.php        → prefix sa_
database/seeders/DatabaseSeeder.php  → ModuleManager 分发 seed
composer.json              → require starship/*
.env.example               → CORS、DB_PREFIX、SYSTEM_ADMIN_PASSWORD
```

Laravel 大版本升级时：**先 diff 官方 laravel/laravel，再叠加以上 8 处。**

---

## 8. 模块 / 插件 与 backend 的边界

```
backend/routes/admin.php
  └── 仅宿主级路由（ping、health）

modules/System/routes/admin.php
  └── 登录、RBAC、菜单…（SystemServiceProvider 加载）

plugins/SmsAliyun/routes/admin-api.php
  └── 插件 API（PluginLoader 在启用时 require）
```

skeleton 的 `backend/` **不应**包含 System 的业务路由或 Controller。

---

## 9. 初始化流程（skeleton 使用者）

```bash
cd my-admin/backend

cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

# 插件（可选；安装会自动发现）
php artisan starship:plugin:install sms-aliyun
php artisan starship:plugin:enable sms-aliyun
```

前端在 `apps/admin/` 单独 `pnpm install && pnpm dev`。

---

## 10. 从 monorepo 抽取 checklist

抽取 `starship/admin` 前，在 monorepo 确认：

- [ ] `backend/app/` 无业务代码（仅 3 个文件级）
- [ ] `packages/core` 已稳定，Plugin 模型在 core 内
- [ ] `modules/*` 可独立 composer 包或复制到 skeleton 的 `modules/`
- [ ] `bootstrap/providers.php` 只注册 App + Core
- [ ] 文档中 CORS、DB_PREFIX、storage:link 已说明
- [x] Feature 测试至少覆盖 `GET /admin-api/ping`（见 `tests/Feature/FrameworkSmokeTest.php`）
- [ ] 独立模板仓库 `starship/admin`（create-project / git clone）；前端 `apps/admin` 同仓裁剪纳入

---

## 11. 相关文档

- [framework.md](./framework.md) — 四层架构与演进路线
- [../deployment/public-storage-link.md](../deployment/public-storage-link.md) — 附件软链
