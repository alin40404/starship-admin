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

    modules/Shop/


    ├── module.json

    ├── app/

    │   ├── Models

    │   ├── Services

    │   ├── Controllers

    │   ├── Repositories

    │   └── Policies


    ├── routes/

    │   ├── admin.php

    │   └── api.php


    ├── database/

    │   ├── migrations

    │   └── seeders


    ├── resources/

    │   ├── lang

    │   └── views


    ├── permissions/

    └── Providers/

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

例如：

    shop.goods.create

    shop.goods.update

    shop.order.refund

------------------------------------------------------------------------

# 6. 菜单规范

module.json声明：

    菜单

    路由

    权限

例如：

    商城管理

     ├ 商品管理

     └ 订单管理

------------------------------------------------------------------------

# 7. API规范

统一：

    /admin-api/shop/products

    /admin-api/shop/orders

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

    PaymentInterface

    ↓

    Plugin实现

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

# 11. Module版本规划

当前：

目录复用。

未来：

Composer包。

例如：

    starship/crm

    starship/shop

    starship/cms

------------------------------------------------------------------------

# 12. 最终原则

Module负责：

业务。

Plugin负责：

外部能力。

Theme负责：

展示。

Core负责：

规则。
