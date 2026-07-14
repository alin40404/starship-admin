# Starship 权限与菜单体系设计方案

> **实现状态（2026-07-14）**：当前落地为 **MenuSeeder + `*Permissions.php` + `module.json` 的 menus/permissions 过滤**（`ModuleMenuFilter`），并不是本文早期设计的「各模块 `menu.json` 自动写入」。权限码与 API 的权威参考：[`docs/api/permissions.md`](../api/permissions.md)、[`docs/api/routes.md`](../api/routes.md)。下文保留为设计演进稿，示例中的 `system.admin.create` 等旧命名以权威文档为准。

## 1. 设计目标

Starship 是模块化平台，因此权限和菜单不能写死在 System 中。

目标：

-   Module 自动注册菜单
-   Module 自动注册权限
-   Plugin 支持配置入口
-   禁用模块自动隐藏入口
-   权限体系可扩展
-   支持未来多租户

核心原则：

    Module提供能力

    System管理权限

    Core负责注册机制

------------------------------------------------------------------------

# 2. 权限架构

目录：

    System Module

    负责：

    用户

    角色

    权限

    菜单


    Core

    负责：

    权限注册接口

    菜单注册接口

------------------------------------------------------------------------

# 3. 权限模型设计

推荐 RBAC：

    User

     |

    Role

     |

    Permission

数据库：

    sa_users

    sa_roles

    sa_permissions

    sa_role_permissions

    sa_user_roles

------------------------------------------------------------------------

# 4. 权限命名规范

格式：

    模块.资源.操作

例如：

商城：

    shop.goods.list

    shop.goods.create

    shop.goods.update

    shop.order.refund

CRM：

    crm.customer.list

    crm.customer.follow

系统：

    system.admin.create

------------------------------------------------------------------------

# 5. Module权限自动注册

Module:

module.json

``` json
{
"permissions":{

"prefixes":[
"shop."
]

}
}
```

启动：

    ModuleLoader

    ↓

    读取权限声明

    ↓

    注册Permission

    ↓

    写入权限表

------------------------------------------------------------------------

# 6. 菜单体系设计

菜单属于 System 管理。

但是来源属于 Module。

结构：

    Module

    提供菜单定义


            |

            ↓


    System Menu Manager


            |

            ↓


    数据库菜单

------------------------------------------------------------------------

# 7. menu.json设计

示例：

``` json
{
"menus":[

{
"title":"商城管理",

"path":"/shop",

"icon":"shop",

"permission":"shop.goods.list",

"children":[

{
"title":"商品管理",

"path":"/shop/goods",

"permission":"shop.goods.list"

}

]

}

]
}
```

------------------------------------------------------------------------

# 8. Module启用流程

    Enable Module


    ↓

    读取module.json


    ↓

    注册权限


    ↓

    注册菜单


    ↓

    加载Route


    ↓

    系统生效

------------------------------------------------------------------------

# 9. Module禁用流程

    Disable Module


    ↓

    停止Provider


    ↓

    隐藏菜单


    ↓

    禁用权限


    ↓

    保留数据

注意：

默认不要删除数据。

------------------------------------------------------------------------

# 10. Plugin权限设计

Plugin一般只增加配置权限。

例如：

支付宝：

    payment.alipay.config

短信：

    sms.aliyun.config

Plugin:

plugin.json

声明：

``` json
{
"permissions":[
"payment.alipay.config"
]
}
```

------------------------------------------------------------------------

# 11. Generator如何参与

Generator生成：

同时生成：

-   Controller
-   Service
-   Model
-   Migration
-   Permission
-   Menu

例如：

生成：

    shop_goods

自动产生：

权限：

    shop.goods.list

    shop.goods.create

    shop.goods.update

    shop.goods.delete

菜单：

    商品管理

------------------------------------------------------------------------

# 12. 前端权限控制

后端：

必须验证权限。

前端：

用于展示。

例如：

Vue：

``` js
v-permission="'shop.goods.create'"
```

流程：

    登录

    ↓

    获取用户权限

    ↓

    生成动态路由

    ↓

    渲染菜单

------------------------------------------------------------------------

# 13. 多租户扩展

未来：

增加：

    tenant_id

权限范围：

    Tenant

    ↓

    Role

    ↓

    Permission

例如：

A公司管理员：

只能看到A数据。

------------------------------------------------------------------------

# 14. 权限缓存

建议：

Redis缓存：

    user_permissions:{id}

    user_menus:{id}

修改权限后：

清理缓存。

------------------------------------------------------------------------

# 15. Contract扩展

Core定义：

``` php
interface PermissionRegistrarInterface
{

public function register(array $permissions);

}


interface MenuRegistrarInterface
{

public function register(array $menus);

}
```

Module调用：

不直接操作数据库。

------------------------------------------------------------------------

# 16. 最终架构

                 Core


         MenuRegistrar

         PermissionRegistrar


                  |


    --------------------------------


    System Module

    负责存储和管理


    Module

    提供声明


    Plugin

    提供扩展


    Generator

    自动生成

------------------------------------------------------------------------

# 17. 最终原则

    权限属于System

    声明属于Module

    注册属于Core

    展示属于Frontend

这样才能支持：

-   模块复用
-   插件扩展
-   动态菜单
-   未来生态化
