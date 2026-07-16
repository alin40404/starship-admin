# Starship Module 开发规范

## 1. Module定位

Module 是 Starship 的业务能力单元。

例如：

    System

    Generator

    CRM

    Shop

    CMS

    Workflow

Module特点：

-   独立目录
-   独立命名空间
-   独立数据库
-   独立权限
-   独立菜单

------------------------------------------------------------------------

# 2. Module目录规范

以 `starship:module:make Shop` 脚手架生成为准：

```
modules/Shop/
├── composer.json
├── module.json
├── app/
│   ├── Http/Controllers/Admin/
│   ├── Models/
│   ├── Services/
│   ├── Support/          # *Permissions.php 等
│   └── Providers/
│       └── ShopServiceProvider.php
├── routes/
│   └── admin.php
├── database/
│   ├── migrations/
│   └── seeders/
└── resources/
    └── lang/
```

说明：

- Controller 位于 `app/Http/Controllers/Admin/`，非根级 `app/Controllers`
- Provider 位于 `app/Providers/`，非根级 `Providers/`
- 权限码为 `app/Support/*Permissions.php` 常量类，无独立 `permissions/` 目录
- Repository / Policies 层按需添加，非脚手架默认内容

------------------------------------------------------------------------

# 3. 分层规范

推荐：

    Controller

    ↓

    Service

    ↓

    Repository

    ↓

    Model

Controller：

负责参数接收。

Service：

负责业务流程。

Repository：

负责数据访问。

Model：

负责数据结构。

------------------------------------------------------------------------

# 4. 数据库规范

表名：

    sa_shop_orders

    sa_shop_products

字段：

统一：

    id

    created_at

    updated_at

    deleted_at

------------------------------------------------------------------------

# 5. 权限规范

格式：

    模块.资源.操作

按钮操作使用 `query` / `create` / `update` / `delete` 等，**不要**用 `index`。

例如：

    system.admins.query

    shop.goods.create

    shop.order.refund

实现约定：

- 后端：`app/Support/*Permissions.php` 常量类
- 前端：`apps/admin/web-antd/src/features/{module}/constants/*-permissions.ts`
- 路由：`->middleware('admin.permission:'.XxxPermissions::QUERY)`

完整清单：[docs/api/permissions.md](../api/permissions.md)

------------------------------------------------------------------------

# 6. 菜单规范

在 `module.json` 中声明本模块菜单 path 与权限归属，供禁用模块时过滤：

```json
{
  "menus": {
    "paths": [
      "/dev/codegen",
      "/dev/products"
    ]
  },
  "permissions": {
    "prefixes": ["generator."],
    "exact": []
  }
}
```

要求：

- `menus.paths` 须覆盖该模块全部页面 path（与 MenuSeeder / 生成菜单一致）
- 共享父目录（如 `/dev`）归 System；子页 `/dev/codegen` 归 Generator
- 由 `starship:generator:crud` 生成的业务页 path：在 `sync_menu` 开启时会**精确追加**到目标模块 `module.json`；`--delete` 时**仅删除该实体的 `route_path`**，不会清掉其它 path（如 `/dev/codegen`）

例如：

    开发工具

     ├ 代码生成   → Generator
     ├ 模块管理   → System
     └ 插件管理   → System

------------------------------------------------------------------------

# 7. API规范

统一前缀：

    /admin-api/{模块或业务资源}

例如：

    /admin-api/shop/products

    /admin-api/system/admins

    /admin-api/auth/login

详见：[docs/api/routes.md](../api/routes.md)

响应：

``` json
{
"code":0,
"message":"success",
"data":{}
}
```

------------------------------------------------------------------------

# 8. 前端规范

模块页面：

    apps/admin/src/views/modules/shop

API：

    apps/admin/src/api/shop

保持：

后端模块

前端模块

名称一致。

------------------------------------------------------------------------

# 9. Module依赖规范

允许：

    Shop

    依赖

    System

不允许：

    Shop

    直接调用

    PaymentAlipay

应该：

    Shop 先创建业务订单

    PaymentInterface::prepay($orderNo, ...)

    ↓

    Plugin 通道实现

------------------------------------------------------------------------

# 10. Module通信规范

禁止：

模块直接调用。

推荐：

Event。

例如：

订单创建：

    OrderCreatedEvent

    ↓

    短信插件

    ↓

    积分模块

------------------------------------------------------------------------

# 11. Module 导出 / 导入

当前目标：项目间复用（A 导出 → B 导入），**不上市场、不强制 Composer**。

- 后端：`modules/{Name}/`
- 管理端前端：`apps/admin/web-antd/src/features/{name}/`
- 验收清单（Member 试点）：[module-export-import-acceptance.md](./module-export-import-acceptance.md)
- CLI：`starship:module:export` / `import`，见 [module-cli.md](./module-cli.md)

# 12. Module版本规划

当前：

目录复用。

未来：

Composer包。

例如：

    starship/crm

    starship/shop

    starship/cms

------------------------------------------------------------------------

# 13. 最终原则

Module负责：

业务。

Plugin负责：

外部能力。

Theme负责：

展示。

Core负责：

规则。
