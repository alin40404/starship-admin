# Starship Contract 接口体系设计方案

## 1. 设计目标

Contract 是 Starship 解耦 Module、Plugin、Theme 的核心机制。

目标：

-   Module 不依赖具体实现
-   Plugin 可以自由替换
-   第三方能力统一接入
-   保持 Core 稳定

核心原则：

    依赖接口

    不要依赖实现

------------------------------------------------------------------------

# 2. 为什么需要 Contract

错误方式：

    Shop Module

    直接调用

    PaymentWechat Plugin

问题：

-   Shop 被绑定微信支付
-   无法切换支付宝
-   无法复用

正确方式：

    Shop Module

        |
        |
    PaymentInterface

        |
        |

    Wechat Plugin

    Alipay Plugin

    Stripe Plugin

------------------------------------------------------------------------

# 3. Contract 所属位置

建议：

    packages/core/src/Contracts/


    ├── Payment

    ├── Storage

    ├── Notification

    ├── User

    ├── Tenant

    ├── Search

    └── Workflow

Core 定义规则。

Module 和 Plugin 实现。

------------------------------------------------------------------------

# 4. Contract 分类

## 4.1 基础能力 Contract

例如：

    UserProviderInterface

    StorageInterface

    CacheInterface

    QueueInterface

------------------------------------------------------------------------

## 4.2 业务能力 Contract

例如：

    PaymentInterface

    NotificationInterface

    SearchInterface

    WorkflowInterface

------------------------------------------------------------------------

## 4.3 平台能力 Contract

例如：

    TenantResolverInterface

    PermissionCheckerInterface

    ConfigRepositoryInterface

------------------------------------------------------------------------

# 5. StorageInterface设计

用途：

统一文件存储。

接口：

``` php
interface StorageInterface
{

public function put(
string $path,
mixed $content
);


public function get(
string $path
);


public function delete(
string $path
);


public function url(
string $path
);

}
```

实现：

    LocalStorage

    AliOSSStorage

    QiniuStorage

    TencentCOSStorage

------------------------------------------------------------------------

# 6. PaymentInterface设计

用途：

支付能力。

接口：

``` php
interface PaymentInterface
{


public function createOrder(
array $data
);


public function pay(
string $orderNo
);


public function refund(
string $orderNo
);


public function query(
string $orderNo
);


}
```

实现：

    WechatPayPlugin

    AlipayPlugin

    StripePlugin

------------------------------------------------------------------------

# 7. NotificationInterface设计

统一消息能力。

接口：

``` php
interface NotificationInterface
{

public function send(
string $receiver,
string $template,
array $data
);


}
```

实现：

    SmsAliyun

    SmsTencent

    EmailSMTP

    WechatWork

------------------------------------------------------------------------

# 8. UserProviderInterface设计

解决模块依赖用户。

不要：

    CRM

    依赖

    System User Model

应该：

    CRM

    UserProviderInterface

    System实现

接口：

``` php
interface UserProviderInterface
{

public function find(
string $id
);


public function current();


}
```

------------------------------------------------------------------------

# 9. Contract绑定机制

Laravel Service Container：

例如：

``` php
$this->app->bind(
StorageInterface::class,
AliOSSStorage::class
);
```

Module 使用：

``` php
public function upload(
StorageInterface $storage
)
{

}
```

不关心：

-   本地
-   OSS
-   COS

------------------------------------------------------------------------

# 10. Contract 与 Module关系

正确：

    Shop Module

    依赖：

    PaymentInterface


    ↓

    Payment Plugin

错误：

    Shop Module

    依赖：

    Wechat SDK

------------------------------------------------------------------------

# 11. Contract 与 Event关系

两者职责不同。

Contract：

解决：

    怎么做

Event：

解决：

    发生了什么

例如：

支付：

Contract：

    PaymentInterface.pay()

成功后：

Event：

    PaymentSuccessEvent

------------------------------------------------------------------------

# 12. Contract版本管理

接口一旦发布：

需要稳定。

例如：

v1:

    PaymentInterface

升级：

    PaymentInterfaceV2

避免：

老 Plugin 全部失效。

------------------------------------------------------------------------

# 13. 推荐核心 Contract

第一阶段：

    StorageInterface

    PaymentInterface

    NotificationInterface

    UserProviderInterface

    ConfigInterface

    PermissionInterface

第二阶段：

    TenantInterface

    SearchInterface

    WorkflowInterface

    AIInterface

------------------------------------------------------------------------

# 14. 最终架构

                    Core


                Contracts


                    |


    --------------------------------


    Module                 Plugin


    Shop                   WechatPay


    CRM                    OSS


    CMS                    SMS

最终原则：

    Core定义规则

    Contract定义能力

    Module实现业务

    Plugin提供替换

    Event负责通知

    Hook负责扩展
