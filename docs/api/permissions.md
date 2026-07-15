# Starship 权限码参考

> 与代码保持一致；变更以各模块 `app/Support/*Permissions.php` 与前端 `constants/*-permissions.ts` 为准。  
> 更新日：2026-07-14

## 1. 约定

| 项 | 说明 |
|----|------|
| 格式 | `{模块}.{资源}.{操作}` |
| 页面权限 | 常省略操作段，如 `system.admins`、`generator.codegen` |
| 按钮权限 | `query` / `create` / `update` / `delete` / `batch_*` / `status` / `sort` 等 |
| 旧命名 | ~~`system.admin.index`~~ → `system.admins.query` |
| 模块归属 | `module.json` 的 `permissions.prefixes` / `exact`；禁用模块时由 `ModuleMenuFilter` 过滤 |

## 2. module.json 绑定

| 模块 | prefixes | menus.paths（摘要） |
|------|----------|---------------------|
| System | `system.` `dashboard.` `monitor.` | `/dashboard/*` `/permission/*` `/org/*` `/platform/*` `/resource/attachments` `/monitor/*` `/dev` `/dev/modules` `/dev/plugins` `/dev/themes` |
| Generator | `generator.` | `/dev/codegen`；业务 CRUD 如 `/dev/products` 由 codegen 在 `sync_menu` 时自动写入 |

完整清单见各自 `modules/*/module.json`。

## 3. 页面权限（MenuSeeder）

### dashboard.*

| 权限码 | 前端 path |
|--------|-----------|
| `dashboard.view` | `/dashboard/console` |
| `dashboard.overview` | `/dashboard/overview` |
| `dashboard.todos` | `/dashboard/todos` |
| `dashboard.quick_entry` | `/dashboard/quick-entry` |
| `dashboard.favorites` | `/dashboard/favorites` |
| `dashboard.inbox` | `/dashboard/inbox` |

### system.*（页面）

| 权限码 | 前端 path |
|--------|-----------|
| `system.menus` | `/permission/menus` |
| `system.roles` | `/permission/roles` |
| `system.admins` | `/permission/admins` |
| `system.departments` | `/org/departments` |
| `system.posts` | `/org/posts` |
| `system.user_groups` | `/org/user-groups` |
| `system.configs` | `/platform/configs` |
| `system.dicts` | `/platform/dicts` |
| `system.categories` | `/platform/categories` |
| `system.regions` | `/platform/regions` |
| `system.attachments` | `/resource/attachments` |
| `system.modules` | `/dev/modules` |
| `system.plugins` | `/dev/plugins` |
| `system.themes` | `/dev/themes` |

### monitor.*（页面）

| 权限码 | 前端 path |
|--------|-----------|
| `monitor.operation_logs` | `/monitor/operation-logs` |
| `monitor.login_logs` | `/monitor/login-logs` |
| `monitor.exception_logs` | `/monitor/exception-logs` |

### generator.*（页面）

| 权限码 | 前端 path |
|--------|-----------|
| `generator.codegen` | `/dev/codegen` |
| （按钮见下） | `/dev/products` |

## 4. 按钮 / API 权限常量

### System — `modules/System/app/Support/`

| 类 | 权限码 |
|----|--------|
| `AdminPermissions` | `system.admins.query/create/update/delete/batch_delete/batch_status/status` |
| `RolePermissions` | `system.roles.query/create/update/delete/batch_delete/batch_status/status` |
| `MenuPermissions` | `system.menus.query/create/append/update/delete/batch_delete/batch_status/status/sort` |
| `DepartmentPermissions` | `system.departments.query/create/append/update/delete/batch_delete/batch_status/status/sort` |
| `PostPermissions` | `system.posts.query/create/update/delete/batch_delete/batch_status` |
| `UserGroupPermissions` | `system.user_groups.query/create/update/delete/batch_delete` |
| `ConfigPermissions` | `system.configs.query/update/delete/batch_delete` |
| `DictPermissions` | `system.dicts.query/create/update/delete` |
| `CategoryPermissions` | `system.categories.query/create/append/update/delete/batch_delete/batch_status/status/sort` |
| `AreaPermissions` | `system.regions.query/create/append/update/delete/batch_delete` |
| `AttachmentPermissions` | `system.attachments.query/upload/delete/batch_delete` |
| `ModulePermissions` | `system.modules.query/update` |
| `PluginPermissions` | `system.plugins.query/discover/install/enable/disable/uninstall/config` |
| `ThemePermissions` | `system.themes.query/discover/install/enable/disable/uninstall/delete/switch` |
| `InboxPermissions` | `dashboard.inbox`（消息接口） |
| `OperationLogPermissions` | `monitor.operation_logs` + `.query/.delete/.batch_delete` |
| `LoginLogPermissions` | `monitor.login_logs` + `.query/.delete/.batch_delete` |
| `ExceptionLogPermissions` | `monitor.exception_logs` + `.query/.delete/.batch_delete` |

### Generator — `modules/Generator/app/Support/`

| 类 | 权限码 |
|----|--------|
| `GeneratorPermissions` | `generator.codegen`；`generator.tables.query/import/update/delete`；`generator.codegen.preview/generate`；`generator.records.query` |
| `ProductsPermissions` | `generator.products.query/create/update/delete/batch_delete/batch_status` |

## 5. 相关文件

- 菜单种子：`modules/System/database/seeders/MenuSeeder.php`
- 路由权限中间件：`admin.permission:{code}`（见 [routes.md](./routes.md)）
- 前端常量：`apps/admin/web-antd/src/features/{module}/constants/*-permissions.ts`
