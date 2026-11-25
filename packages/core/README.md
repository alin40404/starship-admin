# starship-admin/core

Starship Admin 框架内核包（Layer 1）。

负责 **模块扫描**、**插件引擎**、统一 API 响应与 `starship:*` 脚手架命令；不承载 System / Generator 等业务能力。

## 环境要求

| 项 | 版本 |
|----|------|
| PHP | `^8.3` |
| Laravel | `^13.0` |
| Composer | `^2.7`（core 运行时使用 `ClassLoader` 动态加载插件） |

## 包含

| 目录 / 能力 | 说明 |
|-------------|------|
| `Module/` | `ModuleManager`、`module.json` 解析、启动依赖校验 |
| `Plugin/` | 发现 / 安装 / 启用 / 禁用 / 卸载、路由与配置注册 |
| `Models/` | `Plugin`、`PluginConfig` 持久化 |
| `Support/` | `ApiResponse`、`DevEnvironment` 等 |
| `Console/Commands/` | `starship:module:*`、`starship:plugin:*` |
| `Providers/CoreServiceProvider.php` | 注册已启用模块与插件 |

## Composer 依赖说明

### `require`（下游会安装）

```json
{
  "php": "^8.3",
  "laravel/framework": "^13.0",
  "composer/composer": "^2.7"
}
```

- 业务项目的 `composer.json` **也应声明** `laravel/framework`；与 core 的约束由 Composer **合并解析**，`vendor/` 中只装一份。
- `composer/composer` 由 core 带入，供 `PluginAutoloader` 使用，下游无需单独声明。

### `require-dev`（仅维护 core 本身）

`orchestra/testbench`、`phpunit/phpunit` 只在 `packages/core` 目录执行 `composer install` 时安装；下游 `composer require starship-admin/core` **不会**安装这些包。

## 安装

> Packagist / subtree 发版说明见开发仓  
> [packagist.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/packagist.md)。

### 方式 A：从应用模板仓 `release` 分支创建（推荐）

```bash
# GitHub
git clone -b release https://github.com/alin40404/starship-admin.git my-admin
# 或 Gitee
# git clone -b release git@gitee.com:allanchan004/starship-admin.git my-admin

cd my-admin/backend
composer install   # starship-admin/core 来自 Packagist
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

模板内底座为 System / Generator / Member（path）；前端在 `apps/admin`。详见 [release-as-template.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/release-as-template.md)。

### 方式 B：已有 Laravel 项目手动接入

**1. 安装包**（需 Packagist 已上架或配置 VCS 仓库）

```bash
composer require starship-admin/core:^1.0
```

```json
{
  "require": {
    "php": "^8.3",
    "laravel/framework": "^13.0",
    "starship-admin/core": "^1.0"
  }
}
```

**2. 注册 Provider**（`bootstrap/providers.php`）

```php
<?php

use App\Providers\AppServiceProvider;
use Starship\Core\Providers\CoreServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
];
```

**3. 迁移**

core 自带 `plugins`、`plugin_configs` 表迁移：

```bash
php artisan migrate
```

**4. 准备目录**（默认值相对 Laravel 项目根 `base_path()` 的上一级）

```
your-project/
├── backend/              # Laravel 应用（或你的 app 目录）
├── modules/              # STARSHIP_MODULES_PATH
│   ├── System/
│   └── Generator/
└── plugins/              # STARSHIP_PLUGINS_PATH
    └── SmsAliyun/
```

**5. 安装底座模块**（完整后台需要）

```bash
composer require starship/system starship/generator
```

各模块 `module.json` 中 `enabled: true` 后 **重启 PHP 服务**。

### 方式 C：本仓库 monorepo（path 开发）

`backend/composer.json`：

```json
{
  "require": {
    "starship-admin/core": "@dev"
  },
  "repositories": [
    { "type": "path", "url": "../packages/core" }
  ]
}
```

`bootstrap/providers.php` 同上注册 `CoreServiceProvider::class`。

## 配置

`config/starship.php` 由 `CoreServiceProvider` 自动合并，一般无需发布。

| 环境变量 | 默认值 | 说明 |
|----------|--------|------|
| `STARSHIP_MODULES_PATH` | `{项目根}/modules` | 模块目录（`module.json` 扫描路径） |
| `STARSHIP_PLUGINS_PATH` | `{项目根}/plugins` | 插件目录（`plugin.json` 扫描路径） |

> `{项目根}` = `dirname(base_path())`，即 Laravel `backend/` 的上一级。

## 常用 CLI

### 项目安装

| 命令 | 说明 |
|------|------|
| `php artisan starship:install` | 安装引导：环境检测、写库配置、migrate/seed、`storage:link`、写安装锁 |

也可在管理端打开 `/install`（未安装时路由守卫会强制进入）。详见开发仓 [install-wizard.md](https://github.com/alin40404/starship-admin/blob/master/docs/deployment/install-wizard.md)。

### 模块

| 命令 | 说明 |
|------|------|
| `php artisan starship:module:make {Name}` | 在 `modules/{Name}/` 生成脚手架，并默认挂载到 `backend/composer.json`（path + require `@dev`）后执行 `composer update` |
| `php artisan starship:module:import {path}` | 导入模块包；同样默认自动挂载 Composer |

选项：`--title=` 显示名；`--no-composer` 跳过挂载；`--no-update` 只改 `composer.json` 不跑 `composer update`。删除模块时在 `backend/` 执行 `composer remove starship/{slug}`，并清理残留 path `repositories`。

挂载后在 `module.json` 设 `enabled: true` 并重启服务。

### 插件

| 命令 | 说明 |
|------|------|
| `php artisan starship:plugin:make {Name} [--type=sms]` | 在 `plugins/{Name}/` 生成脚手架 |
| `php artisan starship:plugin:discover` | 扫描 `plugins/` 并同步 DB 记录 |
| `php artisan starship:plugin:list` | 列出插件及状态 |
| `php artisan starship:plugin:install {slug}` | 安装 |
| `php artisan starship:plugin:enable {slug}` | 启用（需重启 PHP） |
| `php artisan starship:plugin:disable {slug}` | 禁用（需重启 PHP） |
| `php artisan starship:plugin:uninstall {slug}` | 卸载（须先禁用） |

典型流程：

```bash
php artisan starship:plugin:make SmsAliyun --type=sms
php artisan starship:plugin:discover
php artisan starship:plugin:install sms-aliyun
php artisan starship:plugin:enable sms-aliyun
# 重启 PHP → 在管理端配置插件
php artisan starship:plugin:disable sms-aliyun
php artisan starship:plugin:uninstall sms-aliyun
```

## 依赖原则

- 可依赖 `illuminate/*` / `laravel/framework`
- **不得**依赖 `Modules\System\*` 等业务命名空间
- 持久化扩展通过 Contract 注入（规划中）

## 版本与发布

| 包 | 策略 |
|----|------|
| `starship-admin/core` | SemVer `1.x`，公共 API 稳定后严格遵循 |
| `starship/system` 等 | 独立版本，与 core 解耦发布 |

当前目标发行版为 **`1.0.0`**（本仓开发仍用 path `@dev`）；下游正式依赖使用 `^1.0`。

## 测试

Core 包测试 **独立于 `backend/`**（保持 Laravel 壳纯净），使用 [Orchestra Testbench](https://github.com/orchestral/testbench)：

```bash
cd packages/core
composer install
composer test
```

- 无 `pdo_sqlite` 时自动回退读取 `backend/.env` 使用 MySQL
- 含 `SmsAliyunPluginLifecycleTest` 插件全链路验收

`backend/tests/` 仅保留应用壳冒烟（如 `/admin-api/ping`）；Core / 模块测试各自在对应包目录运行。

## 相关文档

- [CHANGELOG.md](./CHANGELOG.md) — 版本变更
- [packagist.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/packagist.md) — Packagist / subtree 发版
- [framework.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/framework.md) — 四层架构
- [development-checklist.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/development-checklist.md) — 开发清单
- [skeleton-backend.md](https://github.com/alin40404/starship-admin/blob/master/docs/architecture/skeleton-backend.md) — Laravel 壳说明

## 相关仓库

| 仓库 | 说明 |
|------|------|
| [alin40404/starship-admin](https://github.com/alin40404/starship-admin) | 开发仓（monorepo，`packages/core` 源码） |
| [alin40404/starship-core](https://github.com/alin40404/starship-core) | 本仓：Packagist 发布仓 |
| [Packagist: starship-admin/core](https://packagist.org/packages/starship-admin/core) | Composer 包 |
