# Starship Core 详细设计方案

## 1. Core 定位

packages/core 是 Starship 的基础运行内核。

职责：

-   Module 加载
-   Plugin 加载
-   Theme 加载
-   Event 系统
-   Contract 接口
-   公共基础服务

原则：

Core 不包含任何业务。

禁止：

-   商品
-   订单
-   CRM
-   CMS
-   行业逻辑

------------------------------------------------------------------------

# 2. Core 目录设计

    packages/core/

    src/

    ├── Module
    │   ├── ModuleManager.php
    │   ├── ModuleScanner.php
    │   ├── ModuleLoader.php
    │   └── ModuleValidator.php
    │
    ├── Plugin
    │   ├── PluginManager.php
    │   ├── PluginInstaller.php
    │   ├── PluginLoader.php
    │   └── PluginValidator.php
    │
    ├── Theme
    │   ├── ThemeManager.php
    │   ├── ThemeScanner.php
    │   └── ThemeLoader.php
    │
    ├── Event
    │
    ├── Contracts
    │
    ├── Support
    │
    └── Providers

------------------------------------------------------------------------

# 3. ModuleManager设计

## 职责

管理 modules 目录。

负责：

-   扫描模块
-   读取 module.json
-   检查依赖
-   加载 Provider
-   注册路由
-   注册迁移

------------------------------------------------------------------------

## 生命周期

    discover

    ↓

    validate

    ↓

    load

    ↓

    boot

    ↓

    running

------------------------------------------------------------------------

## 核心接口

``` php
interface ModuleManagerInterface
{

public function discover();

public function all();

public function load(string $name);

public function enable(string $name);

public function disable(string $name);

}
```

------------------------------------------------------------------------

# 4. PluginManager设计

## 职责

管理可安装扩展。

负责：

-   安装
-   卸载
-   启用
-   禁用
-   配置管理

------------------------------------------------------------------------

## 生命周期

    discover

    ↓

    install

    ↓

    enable

    ↓

    disable

    ↓

    upgrade

    ↓

    uninstall

------------------------------------------------------------------------

## 核心接口

``` php
interface PluginManagerInterface
{

public function install(string $slug);

public function uninstall(string $slug);

public function enable(string $slug);

public function disable(string $slug);

}
```

------------------------------------------------------------------------

# 5. ThemeManager设计

## 职责

管理前台主题。

负责：

-   主题发现
-   安装
-   激活
-   切换

------------------------------------------------------------------------

接口：

``` php
interface ThemeManagerInterface
{

public function discover();

public function install();

public function activate();

public function current();

}
```

------------------------------------------------------------------------

# 6. EventBus设计

目的：

降低模块耦合。

错误：

    订单服务

    调用

    短信服务

正确：

    OrderCreatedEvent

            |

     SMS Plugin

    积分模块

    统计模块

------------------------------------------------------------------------

事件类型：

-   Module事件
-   Plugin事件
-   Theme事件
-   Business事件

------------------------------------------------------------------------

# 7. Contract体系

Core定义接口。

例如：

    Contracts/

    UserRepositoryInterface

    StorageInterface

    PaymentInterface

    NotificationInterface

业务模块依赖接口。

不要依赖具体实现。

------------------------------------------------------------------------

# 8. Provider加载机制

启动：

    Laravel Boot

    ↓

    CoreServiceProvider

    ↓

    ModuleManager

    ↓

    PluginManager

    ↓

    ThemeManager

    ↓

    系统启动完成

------------------------------------------------------------------------

# 9. 数据存储

Core管理：

## plugins

    sa_plugins

记录：

-   slug
-   version
-   status

## plugin_configs

    sa_plugin_configs

存储插件配置。

Theme：

    sa_themes

------------------------------------------------------------------------

# 10. 开发原则

Core永远保持稳定。

所有业务：

进入 Module。

所有第三方：

进入 Plugin。

所有展示：

进入 Theme。
