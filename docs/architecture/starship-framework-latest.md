# Starship Admin 框架架构（Module / Plugin / Theme 模型）

> 本文定义 Starship Admin 作为可复用企业级开发底座的架构规范。
>
> 当前阶段目标：单人开发、项目复用、模块沉淀。
> 不急于建设市场生态，但提前保留生态化能力。

------------------------------------------------------------------------

# 1. 框架定位

Starship Admin 是：

-   Laravel 企业级后台底座
-   可复用模块体系
-   可扩展插件体系
-   前台主题体系
-   CRUD 自动生成平台

核心目标：

-   Core 稳定
-   Module 可复用
-   Plugin 可安装卸载
-   Theme 可安装切换

------------------------------------------------------------------------

# 2. 总体架构

    Layer 5 Application
        |
        | 具体项目组合
        |
    Layer 4 Theme
        |
        | 前台展示主题
        |
    Layer 3 Plugin
        |
        | 第三方能力扩展
        |
    Layer 2 Module
        |
        | 业务能力模块
        |
    Layer 1 Core
        |
        | 模块/插件/主题加载引擎
        |
    Layer 0 Laravel Shell

------------------------------------------------------------------------

# 3. Core（packages/core）

职责：

-   Module Loader
-   Plugin Manager
-   Theme Manager
-   Event 系统
-   Contract 接口
-   ApiResponse
-   全局基础能力

原则：

Core 不包含业务。

禁止：

-   商品
-   订单
-   CRM
-   CMS

------------------------------------------------------------------------

# 4. Module 设计

## 定义

Module 是完整业务能力。

特点：

-   有业务模型
-   有数据库迁移
-   有菜单
-   有权限
-   有业务流程

例如：

    modules/

    System
    Generator
    CRM
    Shop
    CMS
    Workflow

------------------------------------------------------------------------

## 当前阶段策略

Module 不强制安装卸载。

采用：

-   目录复制
-   Git 管理
-   Composer path 引用

方式复用。

例如：

项目A：

    modules/

    System
    Generator
    CRM

项目B：

    modules/

    System
    Generator
    Shop

------------------------------------------------------------------------

## Module 需要支持

不是完整生态安装，而是：

-   自动发现
-   Provider加载
-   启用/禁用

module.json:

``` json
{
"name":"shop",
"title":"商城",
"version":"1.0.0",
"enabled":true,
"providers":[
"Modules\\Shop\\Providers\\ShopServiceProvider"
]
}
```

------------------------------------------------------------------------

# 5. Plugin 设计

Plugin 用于：

-   第三方服务
-   可替换能力
-   外部扩展

例如：

    plugins/

    PaymentWechat

    PaymentAlipay

    StorageOSS

    SmsAliyun

    OpenAI

生命周期：

    发现
     ↓
    安装
     ↓
    启用
     ↓
    禁用
     ↓
    卸载

CLI：

    starship:plugin:install
    starship:plugin:enable
    starship:plugin:disable
    starship:plugin:uninstall

------------------------------------------------------------------------

# 6. Theme 设计

Theme 专门负责展示。

不属于 Plugin。

例如：

    themes/

    shop-fashion

    shop-school

    cms-government

生命周期：

    发现
     ↓
    安装
     ↓
    启用
     ↓
    切换
     ↓
    卸载

适合：

-   CMS官网
-   商城前台
-   营销页面

后台不做复杂 Theme，仅支持：

-   深色模式
-   主色配置
-   Layout配置

------------------------------------------------------------------------

# 7. 三者区别

             Module     Plugin     Theme
  ---------- ---------- ---------- ----------
  作用       业务能力   扩展能力   展示
  数据库     大量       少量       无
  菜单权限   有         少量       无
  安装卸载   暂不强制   支持       支持
  例子       CRM        支付宝     商城模板

判断：

> 有业务对象 → Module\
> 有第三方实现 → Plugin\
> 有展示页面 → Theme

------------------------------------------------------------------------

# 8. 演进路线

## 阶段一

完成：

-   Core
-   System
-   Generator
-   Plugin生命周期
-   Theme加载

## 阶段二

沉淀：

-   CRM Module
-   Shop Module
-   CMS Module

## 阶段三

成熟模块转 Composer 包。

## 阶段四

未来再考虑：

-   Module Market
-   Plugin Market
-   Theme Market

------------------------------------------------------------------------

# 9. 最终目标

Starship：

    Core

     +
    Module

     +
    Plugin

     +
    Theme

     =
    可组合应用平台

当前重点：

不要过早做市场。

先把：

模块边界 插件规范 主题规范

做好。
