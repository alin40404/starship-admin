# Starship Event 与 Hook 事件扩展体系设计方案

> **实现状态（2026-07-15）**  
> - **Event 主干已落地**：`EventDispatcherInterface` / `EventDispatcher`，事件示例 `PluginDescriptionResolving`，样例插件 `plugins/EventSample`。  
> - **不再采用 WordPress 式 `add_action` / `apply_filters` 全局 Hook。**  
> - 文档中的独立 Filter Hook（改价格等）**延后**；现阶段改写载荷优先用可变 Event 属性。

## 1. 设计目标

Starship 目标：

-   Module 可复用
-   Plugin 可生态化
-   Theme 可扩展
-   应用可组合

为了避免模块之间强耦合，需要建立统一扩展机制。

核心原则：

    业务发生 → Event

    改变行为 → Hook

    展示变化 → Theme

    外部接入 → Plugin

    业务能力 → Module

------------------------------------------------------------------------

# 2. Event 与 Hook 区别

## Event（事件）

定义：

> 某个业务事实已经发生，通知所有关注者。

例如：

    OrderCreatedEvent

    UserRegisteredEvent

    PaymentSuccessEvent

特点：

-   一对多通知
-   发布者不知道监听者
-   用于业务解耦

示例：

    订单创建

    ↓

    OrderCreatedEvent

    ↓

    -----------------

    短信插件

    积分模块

    统计模块

    通知模块

------------------------------------------------------------------------

## Hook（钩子）

定义：

> 在某个执行节点允许外部修改或插入逻辑。

例如：

    before.order.create

    filter.order.price

    after.product.save

特点：

-   修改执行流程
-   修改数据结果
-   插入额外行为

示例：

价格计算：

    商品价格

    ↓

    价格Hook

    ↓

    会员折扣

    ↓

    优惠券

    ↓

    最终价格

------------------------------------------------------------------------

# 3. 为什么不是只使用 Hook？

WordPress 主要使用 Hook：

    Core

    ↓

    Plugin 修改行为

优点：

-   灵活
-   扩展方便

缺点：

-   大型系统容易混乱
-   不知道哪里被修改
-   调试困难

例如：

    1000个Hook

    ↓

    业务流程不可控

------------------------------------------------------------------------

# 4. 为什么 Starship 以 Event 为核心？

Starship 面向：

-   ERP
-   CRM
-   商城
-   SaaS
-   行业系统

这些系统主要是业务流程。

例如：

订单：

    创建订单

    ↓

    库存处理

    ↓

    支付

    ↓

    发货

    ↓

    通知

    ↓

    积分

这些都是业务事件。

所以：

Event 更符合企业系统。

------------------------------------------------------------------------

# 5. Starship 推荐架构

Core：

    packages/core/


    ├── Event

    │   ├── EventDispatcher

    │   ├── Listener

    │   └── Events


    └── Hook

        ├── HookManager

        ├── Filter

        └── Action

------------------------------------------------------------------------

# 6. Event 设计规范

## 6.1 命名规范

使用过去式。

正确：

    UserCreated

    OrderPaid

    ProductPublished

    InvoiceGenerated

错误：

    CreateUser

    PayOrder

原因：

Event 表示事实已经发生。

------------------------------------------------------------------------

# 6.2 Event 示例

用户注册：

``` php
UserCreatedEvent
```

监听：

    SMS Plugin

    Email Plugin

    CRM Module

    Points Module

------------------------------------------------------------------------

订单支付：

    PaymentSuccessEvent

监听：

    订单模块

    财务模块

    通知模块

------------------------------------------------------------------------

# 7. Hook 设计规范

## 7.1 Hook 分类

### Action Hook

执行额外动作。

例如：

    after.user.created

用途：

-   发通知
-   写日志
-   同步数据

------------------------------------------------------------------------

### Filter Hook

修改数据。

例如：

    filter.order.price

用途：

-   修改价格
-   修改内容
-   修改参数

------------------------------------------------------------------------

# 8. Event 与 Hook 使用场景

## 场景1：用户注册

不要：

    UserService

    调用

    SmsService

    EmailService

    PointsService

应该：

    UserCreatedEvent

    ↓

    监听者处理

------------------------------------------------------------------------

## 场景2：订单价格

不要 Event。

因为需要改变结果。

使用：

    filter.order.price

流程：

    原价格

    ↓

    价格Hook

    ↓

    会员折扣

    ↓

    优惠券

    ↓

    最终价格

------------------------------------------------------------------------

## 场景3：支付完成

使用 Event。

    PaymentSuccessEvent

    ↓

    订单处理

    ↓

    积分增加

    ↓

    消息通知

    ↓

    财务记录

------------------------------------------------------------------------

# 9. Module / Plugin 使用规范

## Module之间通信

禁止：

    Shop Module

    直接调用

    CRM Service

推荐：

    CustomerCreatedEvent

    ↓

    CRM监听

------------------------------------------------------------------------

## Plugin接入

例如短信插件：

监听：

    UserCreatedEvent

    OrderCreatedEvent

无需修改业务模块。

------------------------------------------------------------------------

# 10. Core接口设计

## EventDispatcher

``` php
interface EventDispatcherInterface
{

public function dispatch(object $event);

public function listen(
string $event,
callable $listener
);

}
```

------------------------------------------------------------------------

## HookManager

``` php
interface HookManagerInterface
{

public function action(
string $name,
callable $callback
);


public function filter(
string $name,
mixed $value
);

}
```

------------------------------------------------------------------------

# 11. Event 与 Plugin生命周期结合

例如安装短信插件：

    Plugin Install

    ↓

    注册Listener

    ↓

    监听业务Event

    ↓

    运行

卸载：

    删除Listener

    ↓

    插件停止

------------------------------------------------------------------------

# 12. 最终设计原则

Starship 不采用：

    全Hook模式

也不采用：

    全部硬编码调用

采用：

    Event 为主

    Hook 为辅

推荐比例：

    Event 80%

    Hook 20%

------------------------------------------------------------------------

# 13. Starship完整扩展模型

                     Core


            EventBus       Hook


                |           |


    Module      Plugin      Theme


    业务        外部        展示

最终原则：

    业务事实使用 Event

    行为修改使用 Hook

    第三方能力使用 Plugin

    业务领域使用 Module

    展示层使用 Theme
