# Starship Plugin 开发规范

## 1. Plugin 定位

Plugin 是 Starship 的可插拔扩展能力。

主要解决：

-   第三方服务接入
-   可替换实现
-   平台能力扩展

典型：

    plugins/

    PaymentWechat

    PaymentAlipay

    StorageOSS

    SmsAliyun

    OpenAI

    OAuthGoogle

原则：

Plugin 不承载大型业务。

不要：

    plugins/Shop
    plugins/CRM

业务系统应该使用 Module。

------------------------------------------------------------------------

# 2. Plugin 类型规范

plugin.json 中 type：

    payment
    storage
    sms
    email
    oauth
    ai
    map
    search

示例：

    PaymentWechat

    type=payment

------------------------------------------------------------------------

# 3. Plugin 目录结构

    plugins/PaymentWechat/


    ├── plugin.json

    ├── src/

    │   ├── Providers

    │   ├── Services

    │   ├── Contracts

    │   └── Drivers


    ├── config/

    ├── routes/

    ├── database/

    │   └── migrations


    ├── resources/

    └── README.md

------------------------------------------------------------------------

# 4. plugin.json规范

示例：

``` json
{
"name":"PaymentWechat",

"slug":"payment-wechat",

"title":"微信支付",

"version":"1.0.0",

"type":"payment",

"namespace":"Plugins\\PaymentWechat",

"require":{

"modules":[
"System"
]

},

"providers":[

"Plugins\\PaymentWechat\\Providers\\ServiceProvider"

],

"migration":true

}
```

------------------------------------------------------------------------

# 5. Plugin生命周期

## Discover

扫描：

    plugins/*

读取：

    plugin.json

------------------------------------------------------------------------

## Install

执行：

-   创建插件记录
-   执行migration
-   发布配置

------------------------------------------------------------------------

## Enable

加载：

-   Provider
-   Route
-   Service

------------------------------------------------------------------------

## Disable

停止：

-   Provider加载
-   Route注册

------------------------------------------------------------------------

## Upgrade

执行：

    upgrade.php

------------------------------------------------------------------------

## Uninstall

执行：

-   清理配置
-   删除插件记录
-   执行卸载脚本

------------------------------------------------------------------------

# 6. Plugin接口设计

例如支付：

定义：

    PaymentInterface

Core：

``` php
interface PaymentInterface
{

public function pay();

public function refund();

public function query();

}
```

插件实现：

    WechatPayment

    AlipayPayment

    StripePayment

------------------------------------------------------------------------

# 7. Plugin配置管理

统一：

    sa_plugin_configs

例如：

    payment-wechat

    appid

    secret

    mch_id

不要：

插件自己创建配置体系。

------------------------------------------------------------------------

# 8. Plugin事件通信

不要：

    OrderService

    调用

    SmsPlugin

应该：

    OrderCreatedEvent

    ↓

    Plugin监听

------------------------------------------------------------------------

# 9. Plugin安全规范

第三方插件：

需要：

-   权限声明
-   版本声明
-   依赖声明
-   配置隔离

------------------------------------------------------------------------

# 10. 最终原则

Plugin：

负责连接世界。

Module：

负责业务。

Core：

负责规则。
