# Starship 管理端 API 路由参考

> 完整定义见 `modules/*/routes/admin.php` 与 `admin-generator/*.php`。  
> 统一前缀：`/admin-api`。鉴权：`auth:sanctum`（公开接口除外）。  
> 更新日：2026-07-14

## 1. 前缀一览

| 前缀 | 模块 | 说明 |
|------|------|------|
| `/admin-api/auth/*` | System | 登录、资料、菜单/权限 |
| `/admin-api/system/*` | System | 业务 CRUD、选项接口、站点信息 |
| `/admin-api/dev/*` | System | 模块 / 插件管理 |
| `/admin-api/monitor/*` | System | 操作 / 登录 / 异常日志 |
| `/admin-api/inbox/*` | System | 站内消息 |
| `/admin-api/generator/*` | Generator | 代码生成 + Demo CRUD |

错误示例（勿用）：~~`/admin-api/system/auth/login`~~ → `POST /admin-api/auth/login`。

## 2. Auth（公开 + 登录后）

| 方法 | 路径 | 权限 |
|------|------|------|
| POST | `/admin-api/auth/login` | 公开 |
| POST | `/admin-api/auth/sms-code` | 公开 |
| POST | `/admin-api/auth/sms-login` | 公开 |
| POST | `/admin-api/auth/forgot-password` | 公开 |
| GET | `/admin-api/auth/reset-password/verify` | 公开 |
| POST | `/admin-api/auth/reset-password` | 公开 |
| POST | `/admin-api/auth/logout` | 登录 |
| GET | `/admin-api/auth/me` | 登录 |
| PUT | `/admin-api/auth/profile` | 登录 |
| PUT | `/admin-api/auth/password` | 登录 |
| GET | `/admin-api/auth/menus` | 登录 |
| GET | `/admin-api/auth/permissions` | 登录 |

## 3. System 业务（节选）

路径均在 `/admin-api/system/` 下，中间件权限与同名 `*Permissions` 常量一致。

| 资源 path | 典型权限前缀 | 前端页面 |
|-----------|--------------|----------|
| `admins` | `system.admins.*` | `/permission/admins` |
| `roles` | `system.roles.*` | `/permission/roles` |
| `menus` | `system.menus.*` | `/permission/menus` |
| `departments` | `system.departments.*` | `/org/departments` |
| `posts` | `system.posts.*` | `/org/posts` |
| `user-groups` | `system.user_groups.*` | `/org/user-groups` |
| `configs` | `system.configs.*` | `/platform/configs` |
| `dict-types` / `dict-items` | `system.dicts.*` | `/platform/dicts` |
| `categories` | `system.categories.*` | `/platform/categories` |
| `areas` | `system.regions.*` | `/platform/regions` |
| `attachments` | `system.attachments.*` | `/resource/attachments` |

其它：

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin-api/system/site` | 站点公开配置 |
| GET | `/admin-api/system/runtime` | 运行时信息 |
| GET | `/admin-api/system/*-options/*` | 下拉/树选项（多数仅需登录） |

## 4. Dev

| 方法 | 路径 | 权限 |
|------|------|------|
| GET/PUT 等 | `/admin-api/dev/modules` | `system.modules.*` |
| GET/POST 等 | `/admin-api/dev/plugins`、`.../discover`、`.../{slug}/install` 等 | `system.plugins.*` |

## 5. Monitor

| 资源 | 路径前缀 | 权限 |
|------|----------|------|
| 操作日志 | `/admin-api/monitor/operation-logs` | `monitor.operation_logs.*` |
| 登录日志 | `/admin-api/monitor/login-logs` | `monitor.login_logs.*` |
| 异常日志 | `/admin-api/monitor/exception-logs` | `monitor.exception_logs.*` |

## 6. Inbox

| 方法 | 路径 | 权限 |
|------|------|------|
| GET 等 | `/admin-api/inbox/messages` | `dashboard.inbox` |

## 7. Generator

均在 `/admin-api/generator/`（开发环境中间件 `generator.dev` 作用于工具接口）：

| 路径 | 权限 |
|------|------|
| `schemas/tables`、`schemas/tables/{table}/columns` | `generator.tables.query` |
| `tables`、`tables/import`、`tables/{id}` | `generator.tables.*` |
| `tables/{id}/preview`、`generate` | `generator.codegen.preview/generate` |
| `records` | `generator.records.query` |
| `products`（Demo CRUD） | `generator.products.*` |

Demo 路由由 `routes/admin-generator/demo_products.php` 加载。
