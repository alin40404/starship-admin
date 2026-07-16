# Starship Contract 接口体系设计方案

> **实现状态（2026-07-16）**
>
> | Contract | Core 接口 | System 默认 | 插件覆盖样例 |
> |----------|-----------|-------------|--------------|
> | `SmsSenderInterface` | ✅ | `SmsGateway` | `plugins/SmsAliyun` |
> | `StorageInterface` | ✅（含 `exists()`） | `LocalStorage` | `plugins/StorageOss` |
> | `MailSenderInterface` | ✅ | `MailGateway` | `plugins/MailSmtp` |
> | `PaymentInterface` | ✅ 目录已入库 | ❌ 延后 | ❌ 延后 |
> | `NotificationInterface` | ✅ 目录已入库 | ❌ 延后 | ❌ 延后 |
> | `AdminProviderInterface` | ✅ | `AdminProvider` | — |
> | `MemberProviderInterface` | ✅ | Member `MemberProvider` | — |
> | `ConfigRepositoryInterface` | ✅ 目录已入库 | ❌ 延后 | — |
> | `PermissionCheckerInterface` | ✅ 目录已入库 | ❌ 延后 | — |
>
> **目录**：`packages/core/src/Contracts/{Sms,Mail,Storage,Admin,Member,Payment,Notification,Config,Permission}/`  
> **已落地绑定**：Sms / Storage / Mail / Admin / Member；Storage 插件覆盖样例 `StorageOss`。Payment / Notification / Config / Permission 仅接口占位。  
> **第二阶段目录**（尚未建）：Tenant / Search / Workflow / AI / OAuth。  
> **暂缓**：Packagist / `release` 分支。

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

落点：

```text
packages/core/src/Contracts/
├── Sms/             # ✅ 已绑定
├── Mail/            # ✅ 已绑定
├── Storage/         # ✅ 已绑定
├── Admin/           # ✅ AdminProvider 已绑定
├── Member/          # ✅ MemberProvider 已绑定
├── Payment/         # ✅ 接口；实现延后
├── Notification/    # ✅ 接口；实现延后
├── Config/          # ✅ 接口；实现延后
├── Permission/      # ✅ 接口；实现延后
├── Tenant/          # 第二阶段
├── Search/          # 第二阶段
└── Workflow/        # 第二阶段
```

Core 定义规则；Module / System 提供默认实现；Plugin 覆盖可替换能力。

------------------------------------------------------------------------

# 4. Contract 分类

## 4.1 基础能力 Contract

例如：

    AdminProviderInterface / MemberProviderInterface

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

# 8. 身份查询 Contract（Admin / Member）

仓库内没有统一的 `User` 模型，身份分两类，**禁止**用一个模糊 `UserProvider` 混用：

| 接口 | 身份 | 默认实现 |
|------|------|----------|
| `AdminProviderInterface` | 后台管理员 `Admin` | System `AdminProvider`（`request()->user()` / Sanctum） |
| `MemberProviderInterface` | 前台会员 `Member` | Member `MemberProvider`（`Auth::guard('member')`） |

不要：

```text
CRM → use Modules\System\App\Models\Admin
Shop → use Modules\Member\App\Models\Member
```

应该：

```text
后台业务 → AdminProviderInterface::find() / current()
前台业务 → MemberProviderInterface::find() / current()
```

接口形状（两套相同）：

```php
interface AdminProviderInterface
{
    public function find(string|int $id): ?object;

    public function current(): ?object;
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

第一阶段（目录已齐）：

    StorageInterface / SmsSenderInterface / MailSenderInterface   ← 已绑定
    PaymentInterface / NotificationInterface                     ← 仅接口
    AdminProviderInterface / MemberProviderInterface             ← 已绑定
    ConfigRepositoryInterface / PermissionCheckerInterface       ← 仅接口

第二阶段（目录未建）：

    TenantInterface / SearchInterface / WorkflowInterface
    AIInterface / OAuth
    Payment / Notification 的 System 默认与官方插件落地
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
