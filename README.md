# Starship Admin

基于 **Laravel + Vue 3** 的中后台开发框架。提供权限、菜单、模块化、插件、代码生成等底座能力，方便在此之上二开自己的业务系统。

| 制品 | 说明 |
|------|------|
| 本仓库（模板） | `starship/skeleton`：Laravel 薄壳 + 底座模块 + 管理端前端 |
| 内核包 | [`starship-admin/core`](https://packagist.org/packages/starship-admin/core)（Packagist） |

---

## 特性

- **RBAC**：管理员、角色、菜单、按钮权限、动态路由
- **模块化**：`modules/` + `module.json`，可独立开关
- **插件**：`plugins/` 发现 / 安装 / 启用 / 禁用 / 卸载
- **代码生成**：Generator 按表生成前后端 CRUD
- **统一 API**：统一响应、异常与审计日志（操作 / 登录 / 异常）
- **内核可复用**：`starship-admin/core` 独立发包，应用仓保持薄壳

---

## 技术栈

| 层 | 技术 |
|----|------|
| 后端 | PHP 8.3+、Laravel 13、MySQL 8、Redis |
| 前端 | Vue 3、TypeScript、Vite、Vben Admin 5.x、Pinia |
| 工具 | Composer 2.x、pnpm 9+、Node.js 20+ |

---

## 架构概览

```text
业务项目 / 自有 modules/*
        ↓
底座模块   modules/System · Generator · Member
        ↓
框架内核   starship-admin/core（Module / Plugin / ApiResponse / CLI）
        ↓
Laravel 壳  backend/（bootstrap、config、artisan）
```

详细分层与边界见 [docs/architecture/framework.md](docs/architecture/framework.md)。

---

## 仓库结构

```text
starship-admin/
├── backend/          # Laravel 宿主（薄壳）
├── packages/core/    # 框架内核（master 开发用 path；release 用 Packagist）
├── modules/          # 底座模块（System / Generator / Member …）
├── plugins/          # 可选插件
├── themes/           # 主题
├── apps/admin/       # 管理端前端
└── docs/             # 架构与部署文档
```

### 底座模块

| 模块 | 职责 |
|------|------|
| **System** | 登录认证、管理员 / 角色 / 菜单 / 权限、字典、配置、附件、插件管理、监控日志 |
| **Generator** | 按数据表生成 Model / Controller / Service、Vue 列表与表单、API 与类型 |
| **Member** | 会员相关底座能力（列表、认证契约等） |

在 `modules/` 下新增业务模块即可扩展；规范见文档索引。

---

## 分支约定

| 分支 | 用途 |
|------|------|
| `master` | 框架研发：core 用 path `@dev`；可含样例模块与完整文档 |
| `release` | **对外模板**：core 用 Packagist `^1.0`；仅 System + Generator + Member |

下游请以 `release` 为起点。约定见 [release-as-template.md](docs/architecture/release-as-template.md)。

---

## 快速开始

### 1. 克隆模板（推荐 `release`）

```bash
# GitHub
git clone -b release https://github.com/alin40404/starship-admin.git my-admin

# 或 Gitee
git clone -b release git@gitee.com:allanchan004/starship-admin.git my-admin
```

### 2. 后端

```bash
cd my-admin/backend
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# 配置 .env 中的数据库等
php artisan migrate --seed
php artisan storage:link      # 附件 public 磁盘
php artisan serve             # http://127.0.0.1:8000
```

### 3. 前端

```bash
cd ../apps/admin
pnpm install
pnpm dev:antd                 # 默认 http://localhost:5666
```

Windows 也可在仓库根目录双击 `start-dev.bat` 同时拉起前后端。

### 4. 常用地址

| 服务 | 地址 |
|------|------|
| 管理端 | http://localhost:5666 |
| 后端 API | http://127.0.0.1:8000/admin-api |
| 健康检查 | `GET /admin-api/ping` |

附件软链接说明：[docs/deployment/public-storage-link.md](docs/deployment/public-storage-link.md)。

---

## 扩展方式

| 方式 | 目录 | 说明 |
|------|------|------|
| 模块 Module | `modules/{Name}` | 业务能力主体；`starship:module:make` |
| 插件 Plugin | `plugins/{Name}` | 第三方适配与增强；`starship:plugin:make` |
| 主题 Theme | `themes/{Name}` | 前台 / 渲染主题 |

不要把业务逻辑写进 `backend/app/` 或 `packages/core`。

```bash
cd backend
php artisan starship:module:make Demo
php artisan starship:plugin:make SmsDemo
```

---

## 内核包

日常在 monorepo 的 `packages/core` 开发；发版拆到独立仓并发布 Packagist。

- Packagist：https://packagist.org/packages/starship-admin/core  
- 源码仓：https://github.com/alin40404/starship-core  
- 说明：[docs/architecture/packagist.md](docs/architecture/packagist.md)、[packages/core/README.md](packages/core/README.md)

---

## 文档

| 文档 | 内容 |
|------|------|
| [framework.md](docs/architecture/framework.md) | 分层架构与二开边界 |
| [release-as-template.md](docs/architecture/release-as-template.md) | `release` 模板约定 |
| [packagist.md](docs/architecture/packagist.md) | core 发包与 subtree |
| [skeleton-backend.md](docs/architecture/skeleton-backend.md) | 后端骨架 |
| [development-checklist.md](docs/architecture/development-checklist.md) | 开发清单 |
| [Module 开发规范](docs/deployment/Starship_Module_开发规范.md) | 模块规范 |
| [Plugin 开发规范](docs/deployment/Starship_Plugin_开发规范.md) | 插件规范 |
| [Theme 开发规范](docs/deployment/Starship_Theme_开发规范.md) | 主题规范 |
| [module-cli.md](docs/deployment/module-cli.md) / [plugin-cli.md](docs/deployment/plugin-cli.md) | CLI |
| [generator/cli.md](docs/generator/cli.md) | 代码生成 CLI |
| [权限与菜单](docs/deployment/Starship_权限与菜单体系设计方案.md) | RBAC |
| [ui/admin-font-size.md](docs/ui/admin-font-size.md) | 管理端字号 |

API 路由与权限码见 `docs/api/`。

---

## 相关仓库

| 仓库 | 用途 |
|------|------|
| [alin40404/starship-admin](https://github.com/alin40404/starship-admin) | 本仓（GitHub） |
| [allanchan004/starship-admin](https://gitee.com/allanchan004/starship-admin) | 本仓（Gitee） |
| [alin40404/starship-core](https://github.com/alin40404/starship-core) | 内核发布仓 |

---

## License

[MIT](LICENSE) © allanchan
