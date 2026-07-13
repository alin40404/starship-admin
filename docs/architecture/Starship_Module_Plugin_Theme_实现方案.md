# Starship Admin Module / Plugin / Theme 扩展体系实现方案

## 1. 目标

Starship Admin 目标不是简单后台框架，而是：

-   可复用业务模块体系
-   可扩展插件体系
-   前台主题体系
-   可组合应用平台

当前阶段：

-   不建设市场系统
-   不强制 Module 安装卸载
-   Module 以复用为主
-   Plugin / Theme 实现安装卸载生命周期

最终目标：

    Core
     |
     + Module
     |
     + Plugin
     |
     + Theme
     |
     Application

------------------------------------------------------------------------

# 2. 扩展体系设计

## 2.1 Module

定义：

Module 是完整业务能力。

例如：

    System
    Generator
    CRM
    Shop
    CMS
    Workflow

特点：

-   有业务模型
-   有数据库
-   有权限
-   有菜单
-   有业务流程

复用方式：

当前阶段：

-   复制目录
-   Git管理
-   Composer path

未来：

-   Composer包
-   Module市场

------------------------------------------------------------------------

## 2.2 Plugin

定义：

Plugin 是可插拔能力。

例如：

    PaymentWechat

    PaymentAlipay

    StorageOSS

    SmsAliyun

    OpenAI

特点：

-   独立安装
-   独立卸载
-   可替换
-   面向第三方服务

生命周期：

    发现
     ↓
    校验
     ↓
    安装
     ↓
    启用
     ↓
    禁用
     ↓
    升级
     ↓
    卸载

------------------------------------------------------------------------

## 2.3 Theme

定义：

Theme 是展示层扩展。

例如：

    shop-fashion

    cms-company

    school-theme

特点：

-   前台页面
-   样式
-   布局
-   静态资源

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

------------------------------------------------------------------------

# 3. Core 架构设计

目录：

    packages/core/

    src/

    ├── Module
    │
    ├── Plugin
    │
    ├── Theme
    │
    ├── Event
    │
    ├── Contracts
    │
    └── Support

------------------------------------------------------------------------

# 4. ModuleManager 实现方案

## 4.1 职责

负责：

-   Module扫描
-   配置读取
-   Provider加载
-   生命周期管理

不负责：

-   Module业务逻辑

------------------------------------------------------------------------

## 4.2 核心类

    ModuleManager

    ModuleScanner

    ModuleLoader

    ModuleRepository

    ModuleValidator

------------------------------------------------------------------------

## 4.3 加载流程

    Laravel启动

    ↓

    ModuleScanner扫描 modules/

    ↓

    读取 module.json

    ↓

    检查依赖

    ↓

    加载ServiceProvider

    ↓

    注册Route

    ↓

    注册Migration

    ↓

    完成启动

------------------------------------------------------------------------

# 5. module.json规范

示例：

``` json
{
"name":"shop",

"title":"商城",

"version":"1.0.0",

"type":"business",

"namespace":"Modules\\Shop",

"enabled":true,

"require":{
    "modules":[
        "System"
    ]
},

"providers":[
    "Modules\\Shop\\Providers\\ShopServiceProvider"
]
}
```

字段：

  字段        说明
  ----------- ------------
  name        模块标识
  title       名称
  version     版本
  type        模块类型
  require     依赖
  providers   服务提供者

------------------------------------------------------------------------

# 6. PluginManager 实现方案

## 6.1 职责

负责：

-   插件安装
-   插件卸载
-   配置管理
-   Provider加载

------------------------------------------------------------------------

## 6.2 核心类

    PluginManager

    PluginInstaller

    PluginLoader

    PluginRepository

    PluginConfigManager

------------------------------------------------------------------------

## 6.3 安装流程

执行：

    starship:plugin:install payment-wechat

流程：

    读取plugin.json

    ↓

    检查依赖

    ↓

    写入插件记录

    ↓

    执行migration

    ↓

    发布配置

    ↓

    注册Provider

    ↓

    启用插件

------------------------------------------------------------------------

# 7. plugin.json规范

示例：

``` json
{
"name":"PaymentWechat",

"slug":"payment-wechat",

"type":"payment",

"version":"1.0.0",

"namespace":"Plugins\\PaymentWechat",

"providers":[
"Plugins\\PaymentWechat\\Providers\\ServiceProvider"
],

"migration":true
}
```

------------------------------------------------------------------------

# 8. ThemeManager 实现方案

## 8.1 职责

负责：

-   主题发现
-   安装
-   激活
-   配置

------------------------------------------------------------------------

## 8.2 核心类

    ThemeManager

    ThemeScanner

    ThemeLoader

    ThemeRepository

------------------------------------------------------------------------

## 8.3 加载流程

    用户选择主题

    ↓

    ThemeManager

    ↓

    读取theme.json

    ↓

    加载入口

    ↓

    加载资源

    ↓

    渲染页面

------------------------------------------------------------------------

# 9. theme.json规范

示例：

``` json
{
"name":"shop-fashion",

"title":"商城主题",

"type":"frontend",

"version":"1.0.0",

"require":{
    "modules":[
        "Shop"
    ]
},

"entry":{
    "layout":"layouts/default.vue"
}
}
```

------------------------------------------------------------------------

# 10. 数据库设计

## 插件表

    sa_plugins

    id

    slug

    name

    version

    status

    installed_at

------------------------------------------------------------------------

## 插件配置表

    sa_plugin_configs

    plugin

    key

    value

------------------------------------------------------------------------

## 主题表

    sa_themes

    id

    name

    type

    version

    status

    config

------------------------------------------------------------------------

Module当前不需要独立安装表。

------------------------------------------------------------------------

# 11. Event系统设计

目的：

降低模块耦合。

错误：

    订单服务

    调用

    短信服务

正确：

    OrderCreatedEvent

            |

    ----------------

    SMS Plugin

    积分Module

    统计Module

------------------------------------------------------------------------

事件目录：

    packages/core/Event

支持：

-   Module事件
-   Plugin事件
-   Theme事件

------------------------------------------------------------------------

# 12. 开发顺序

## 第一阶段

必须完成：

    ModuleManager

    PluginManager

    module.json

    plugin.json

目标：

System

Generator

Plugin运行。

------------------------------------------------------------------------

## 第二阶段

完成：

    ThemeManager

    theme.json

目标：

CMS商城前台主题。

------------------------------------------------------------------------

## 第三阶段

完成：

    EventBus

    Contract体系

目标：

生态扩展。

------------------------------------------------------------------------

# 13. 最终目录

    starship/


    packages/core/


    modules/

    System

    Generator

    CRM

    Shop


    plugins/

    PaymentWechat

    StorageOSS

    SmsAliyun


    themes/

    shop-fashion

    cms-company

------------------------------------------------------------------------

# 14. 最终原则

一句话：

    Module 做业务

    Plugin 做扩展

    Theme 做展示

    Core 做规则

不要让：

-   Plugin承载大型业务
-   Module依赖具体厂商
-   Theme包含业务逻辑

保持边界清晰，未来才能扩展生态。
