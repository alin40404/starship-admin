# Starship Admin

Starship Admin 是一个基于 **Laravel + Vue 3** 的企业级后台开发框架，目标是提供一套 **开箱即用、可扩展、易二开、适合长期维护** 的中后台开发底座。

它结合 Laravel 现代后端生态与 Vue 3 前端工程化能力，提供统一的后台基础能力、权限体系、模块化机制、插件机制与 CRUD 代码生成能力。

| 制品 | 说明 |
|------|------|
| 本仓库（模板） | `starship/skeleton`：Laravel 薄壳 + 底座模块 + 管理端前端 |
| 内核包 | [`starship-admin/core`](https://packagist.org/packages/starship-admin/core)（Packagist） |

---

## 1. 如何开始工作

先分清两条线：

| 你想做什么 | 用哪个分支 | 说明 |
|------------|------------|------|
| **做自己的业务后台** | `release` | 对外模板；core 从 Packagist 安装 |
| **改框架本身**（内核 / 底座 / 文档） | `master` | 研发分支；core 用本地 path |

日常业务项目请走 **`release`**，不要直接以 `master` 当业务起点。

### 1.1 环境要求

- PHP 8.3+、Composer 2.x
- Node.js 20+、pnpm 9+
- MySQL 8.0+（建议再备 Redis）
- Git

### 1.2 基于模板开工（推荐）

**① 克隆 `release`**

```bash
# GitHub
git clone -b release https://github.com/alin40404/starship-admin.git my-admin

# 或 Gitee
git clone -b release git@gitee.com:allanchan004/starship-admin.git my-admin
```

**② 启动后端**

```bash
cd my-admin/backend
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# 编辑 .env：配置数据库等
php artisan migrate --seed
php artisan storage:link      # 附件 public 磁盘
php artisan serve             # http://127.0.0.1:8000
```

**③ 启动前端**

```bash
cd ../apps/admin
pnpm install
pnpm dev:antd                 # http://localhost:5666
```

Windows 也可在仓库根目录双击 `start-dev.bat`，同时拉起前后端。

**④ 打开并确认**

| 服务 | 地址 |
|------|------|
| 管理端 | http://localhost:5666 |
| 后端 API | http://127.0.0.1:8000/admin-api |
| 健康检查 | `GET /admin-api/ping` |

用 seed 账号登录后台；能进首页、看到菜单，即环境就绪。

### 1.3 开始写业务

业务放在 **自己的模块**，不要写进 `backend/app/` 或 `packages/core`。

```bash
cd backend
php artisan starship:module:make Demo
```

然后：

1. 在 `modules/Demo/module.json` 确认 `enabled: true`
2. 按模块规范补迁移、接口、菜单与权限
3. 管理端在 `apps/admin` 对应目录加页面（或走 Generator 生成 CRUD）
4. 需要第三方能力时用插件：`php artisan starship:plugin:make SmsDemo`

模块 / 插件约定见下文「模块目录规范」「插件机制」，以及 `docs/deployment/` 下开发规范。

### 1.4 参与框架开发（`master`）

```bash
git clone https://github.com/alin40404/starship-admin.git starship-admin
cd starship-admin
git checkout master
cd backend && composer install
# … 同样配置 .env、migrate、storage:link、serve
cd ../apps/admin && pnpm install && pnpm dev:antd
```

- 内核在 `packages/core`，`backend` 通过 path `@dev` 引用
- 改完内核可跑：`cd packages/core && composer test`
- 发 Packagist 见 [packagist.md](docs/architecture/packagist.md)（脚本 `scripts/publish-core.ps1`）
- 刷新对外模板见 [release-as-template.md](docs/architecture/release-as-template.md)

### 1.5 分支速查

| 分支 | 用途 |
|------|------|
| `master` | 框架研发：core path `@dev`；可含样例模块 |
| `release` | **对外模板**：core Packagist `^1.0`；仅 System + Generator + Member |

---

## 2. 核心能力

- 完整的后台基础能力
- RBAC 权限管理
- 动态菜单与按钮权限
- 前后端分离的现代工程架构
- 底座模块化扩展（`modules/`）
- 插件扩展（`plugins/`）：第三方服务适配与能力增强
- 通用 CRUD 代码生成
- 统一 API 响应结构与异常处理
- 日志审计与系统配置
- 企业级项目二次开发友好

---

## 3. 设计原则

- **约定优于配置**
- **模块边界清晰**
- **插件可扩展，但不侵入核心**
- **前后端职责清晰**
- **优先考虑长期维护性**
- **优先考虑二次开发友好性**
- 业务逻辑写在自有模块中，不写入 `backend/app/` 或 `packages/core`

---

## 4. 技术栈

### 4.1 后端

- Laravel 13
- PHP 8.3+
- MySQL 8.0+
- Redis
- Laravel Queue / Scheduler / Storage

### 4.2 前端

- Vue 3
- TypeScript
- Vite
- Vben Admin 5.x
- Pinia
- Vue Router
- Axios

### 4.3 开发环境

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- pnpm 9+
- MySQL 8.0+
- Redis 6+
- Nginx / OpenResty
- Git

---

## 5. 底座模块与插件

对外模板（`release`）内置底座模块：**System**、**Generator**、**Member**；并支持本地插件机制。

### 5.1 System

系统基础模块，负责后台框架最核心的管理能力：

- 登录与认证
- 管理员 / 角色 / 菜单 / 权限节点
- RBAC、动态菜单、按钮权限
- 部门 / 岗位（可选）
- 数据字典、系统配置
- 文件上传与附件管理
- 个人资料、修改密码
- 插件管理 UI
- **监控中心**：操作日志、登录日志、异常日志  
  （API 前缀 `/admin-api/monitor/*`，权限 `monitor.*`）

### 5.2 Generator

CRUD 代码生成器，可根据数据表生成：

- Laravel Model / Request / Controller / Service（Resource 可选）
- 后端路由、权限节点、菜单记录
- Vue 列表页 / 表单页
- 前端 API、TypeScript 类型
- 字段 / 搜索项 / 表格列 / 表单项配置

### 5.3 Member

会员底座模块：会员列表与基础资料、认证相关契约等（依赖 System）。

### 5.4 插件能力

插件用于可选增强与第三方服务适配（短信、存储、支付通道等），不是底座模块。

已支持：

- `plugins/` 目录扫描与 `plugin.json` 描述
- 发现 / 安装 / 启用 / 禁用 / 卸载
- 配置读取与保存（敏感字段可加密）
- 注册 ServiceProvider、后台 API 路由
- 同步菜单与权限节点
- 异常进入 System 监控日志
- Artisan：`starship:plugin:*`

扩展对象边界：

| 类型 | 目录 | 定位 | 示例 |
|------|------|------|------|
| 底座模块 | `modules/` | 框架内置核心能力 | System、Generator、Member |
| 插件 | `plugins/` | 可选增强 / 第三方适配 | SmsAliyun、StorageOss |
| 主题 | `themes/` | 前台 / 渲染主题 | default、corporate |

---

## 6. 插件机制

### 6.1 插件目录结构

```text
plugins/
├─ SmsAliyun/
│  ├─ plugin.json
│  ├─ src/
│  │  ├─ Providers/
│  │  ├─ Services/
│  │  ├─ Http/Controllers/          # 可选
│  │  └─ Support/
│  ├─ routes/
│  │  └─ admin-api.php              # 可选
│  ├─ database/
│  │  ├─ migrations/                # 可选
│  │  └─ seeders/                   # 可选
│  ├─ config/
│  ├─ resources/
│  │  ├─ lang/                      # 可选
│  │  └─ views/                     # 可选
│  ├─ front/                        # 可选：后台前端扩展
│  │  ├─ api/
│  │  ├─ views/
│  │  ├─ components/
│  │  └─ types/
│  ├─ docs/
│  └─ tests/
│
└─ StorageOss/
   └─ ...
```

命名约定：

```text
目录名：SmsAliyun
插件标识：sms-aliyun
命名空间：Plugins\SmsAliyun
配置键：plugin.sms_aliyun
权限前缀：plugin.sms_aliyun
```

### 6.2 插件类型

| 类型 | 说明 | 示例 |
|------|------|------|
| `adapter` | 第三方服务适配器 | 阿里云短信、OSS、七牛 |
| `integration` | 外部平台集成 | 企业微信、钉钉、飞书 |
| `feature` | 可选功能增强 | 导入导出、通知增强 |
| `payment` | 支付通道适配 | 微信支付等 |
| `storage` | 存储通道适配 | OSS、COS、S3、Qiniu |

### 6.3 `plugin.json`

```json
{
  "name": "SmsAliyun",
  "slug": "sms-aliyun",
  "type": "adapter",
  "namespace": "Plugins\\SmsAliyun",
  "version": "1.0.0",
  "description": "阿里云短信服务适配插件",
  "author": "Starship Admin",
  "homepage": "",
  "require": {
    "starship": ">=1.0.0",
    "php": ">=8.3",
    "modules": ["System"],
    "plugins": []
  },
  "providers": [
    "Plugins\\SmsAliyun\\Src\\Providers\\SmsAliyunServiceProvider"
  ],
  "routes": [
    "routes/admin-api.php"
  ],
  "migrations": true,
  "seeders": false,
  "config": "config/sms-aliyun.php",
  "front": {
    "enabled": true,
    "path": "front"
  },
  "permissions": [
    "plugin.sms_aliyun.index",
    "plugin.sms_aliyun.config",
    "plugin.sms_aliyun.test"
  ],
  "menus": [
    {
      "title": "阿里云短信",
      "path": "/plugin/sms-aliyun",
      "permission": "plugin.sms_aliyun.index"
    }
  ],
  "enabled": false,
  "order": 500
}
```

| 字段 | 说明 |
|------|------|
| `name` | 插件名称（大驼峰） |
| `slug` | 唯一标识（小写短横线） |
| `type` | 类型：`adapter` / `integration` / `feature` 等 |
| `namespace` | PHP 命名空间 |
| `version` | 语义化版本 |
| `require` | 依赖（Starship / PHP / 模块 / 插件） |
| `providers` | ServiceProvider |
| `routes` | 路由文件 |
| `migrations` | 是否含迁移 |
| `config` | 默认配置文件 |
| `front` | 是否含后台前端扩展 |
| `permissions` / `menus` | 权限与菜单声明 |
| `enabled` / `order` | 启用状态与加载顺序 |

### 6.4 生命周期

```text
discovered  →  installed  →  enabled
                 ↓              ↓
            uninstalled     disabled
```

| 操作 | 说明 |
|------|------|
| 发现 | 扫描 `plugins/`，读取 `plugin.json` |
| 安装 | 写入注册表，执行迁移，发布配置，写入菜单与权限 |
| 启用 | 注册服务、加载路由、开启配置、显示菜单 |
| 禁用 | 停止加载路由与菜单，默认不删除数据 |
| 卸载 | 移除注册信息；删数据需二次确认 |

### 6.5 持久化

内核使用表（受 `DB_PREFIX` 影响，默认前缀 `sa_`）：

```text
plugins              # 插件注册表
plugin_configs       # 插件配置表
```

敏感配置（`access_key`、`secret_key`、`token` 等）应加密存储，接口不向前端回传明文密钥。

### 6.6 后端加载

由 `packages/core` 中的插件引擎统一处理：

- 扫描目录、读取清单、校验依赖
- 注册 ServiceProvider、配置、路由、迁移
- 同步菜单与权限、查询启用状态

核心类大致位于：

```text
packages/core/src/Plugin/
packages/core/src/Providers/CoreServiceProvider.php
packages/core/src/Models/Plugin.php
packages/core/src/Models/PluginConfig.php
```

`backend/app/` 仅保留 Laravel 薄壳。

### 6.7 前端扩展

插件前端文件可放在插件目录 `front/`，再同步或链接到管理端：

```text
plugins/SmsAliyun/front/
apps/admin/.../plugins/sms-aliyun/
```

常用命令见 [docs/deployment/plugin-cli.md](docs/deployment/plugin-cli.md)。

### 6.8 扩展点

通过 Laravel Event / Listener 接入，避免修改核心代码。常见事件点：

```text
system.booted
admin.login.before / admin.login.after / admin.logout.after
file.upload.before / file.upload.after
menu.build.after
permission.sync.after
generator.table.loaded / generator.code.generated
config.updated
```

### 6.9 权限与菜单

插件权限节点建议：

```text
plugin.{plugin_slug}.{action}
```

安装或启用时同步到 System；路由必须走统一认证与权限中间件。

### 6.10 配置读取

优先级：

```text
数据库配置 > 环境变量 > 插件默认配置
```

```php
plugin_config('sms-aliyun.access_key');
plugin_config('sms-aliyun.sign_name');
```

### 6.11 Artisan 命令

```bash
php artisan starship:plugin:discover
php artisan starship:plugin:list
php artisan starship:plugin:make SmsDemo
php artisan starship:plugin:install SmsAliyun
php artisan starship:plugin:enable SmsAliyun
php artisan starship:plugin:disable SmsAliyun
php artisan starship:plugin:uninstall SmsAliyun
```

### 6.12 安全边界

- 不直接修改 `backend/app`、`modules/System`、`modules/Generator`、`packages/core` 源码来“打补丁”
- 迁移可追踪；启用前校验依赖与版本
- 路由走统一认证与权限中间件
- 敏感配置加密；卸载默认不删业务数据
- 异常写入 System 监控日志

---

## 7. 仓库结构

```text
starship-admin/
├─ backend/                 # Laravel 宿主（薄壳）
│  ├─ app/
│  ├─ bootstrap/
│  ├─ config/
│  ├─ database/
│  ├─ public/
│  ├─ routes/
│  ├─ storage/
│  ├─ tests/
│  ├─ artisan
│  ├─ composer.json
│  └─ .env.example
│
├─ apps/
│  └─ admin/                # 管理端（Vben Admin 5.x）
│
├─ modules/                 # 底座模块
│  ├─ System/
│  ├─ Generator/
│  └─ Member/
│
├─ plugins/                 # 可选插件
├─ themes/                  # 主题
│
├─ packages/
│  └─ core/                 # 内核（master 用 path；release 用 Packagist）
│
├─ docs/                    # 架构与部署文档
├─ README.md
├─ README.en.md
└─ LICENSE
```

> `master` 研发分支可能另含样例模块，**对外请以 `release` 为准**（仅 System + Generator + Member）。

---

## 8. 目录约定

### 8.1 `backend`

Laravel 宿主应用入口：bootstrap、config、artisan。不放业务 Controller / Service。

### 8.2 `apps`

可独立运行、构建、部署的前端应用。管理端为 `apps/admin/`。

### 8.3 `modules`

底座与自研业务模块目录。通过 `module.json` 注册。脚手架：

```bash
php artisan starship:module:make Demo
```

### 8.4 `plugins`

可选插件与第三方适配器，与 `modules` 分离。

### 8.5 `packages`

代码级共享能力。当前核心为 `packages/core`（`starship-admin/core`）。

### 8.6 `themes`

前台 / 渲染主题（如 `default`、`corporate`）。管理端主题色、暗黑模式、布局样式放在 `apps/admin` 前端工程内，不占用根目录 `themes/`。

---

## 9. 模块目录规范

以 `System` 为例：

```text
modules/System/
├─ module.json
├─ app/
│  ├─ Models/
│  ├─ Http/
│  │  ├─ Controllers/
│  │  ├─ Requests/
│  │  └─ Resources/           # 可选
│  ├─ Services/
│  ├─ Repositories/           # 可选
│  ├─ Providers/
│  └─ Support/
├─ routes/
│  ├─ admin-api.php
│  └─ web.php                 # 可选
├─ database/
│  ├─ migrations/
│  └─ seeders/
├─ config/
├─ resources/
│  ├─ lang/
│  └─ views/                  # 可选
├─ front/                     # 可选
│  ├─ api/
│  ├─ views/
│  └─ types/
└─ tests/
```

---

## 10. `module.json` 规范

权威字段以仓库内模块为准；权限与菜单见 [docs/api/permissions.md](docs/api/permissions.md)。

```json
{
  "name": "system",
  "title": "系统管理",
  "version": "0.1.0",
  "enabled": true,
  "providers": [
    "Modules\\System\\App\\Providers\\SystemServiceProvider"
  ],
  "seeders": [
    "Modules\\System\\Database\\Seeders\\StarshipFoundationSeeder"
  ],
  "menus": {
    "paths": [
      "/permission/admins",
      "/dev/modules",
      "/monitor/operation-logs"
    ]
  },
  "permissions": {
    "prefixes": ["system.", "dashboard.", "monitor."],
    "exact": []
  }
}
```

| 字段 | 说明 |
|------|------|
| `name` | 模块标识（小写） |
| `title` | 展示名称 |
| `version` | 版本 |
| `enabled` | 是否启用 |
| `dev_only` | 可选；仅开发环境（如 Generator） |
| `require.modules` | 可选；依赖的其它模块 `name` |
| `providers` | ServiceProvider |
| `seeders` | 可选 |
| `menus.paths` | 归属本模块的前端菜单 path |
| `permissions.prefixes` / `exact` | 权限码归属规则 |

---

## 11. 模块命名规范

### 11.1 目录与标识

```text
modules/System      →  name: system
modules/Generator   →  name: generator
modules/Member      →  name: member
```

### 11.2 PHP 命名空间

```php
Modules\System
Modules\Generator
Modules\Member
```

### 11.3 前端路由 path

与菜单 `path` 一致，小写短横线：

```text
/permission/admins
/permission/roles
/permission/menus
/org/departments
/platform/configs
/resource/attachments
/monitor/operation-logs
/dev/codegen
/dev/modules
/dev/plugins
/member/members
```

监控中心页面路由为 `/monitor/*`，权限码为 `monitor.*`。

### 11.4 权限节点

点分结构 `{模块}.{资源}.{操作}`：

```text
system.admins
system.admins.query
system.admins.create
system.admins.update
system.admins.delete

member.members.query
generator.codegen
```

完整清单见 [docs/api/permissions.md](docs/api/permissions.md)。

---

## 12. 后端架构分层

```text
Controller → Request → Service → Model / Repository → Database
```

| 层 | 职责 |
|----|------|
| Controller | 接收请求、调用验证与 Service、返回统一响应；不写复杂业务 |
| Request | 参数验证、规则、提示、场景 |
| Service | 流程编排、业务判断、事务 |
| Model | 模型、关联、访问器 / 修改器、作用域 |
| Repository | 可选；复杂查询时再引入 |

---

## 13. API 规范

### 13.1 路由前缀

后台 API 统一：

```text
/admin-api
```

业务接口方法约定为 **仅 GET / POST**（写操作含更新、删除均用 POST）。示例：

```text
POST   /admin-api/auth/login
GET    /admin-api/auth/permissions
GET    /admin-api/system/admins
POST   /admin-api/system/admins
POST   /admin-api/system/admins/{id}
POST   /admin-api/system/admins/{id}/delete
GET    /admin-api/dev/plugins
GET    /admin-api/generator/tables
```

完整路由见 [docs/api/routes.md](docs/api/routes.md)。

### 13.2 统一响应

成功：

```json
{
  "code": 0,
  "message": "success",
  "data": {}
}
```

分页：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "items": [],
    "total": 0,
    "page": 1,
    "page_size": 20
  }
}
```

失败：

```json
{
  "code": 400,
  "message": "请求参数错误",
  "data": null
}
```

### 13.3 常用 code

| code | 说明 |
|---:|------|
| 0 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未登录或登录过期 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 422 | 表单验证失败 |
| 500 | 服务器错误 |

---

## 14. 权限设计

采用 RBAC。

### 14.1 核心对象

管理员、角色、菜单、权限节点，以及管理员—角色、角色—菜单、角色—权限关系。

### 14.2 权限层级

| 类型 | 说明 | 示例 |
|------|------|------|
| 目录 | 菜单分组 | 系统管理 |
| 菜单 | 页面入口 | 管理员管理 |
| 按钮 | 操作权限 | 新增、编辑、删除 |

### 14.3 前后端控制

- 前端：按权限节点控制路由、菜单、按钮
- 后端：必须二次校验权限节点，不能只依赖前端隐藏

详见 [docs/api/permissions.md](docs/api/permissions.md)、[权限与菜单体系](docs/deployment/Starship_权限与菜单体系设计方案.md)。

---

## 15. 数据库规范

### 15.1 表前缀

默认通过环境变量：

```text
DB_PREFIX=sa_
```

迁移中表名一般为逻辑名（如 `admins`、`roles`），实际物理表为 `sa_admins` 等。

### 15.2 命名

```text
{业务名}                 # 如 admins、roles、menus
{模块}_{业务}            # 如 generator_tables、monitor_operation_logs
```

### 15.3 通用字段

```sql
`id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`created_by` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
`updated_by` bigint unsigned DEFAULT NULL COMMENT '更新人ID',
`created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
`updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
`deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`)
```

常用扩展：

```sql
`status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0=禁用,1=启用',
`sort` int NOT NULL DEFAULT 0 COMMENT '排序',
`remark` varchar(500) DEFAULT NULL COMMENT '备注'
```

### 15.4 字段命名

- 小写 + 下划线
- 布尔优先 `is_` 前缀
- 时间字段 `_at` 后缀

---

## 16. 底座相关表

逻辑表名（再加 `DB_PREFIX`）：

**System**

```text
admins / roles / menus
admin_role / role_menu
dict_types / dict_items
configs / attachments
departments / posts / admin_post          # 组织相关
monitor_operation_logs
monitor_login_logs
monitor_exception_logs
```

**内核插件 / 主题**

```text
plugins / plugin_configs
themes
```

**Generator**

```text
generator_tables
generator_columns
generator_records
```

**Member**

```text
members
```

---

## 17. CRUD 代码生成器

### 17.1 输入

- 选择已有数据表，读取字段 / 类型 / 注释 / 索引
- 手动配置展示方式、搜索、表单、列表、验证规则

### 17.2 字段配置项

| 配置项 | 说明 |
|--------|------|
| 字段名 / 注释 / 类型 | 库表元数据 |
| TS 类型 | 前端类型 |
| 列表 / 搜索 / 表单 | 是否参与 |
| 表单组件 | Input、Select、DatePicker 等 |
| 验证规则 | 必填、长度、格式 |
| 字典绑定 | 可选 |
| 排序 | 显示顺序 |

### 17.3 生成内容

后端：Model、Controller、Request、Service、Route、Permission、Menu（Migration / Seeder 可选）

前端：`api.ts`、`types.ts`、列表 / 表单页、`columns` / `schema` 等

### 17.4 原则

- 只生成基础 CRUD；复杂逻辑在 Service 扩展
- 默认不覆盖手写代码（除非明确确认）
- 生成记录可追踪；菜单与权限可自动创建
- 生成结果符合模块目录规范

CLI 见 [docs/generator/cli.md](docs/generator/cli.md)。

---

## 18. 前端架构

管理端基于 Vben Admin 5.x。应用内常见目录：

```text
apps/admin/.../src/
├─ api/
├─ components/
├─ layouts/
├─ router/
├─ store/
├─ utils/
├─ views/
├─ features/              # 与后端模块对应的功能目录
└─ plugins/               # 插件前端扩展
```

具体以仓库内 `apps/admin` 工程为准。字号约定见 [docs/ui/admin-font-size.md](docs/ui/admin-font-size.md)。

---

## 19. 登录认证

推荐 **Laravel Sanctum**（SPA 前后端分离）。

支持能力：

- 账号密码登录（验证码可选）
- 登录失败次数限制
- Token 登录态、退出登录
- 获取当前管理员信息 / 菜单 / 权限节点

---

## 20. 文件上传

由 System 统一管理附件。存储驱动可扩展：`local` / `public` / `s3` 及 OSS、COS、七牛等（可通过插件适配）。

附件记录通常包含：原名、路径、URL、大小、MIME、驱动、上传人、时间。

使用 **public** 磁盘时需执行：

```bash
php artisan storage:link
```

详见 [docs/deployment/public-storage-link.md](docs/deployment/public-storage-link.md)。

---

## 21. 日志审计

监控中心记录操作 / 登录 / 异常。操作审计字段建议包含：

操作人、模块、动作、方法、地址、参数、IP、UA、响应状态、耗时、时间。

敏感字段脱敏：`password`、`token`、`secret`、`authorization` 等。

---

## 22. 文档索引

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
| [docs/api/](docs/api/) | 路由与权限码 |

内核包说明：[packages/core/README.md](packages/core/README.md)。

---

## 23. 相关仓库

| 仓库 | 用途 |
|------|------|
| [alin40404/starship-admin](https://github.com/alin40404/starship-admin) | 本仓（GitHub） |
| [allanchan004/starship-admin](https://gitee.com/allanchan004/starship-admin) | 本仓（Gitee） |
| [alin40404/starship-core](https://github.com/alin40404/starship-core) | 内核 Packagist **发布仓**（subtree 同步，不在此仓日常开发） |

---

## License

[MIT](LICENSE) © allanchan
