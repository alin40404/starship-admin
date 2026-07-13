# Starship Admin

Starship Admin 是一个基于 **Laravel + Vue 3** 的企业级后台开发框架，目标是提供一套 **开箱即用、可扩展、易二开、适合长期维护** 的中后台开发底座。

它借鉴主流后台框架的设计思路，结合 Laravel 现代后端生态与 Vue 3 前端工程化能力，为企业管理系统、运营后台、CMS 系统、SaaS 平台管理端、商家工作台、电商后台、O2O 系统、门店管理系统等独立业务系统提供统一的后台基础能力、权限体系、模块化机制和 CRUD 代码生成能力。

---

## 1. 项目定位

Starship Admin 不是一个“大而全”的业务系统，也不是一开始就内置商城、CMS、O2O、SaaS、会员、支付、订单等完整业务系统。

它的核心定位是：

> 提供一套稳定、统一、可复用的后台开发基础框架，让开发者可以基于它快速构建不同类型的企业级业务系统。

第一阶段不建议急着开发复杂业务系统，而是先把后台框架最核心的底座能力做好。

第一阶段重点建设两个底座模块：

- `System`：系统基础模块（含监控中心：操作/登录/异常日志）
- `Generator`：CRUD 代码生成器模块

同时，第一阶段需要提前预留插件机制，但插件机制不作为第四个业务模块，而作为框架扩展能力建设。

这两个底座模块稳定之后，再基于 Starship Admin 孵化独立业务系统或行业解决方案，例如：

- CMS 内容管理系统
- 商城系统
- O2O 系统
- SaaS 多租户系统
- 商家工作台系统
- 会员中心系统
- 支付中心系统
- 订单中心系统

需要注意：这些不是简单的 `modules/Cms`、`modules/Shop` 级别的功能模块，而应该被定义为独立业务系统。它们可以复用 Starship Admin 的后台底座能力，也可以拥有自己的业务模块、前端应用、数据库设计和部署方式。

---

## 2. 核心目标

Starship Admin 的核心目标包括：

- 提供完整的后台基础能力
- 支持 RBAC 权限管理
- 支持动态菜单与按钮权限
- 支持前后端分离的现代工程架构
- 支持底座模块化扩展
- 提前规划插件扩展能力，支持第三方服务适配与能力增强
- 支持通用 CRUD 代码生成
- 支持统一 API 响应结构
- 支持统一异常处理
- 支持日志审计与系统配置
- 支持企业级项目二次开发
- 为 CMS 系统、官网后台、SaaS 管理端、商家工作台、电商后台、O2O 系统等独立业务系统提供统一开发底座

---

## 3. 设计理念

Starship Admin 遵循以下设计原则：

- **框架只做底座，不预装复杂业务**
- **能力可复用，业务可扩展**
- **约定优于配置**
- **模块边界清晰**
- **插件可扩展，但不侵入核心**
- **前后端职责清晰**
- **优先考虑长期维护性**
- **优先考虑二次开发友好性**
- **优先考虑企业项目落地能力**
- **先稳定底座模块，再孵化独立业务系统**

---

## 4. 技术栈

### 4.1 后端

- Laravel
- PHP 8.3+
- MySQL 8.0+
- Redis
- Laravel Queue
- Laravel Scheduler
- Laravel Storage

### 4.2 前端

- Vue 3
- TypeScript
- Vite
- Vben Admin 5.x
- Pinia
- Vue Router
- Axios

### 4.3 推荐开发环境

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- pnpm 9+
- MySQL 8.0+
- Redis 6+
- Nginx / OpenResty
- Git

---

## 5. 第一阶段建设范围

第一阶段只聚焦后台底座能力，不直接开发复杂业务系统。

### 5.1 System 模块

`System` 是系统基础模块，负责后台框架最核心的管理能力。

建议包含：

- 登录与认证
- 管理员管理
- 角色管理
- 菜单管理
- 权限节点管理
- RBAC 权限控制
- 动态菜单
- 按钮权限
- 部门管理，可选
- 岗位管理，可选
- 数据字典
- 系统配置
- 文件上传
- 附件管理
- 个人资料
- 修改密码
- 基础参数配置
- **监控中心**：操作日志、登录日志、异常日志（API 前缀 `/admin-api/monitor/*`，权限 `monitor.*`）

### 5.2 Generator 模块

`Generator` 是 CRUD 代码生成器模块，负责提升后台业务开发效率。

建议支持根据数据库表生成：

- Laravel Model
- Laravel Request 验证类
- Laravel Controller
- Laravel Service
- Laravel Resource，可选
- 后端路由
- 权限节点
- 菜单记录
- Vue 列表页
- Vue 表单页
- 前端 API 文件
- TypeScript 类型定义
- 字段配置
- 搜索项配置
- 表格列配置
- 表单项配置

### 5.3 插件能力预留

插件能力不建议作为第一阶段的重业务模块，但建议作为框架基础扩展机制提前规划。

第一阶段建议做到：

- 预留 `plugins/` 目录
- 定义 `plugin.json` 插件描述文件
- 支持扫描本地插件
- 支持插件注册表
- 支持启用、禁用插件
- 支持插件配置读取和保存
- 支持插件注册 ServiceProvider
- 支持插件注册后台 API 路由
- 支持插件同步菜单和权限节点
- 支持插件异常进入 System 监控日志

第一阶段不建议做：

- 插件市场
- 在线安装
- 远程升级
- 商业授权
- 插件评分
- 插件交易结算

---

## 6. 后续独立业务系统方向

第一阶段完成后，不建议把 CMS、商城、O2O、SaaS 等继续简单描述为“模块”。

更准确的定义是：

> `System`、`Generator` 是 Starship Admin 的底座模块（其中 System 含监控中心能力）；CMS、商城、O2O、SaaS、会员、支付、订单等应该是基于底座延伸出来的独立业务系统或业务中心。

建议演进顺序：

| 阶段 | 名称 | 准确定义 | 说明 |
|---|---|---|---|
| 第一阶段 | System | 底座模块 | 系统基础能力（含管理员、角色、菜单、权限、配置、字典、文件上传、操作/登录/异常日志） |
| 第一阶段 | Generator | 底座模块 | CRUD 代码生成器，生成后端代码、前端页面、菜单和权限节点 |
| 第二阶段 | Cms | 独立内容管理系统 | 可独立建设官网、文章、栏目、页面、内容发布能力 |
| 第二阶段 | Member | 独立会员中心系统 | 会员、等级、标签、积分、成长值、会员资产等能力 |
| 第二阶段 | Payment | 独立支付中心系统 | 支付渠道、支付单、退款单、回调、对账等能力 |
| 第三阶段 | Shop | 独立商城系统 | 商品、SKU、购物车、营销、交易、售后等完整商城能力 |
| 第三阶段 | Order | 独立订单中心系统 | 订单生命周期、支付状态、发货状态、售后状态、订单流转 |
| 第三阶段 | Merchant | 独立商家工作台系统 | 商家入驻、商家后台、店铺、结算、经营数据 |
| 第三阶段 | O2o | 独立 O2O 业务系统 | 门店、服务、预约、核销、位置、到店/到家业务 |
| 第三阶段 | Saas | 独立 SaaS 多租户系统 | 租户、套餐、订阅、隔离策略、租户控制台 |

这些独立业务系统可以有两种组织方式：

### 6.1 推荐方式：独立仓库

适合正式产品化：

```text
starship-admin/          # 后台框架底座
starship-cms/            # CMS 内容管理系统
starship-shop/           # 商城系统
starship-o2o/            # O2O 系统
starship-saas/           # SaaS 多租户系统
```

优点是边界清晰、版本独立、部署独立，不会让 Starship Admin 主仓库变得臃肿。

### 6.2 可选方式：解决方案目录

如果希望放在同一个大仓库里，也建议使用 `solutions/`，不要直接塞进 `modules/`。

```text
solutions/
├─ cms/
├─ shop/
├─ o2o/
└─ saas/
```

`solutions/` 表示完整解决方案或独立业务系统，`modules/` 表示底座内部的功能模块。

---


## 7. 插件能力提前规划

插件能力建议提前规划，但第一阶段不要把插件市场、在线安装、远程分发做得过重。

正确路线是：

```text
第一阶段：预留插件目录、插件描述文件、插件注册机制、插件配置能力、插件生命周期接口
第二阶段：实现插件管理后台、插件安装/启用/禁用/升级、权限与菜单同步
第三阶段：再考虑插件市场、远程仓库、在线购买、授权验证、自动更新
```

### 7.1 插件定位

插件不是底座模块，也不是完整业务系统。

Starship Admin 中三类扩展对象的边界建议这样定义：

| 类型 | 目录 | 定位 | 示例 |
|---|---|---|---|
| 底座模块 | `modules/` | 框架内置核心能力，和主框架一起演进 | `System`、`Generator` |
| 插件 | `plugins/` | 可选安装的增强能力或第三方服务适配器 | 阿里云短信、七牛存储、微信支付适配器 |
| 独立业务系统 | `solutions/` 或独立仓库 | 基于 Starship Admin 构建的完整业务系统 | CMS、商城、O2O、SaaS |

因此，插件主要解决这些问题：

- 对接第三方服务
- 扩展某个通用能力
- 增加可选功能
- 不侵入底座核心代码
- 可以启用、禁用、升级和卸载

不建议把完整 CMS、商城、O2O、SaaS 做成插件。它们应该是独立业务系统。

### 7.2 插件类型规划

建议插件按能力类型分类。

| 类型 | 说明 | 示例 |
|---|---|---|
| `adapter` | 第三方服务适配器 | 阿里云短信、腾讯云短信、七牛云存储、阿里云 OSS |
| `integration` | 外部平台集成 | 企业微信、钉钉、飞书、微信开放平台 |
| `feature` | 可选功能增强 | 导入导出、通知中心、审批流、报表中心 |
| `payment` | 支付通道适配 | 微信支付、支付宝支付、Stripe，可后期使用 |
| `storage` | 存储通道适配 | OSS、COS、S3、Qiniu |
| `theme` | 前台模板主题，可选 | CMS 主题、官网主题；后台 Vue 主题不建议用插件承载 |

第一阶段最适合优先验证的插件是：

```text
plugins/SmsAliyun
plugins/StorageQiniu
plugins/NotifyWebhook
```

这类插件边界清晰，不会把业务复杂度提前带进主框架。

### 7.3 插件目录建议

建议根目录提前预留 `plugins/`。

```text
plugins/
├─ SmsAliyun/
│  ├─ plugin.json                     # 插件描述文件
│  ├─ src/                            # 插件后端代码
│  │  ├─ Providers/                   # 插件服务提供者
│  │  ├─ Services/                    # 插件服务类
│  │  ├─ Http/Controllers/            # 插件控制器，可选
│  │  └─ Support/                     # 插件辅助能力
│  ├─ routes/                         # 插件路由，可选
│  │  └─ admin-api.php
│  ├─ database/
│  │  ├─ migrations/                  # 插件迁移，可选
│  │  └─ seeders/                     # 插件初始化数据，可选
│  ├─ config/                         # 插件默认配置
│  ├─ resources/
│  │  ├─ lang/                        # 语言包，可选
│  │  └─ views/                       # Blade 视图，可选
│  ├─ front/                          # 插件前端扩展，可选
│  │  ├─ api/
│  │  ├─ views/
│  │  ├─ components/
│  │  └─ types/
│  ├─ docs/                           # 插件说明文档
│  └─ tests/                          # 插件测试
│
└─ StorageQiniu/
   └─ ...
```

插件目录使用大驼峰，插件标识使用小写短横线或小写下划线。

示例：

```text
目录名：SmsAliyun
插件标识：sms-aliyun
命名空间：Plugins\SmsAliyun
配置键：plugin.sms_aliyun
权限前缀：plugin.sms_aliyun
```

### 7.4 `plugin.json` 规范

每个插件都应该有自己的 `plugin.json`。

示例：

```json
{
  "name": "SmsAliyun",
  "slug": "sms-aliyun",
  "type": "adapter",
  "namespace": "Plugins\\SmsAliyun",
  "version": "1.0.0",
  "description": "阿里云短信服务适配插件",
  "author": "Starship Admin",
  "homepage": "",
  "require": {
    "starship": ">=1.0.0",
    "php": ">=8.3",
    "modules": ["System"],
    "plugins": []
  },
  "providers": [
    "Plugins\\SmsAliyun\\Src\\Providers\\SmsAliyunServiceProvider"
  ],
  "routes": [
    "routes/admin-api.php"
  ],
  "migrations": true,
  "seeders": false,
  "config": "config/sms-aliyun.php",
  "front": {
    "enabled": true,
    "path": "front"
  },
  "permissions": [
    "plugin.sms_aliyun.index",
    "plugin.sms_aliyun.config",
    "plugin.sms_aliyun.test"
  ],
  "menus": [
    {
      "title": "阿里云短信",
      "path": "/plugin/sms-aliyun",
      "permission": "plugin.sms_aliyun.index"
    }
  ],
  "enabled": false,
  "order": 500
}
```

字段说明：

| 字段 | 说明 |
|---|---|
| `name` | 插件名称，使用大驼峰 |
| `slug` | 插件唯一标识，建议小写短横线 |
| `type` | 插件类型，如 `adapter`、`integration`、`feature` |
| `namespace` | 插件 PHP 命名空间 |
| `version` | 插件版本，建议遵循语义化版本 |
| `require` | 依赖要求，包括 Starship 版本、PHP 版本、模块依赖、插件依赖 |
| `providers` | 插件服务提供者 |
| `routes` | 插件路由文件 |
| `migrations` | 是否包含数据库迁移 |
| `config` | 插件默认配置文件 |
| `front` | 是否包含后台前端扩展 |
| `permissions` | 插件权限节点 |
| `menus` | 插件菜单声明 |
| `enabled` | 是否启用 |
| `order` | 插件加载顺序 |

### 7.5 插件生命周期

插件生命周期建议统一为：

```text
discovered      # 已发现
installed       # 已安装
enabled         # 已启用
disabled        # 已禁用
upgrading       # 升级中
uninstalled     # 已卸载
```

对应操作：

| 操作 | 说明 |
|---|---|
| 发现 | 扫描 `plugins/` 目录，读取 `plugin.json` |
| 安装 | 写入插件注册表，执行迁移，发布配置，写入菜单和权限 |
| 启用 | 注册服务、加载路由、开启配置、显示菜单 |
| 禁用 | 停止加载路由和菜单，但默认不删除数据 |
| 升级 | 检查版本，执行增量迁移，更新配置和权限 |
| 卸载 | 移除插件注册信息；是否删除数据必须二次确认 |

第一阶段建议只实现：

```text
发现、安装、启用、禁用、配置读取
```

不建议第一阶段实现：

```text
在线市场、远程下载、自动更新、授权购买、插件评分、商业结算
```

### 7.6 插件管理表设计

插件管理能力可以先归入 `System` 模块，不必第一阶段新增独立 `Plugin` 模块。

建议表：

```text
sa_system_plugin              # 插件注册表
sa_system_plugin_config       # 插件配置表
sa_system_plugin_migration    # 插件迁移记录表，可选
sa_system_plugin_log          # 插件安装/启用/禁用/升级日志，可选
```

`sa_system_plugin` 建议字段：

```sql
`id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`name` varchar(100) NOT NULL COMMENT '插件名称',
`slug` varchar(100) NOT NULL COMMENT '插件标识',
`type` varchar(50) DEFAULT NULL COMMENT '插件类型',
`version` varchar(50) NOT NULL DEFAULT '1.0.0' COMMENT '当前版本',
`description` varchar(500) DEFAULT NULL COMMENT '插件描述',
`author` varchar(100) DEFAULT NULL COMMENT '作者',
`namespace` varchar(255) DEFAULT NULL COMMENT '命名空间',
`path` varchar(255) DEFAULT NULL COMMENT '插件路径',
`status` tinyint NOT NULL DEFAULT 0 COMMENT '状态:0=未安装,1=已安装,2=已启用,3=已禁用',
`config` json DEFAULT NULL COMMENT '插件配置快照',
`installed_at` timestamp NULL DEFAULT NULL COMMENT '安装时间',
`enabled_at` timestamp NULL DEFAULT NULL COMMENT '启用时间',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uk_slug` (`slug`)
```

`sa_system_plugin_config` 建议字段：

```sql
`id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`plugin_slug` varchar(100) NOT NULL COMMENT '插件标识',
`config_key` varchar(100) NOT NULL COMMENT '配置键',
`config_value` text DEFAULT NULL COMMENT '配置值',
`value_type` varchar(30) NOT NULL DEFAULT 'string' COMMENT '值类型:string,int,bool,json,array',
`is_encrypted` tinyint NOT NULL DEFAULT 0 COMMENT '是否加密:0=否,1=是',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `uk_plugin_config` (`plugin_slug`,`config_key`)
```

敏感配置必须支持加密存储，例如：

```text
access_key
secret_key
app_secret
token
private_key
```

### 7.7 插件后端加载机制

插件加载建议由 `PluginManager` 统一处理。

核心职责：

- 扫描插件目录
- 读取 `plugin.json`
- 校验版本和依赖
- 注册 ServiceProvider
- 加载配置
- 加载路由
- 加载迁移
- 同步菜单和权限
- 提供插件启用状态查询

建议后端核心类（已迁入 `packages/core`）：

```text
packages/core/src/Plugin/PluginManager.php
packages/core/src/Plugin/PluginManifest.php
packages/core/src/Plugin/PluginLoader.php
packages/core/src/Plugin/PluginLifecycle.php
packages/core/src/Providers/CoreServiceProvider.php
```

`backend/app/` 仅保留 Laravel 薄壳（`AppServiceProvider`、`User`、空 `Controller` 基类）。

### 7.8 插件前端扩展机制

插件前端扩展不要一开始做得太复杂。

第一阶段建议支持三类扩展：

- 插件配置页面
- 插件菜单页面
- 插件前端 API 文件

建议插件前端文件先放在插件自身目录：

```text
plugins/SmsAliyun/front/
├─ api/
├─ views/
├─ components/
└─ types/
```

构建时可以通过脚本同步到：

```text
apps/admin/src/plugins/sms-aliyun/
```

或者通过 Vite alias 指向插件目录。

第一版推荐使用“同步生成”方式，更容易理解和调试：

```bash
php artisan starship:plugin:publish-front SmsAliyun
```

同步后结构：

```text
apps/admin/src/plugins/
└─ sms-aliyun/
   ├─ api/
   ├─ views/
   ├─ components/
   └─ types/
```

### 7.9 插件扩展点 Hook 规划

插件要避免直接修改核心代码，应该通过扩展点接入。

建议预留后端 Hook：

```text
system.booted                         # 系统启动完成
admin.login.before                    # 管理员登录前
admin.login.after                     # 管理员登录后
admin.logout.after                    # 管理员退出后
file.upload.before                    # 文件上传前
file.upload.after                     # 文件上传后
menu.build.after                      # 菜单构建后
permission.sync.after                 # 权限同步后
generator.table.loaded                # 生成器读取表后
generator.code.generated              # 生成器代码生成后
config.updated                        # 系统配置更新后
```

后期业务系统可以继续扩展：

```text
member.registered
payment.created
payment.paid
order.created
order.paid
order.refunded
merchant.approved
```

实现方式建议优先复用 Laravel Event，不要一开始自造复杂 Hook 框架。

可以在 `PluginManager` 里提供统一注册入口：

```php
PluginManager::listen('file.upload.after', SmsUploadListener::class);
```

底层可以映射为 Laravel Event / Listener。

### 7.10 插件权限与菜单

插件不能绕过 RBAC。

插件声明的菜单和权限应该在安装或启用时同步到 System 模块。

权限节点建议统一使用：

```text
plugin.{plugin_slug}.{action}
```

示例：

```text
plugin.sms_aliyun.index
plugin.sms_aliyun.config
plugin.sms_aliyun.test
plugin.storage_qiniu.index
plugin.storage_qiniu.config
```

插件菜单建议挂在后台：

```text
系统管理 / 插件管理
系统管理 / 插件配置
扩展管理 / 阿里云短信
扩展管理 / 七牛云存储
```

是否创建单独“扩展管理”菜单，可以根据插件数量决定。

### 7.11 插件配置规范

插件配置要同时支持：

- 默认配置文件
- 数据库配置覆盖
- 后台页面修改配置
- 敏感字段加密
- 配置缓存

读取优先级建议：

```text
数据库配置 > 环境变量 > 插件默认配置
```

示例：

```php
plugin_config('sms-aliyun.access_key');
plugin_config('sms-aliyun.sign_name');
```

敏感配置不应该明文返回给前端。

前端展示时可以返回：

```text
已配置
未配置
```

不要返回完整 `secret_key`。

### 7.12 插件命令规划

建议提前规划 Artisan 命令：

```bash
php artisan starship:plugin:discover
php artisan starship:plugin:list
php artisan starship:plugin:install SmsAliyun
php artisan starship:plugin:enable SmsAliyun
php artisan starship:plugin:disable SmsAliyun
php artisan starship:plugin:uninstall SmsAliyun
php artisan starship:plugin:migrate SmsAliyun
php artisan starship:plugin:publish-config SmsAliyun
php artisan starship:plugin:publish-front SmsAliyun
```

第一阶段最少实现：

```bash
php artisan starship:plugin:discover
php artisan starship:plugin:list
php artisan starship:plugin:enable SmsAliyun
php artisan starship:plugin:disable SmsAliyun
```

### 7.13 插件安全边界

插件能力必须有安全边界。

建议规则：

- 插件不能直接修改 `backend/app` 核心代码
- 插件不能直接修改 `modules/System`、`modules/Generator`
- 插件数据库迁移必须可追踪
- 插件启用前必须校验依赖和版本
- 插件路由必须走统一认证和权限中间件
- 插件配置敏感字段必须加密
- 插件卸载默认不删除业务数据
- 插件前端页面必须经过菜单和权限控制
- 插件异常必须记录到 System 监控日志

如果后期支持第三方插件上传安装，必须增加：

- 插件签名校验
- 插件包 Hash 校验
- 插件来源白名单
- 插件危险函数扫描
- 插件安装前备份提醒
- 插件权限声明审核

### 7.14 插件能力第一阶段验收标准

第一阶段插件能力不要求完整市场化，但至少要能证明架构可行。

建议验收标准：

- 可以扫描 `plugins/` 目录
- 可以读取 `plugin.json`
- 可以在后台看到插件列表
- 可以启用和禁用插件
- 插件可以注册 ServiceProvider
- 插件可以注册后台 API 路由
- 插件可以加载自己的配置
- 插件可以同步菜单和权限节点
- 插件配置可以在后台保存
- 插件异常可以进入 System 监控日志
- 插件不能绕过后台登录和权限校验

第一阶段可以用 `SmsAliyun` 或 `StorageQiniu` 做验证插件。


## 8. 推荐仓库结构

第一版建议保持克制，只保留后台框架底座真正需要的目录。

```text
starship-admin/
├─ backend/                           # Laravel 后端宿主应用
│  ├─ app/                            # Laravel 应用核心代码
│  ├─ bootstrap/                      # Laravel 启动引导
│  ├─ config/                         # 全局配置
│  ├─ database/                       # 宿主应用迁移与填充
│  ├─ public/                         # Web 根目录
│  ├─ resources/                      # 后端资源、语言包、Blade 预留
│  ├─ routes/                         # 宿主应用路由入口
│  ├─ storage/                        # 运行时存储
│  ├─ tests/                          # 后端测试
│  ├─ artisan                         # Laravel CLI
│  ├─ composer.json                   # PHP 依赖配置
│  └─ .env.example                    # 后端环境变量模板
│
├─ apps/                              # 前端应用集合
│  └─ admin/                          # 后台管理端，基于 Vben Admin 5.x
│     ├─ src/                         # 前端源码
│     ├─ public/                      # 公共静态资源
│     ├─ scripts/                     # 前端构建/发布脚本
│     ├─ types/                       # TypeScript 类型声明
│     ├─ package.json                 # Node 依赖配置
│     ├─ vite.config.ts               # Vite 配置
│     └─ .env.example                 # 前端环境变量模板
│
├─ modules/                           # Starship Admin 底座模块
│  ├─ System/                         # 系统基础模块（含监控中心）
│  └─ Generator/                      # CRUD 代码生成器模块
│
├─ plugins/                           # 插件预留目录，放可选插件和第三方服务适配器
│  └─ .gitkeep                        # 第一阶段可先保留空目录
│
├─ packages/                          # 框架内核与共享包
│  └─ core/                           # Layer 1 内核 starship/core（Module/Plugin 引擎）
│
├─ docs/                              # 项目文档
│  ├─ architecture/                   # 架构设计（见 docs/architecture/framework.md）
│  ├─ modules/                        # 模块开发规范
│  ├─ generator/                      # 代码生成器设计
│  ├─ plugins/                        # 插件机制设计
│  ├─ database/                       # 数据库规范
│  ├─ api/                            # API 规范
│  └─ deployment/                     # 部署文档
│
├─ scripts/                           # 根级脚本，如安装、构建、发布
├─ .editorconfig                      # 编辑器规范
├─ .gitignore                         # Git 忽略规则
├─ README.md                          # 中文说明
├─ README.en.md                       # 英文说明
└─ LICENSE                            # 开源协议
```

---

## 9. 目录命名建议

### 9.1 `backend`

保留 `backend`。

原因：

- 对 Laravel 后端宿主应用表达清晰
- 适合中文团队理解
- 与 `apps/admin` 前端目录形成清晰边界

不建议改成 `server`，除非项目后续整体转向更强的 Node monorepo 风格。

### 9.2 `apps`

`apps` 只放可以独立运行、独立构建、独立部署的前端应用。

第一版建议只保留：

```text
apps/admin/
```

暂时不建议放：

```text
apps/web/
```

原因是 `web` 命名过于泛化，后期容易混淆。

如果后续确实需要前台应用，建议按场景命名：

```text
apps/website/     # 官网、文档站、展示站
apps/portal/      # 用户中心、客户工作台
apps/merchant/    # 商家端
apps/mobile/      # 移动端 H5
```

### 9.3 `modules`

保留 `modules`，但要收窄它的含义。

`modules` 只用于放 Starship Admin 底座内部的功能模块，例如：

```text
modules/System
modules/Generator
```

不建议把 CMS、商城、O2O、SaaS 这类完整业务系统直接放进 `modules/`。

也不建议把 `modules` 改成：

```text
plugins
addons
extensions
```

原因：

- `System`、`Generator` 属于底座能力模块，不是普通插件
- CMS、商城、O2O、SaaS 更适合定义为独立业务系统或解决方案
- `plugins` 更适合后期第三方插件市场或外部服务适配器

### 9.4 `plugins`

建议提前规划 `plugins`，但第一阶段只做轻量预留和本地插件能力，不做复杂插件市场。

推荐预留：

```text
plugins/
├─ SmsAliyun/
├─ StorageQiniu/
└─ NotifyWebhook/
```

建议规则：

- `modules`：底座功能模块，例如 `System`、`Generator`
- `plugins`：可选插件、第三方服务适配器、能力增强包
- `solutions`：独立业务系统或行业解决方案

插件适合承载短信、存储、支付通道、通知通道、外部平台集成等可插拔能力，不适合承载完整 CMS、商城、O2O、SaaS。

### 9.5 `solutions`

第一版不建议增加 `solutions`。

后期如果要把 CMS、商城、O2O、SaaS 等完整业务系统放在同一个仓库里，建议使用：

```text
solutions/
├─ cms/
├─ shop/
├─ o2o/
└─ saas/
```

`solutions` 的定位是“完整业务系统 / 行业解决方案”，不是单一功能模块。

更推荐正式产品化时拆成独立仓库，例如：

```text
starship-cms
starship-shop
starship-o2o
starship-saas
```

### 9.6 `packages`

`packages` 只放代码级共享能力，不能变成杂物目录。

建议包含：

```text
packages/
├─ contracts/      # 后端接口契约、前端协议契约
└─ shared/         # 共享常量、枚举、结构协议
```

后期可以扩展：

```text
packages/core/     # 框架核心能力
packages/ui/       # 前端共享组件
```

### 9.7 `themes`

第一版不建议放 `themes`。

因为第一阶段聚焦后台框架底座，不直接做 CMS 或官网模板系统。

如果后期做 CMS、官网、Blade 模板主题，则建议放根目录：

```text
themes/
├─ default/
└─ corporate/
```

如果只是后台管理端的主题色、暗黑模式、布局样式，不应该放根目录 `themes`，而应该放在：

```text
apps/admin/src/design/
```

或者：

```text
apps/admin/src/theme/
```

推荐使用：

```text
apps/admin/src/design/
```

原因是它可以同时承载主题变量、全局样式、设计令牌、布局风格等内容。

---

## 10. 模块目录规范

每个模块建议采用统一结构。

以 `System` 模块为例：

```text
modules/System/
├─ module.json                         # 模块描述文件
├─ app/                                # 后端业务代码
│  ├─ Models/                          # 模型
│  ├─ Http/
│  │  ├─ Controllers/                  # 控制器
│  │  ├─ Requests/                     # 请求验证
│  │  └─ Resources/                    # API 资源，可选
│  ├─ Services/                        # 服务层
│  ├─ Repositories/                    # 仓储层，可选
│  ├─ Providers/                       # 服务提供者
│  └─ Support/                         # 模块辅助能力
│
├─ routes/                             # 模块路由
│  ├─ admin-api.php                    # 后台 API 路由
│  └─ web.php                          # Web 路由，可选
│
├─ database/
│  ├─ migrations/                      # 模块数据库迁移
│  └─ seeders/                         # 模块初始化数据
│
├─ config/                             # 模块配置
├─ resources/
│  ├─ lang/                            # 语言包
│  └─ views/                           # Blade 视图，可选
│
├─ front/                              # 模块前端页面，可选
│  ├─ api/                             # 前端接口文件
│  ├─ views/                           # 前端页面
│  └─ types/                           # 前端类型定义
│
└─ tests/                              # 模块测试
```

---

## 11. `module.json` 规范

每个模块都应该有自己的 `module.json`。

示例：

```json
{
  "name": "System",
  "slug": "system",
  "namespace": "Modules\\System",
  "version": "1.0.0",
  "description": "系统基础模块",
  "providers": [
    "Modules\\System\\App\\Providers\\SystemServiceProvider"
  ],
  "enabled": true,
  "order": 100
}
```

字段说明：

| 字段 | 说明 |
|---|---|
| `name` | 模块名称，使用大驼峰 |
| `slug` | 模块标识，使用小写 |
| `namespace` | PHP 命名空间 |
| `version` | 模块版本 |
| `description` | 模块说明 |
| `providers` | 模块服务提供者 |
| `enabled` | 是否启用 |
| `order` | 模块加载顺序 |

---

## 12. 模块命名规范

### 12.1 模块目录

使用大驼峰：

```text
modules/System
modules/Generator
```

### 12.2 模块标识

使用小写：

```text
system
generator
```

### 12.3 PHP 命名空间

使用大驼峰：

```php
Modules\System
Modules\Generator
```

### 12.4 前端路由路径

使用小写短横线：

```text
/system/user
/system/role
/system/menu
/monitor/operation-logs
/generator/table
```

> 监控中心页面路由仍使用 `/monitor/*`；对应权限码为 `monitor.*`（定义于 `modules/System/app/Support/*LogPermissions.php`）。

### 12.5 权限节点

使用点分结构：

```text
system.user.index
system.user.create
system.user.update
system.user.delete
system.user.export
```

---

## 13. 后端架构分层

建议每个模块内部遵循统一分层：

```text
Controller
  ↓
Request
  ↓
Service
  ↓
Model / Repository
  ↓
Database
```

### 13.1 Controller

负责：

- 接收请求
- 调用 Request 验证
- 调用 Service
- 返回统一响应

不建议在 Controller 写复杂业务逻辑。

### 13.2 Request

负责：

- 参数验证
- 参数规则
- 字段提示
- 场景区分

### 13.3 Service

负责：

- 业务流程编排
- 权限相关业务判断
- 数据处理
- 事务控制

### 13.4 Model

负责：

- 数据模型定义
- 关联关系
- 访问器
- 修改器
- 查询作用域

### 13.5 Repository，可选

如果业务较复杂，可以增加 Repository。

如果只是简单 CRUD，可以先不强制使用 Repository，避免过度设计。

---

## 14. API 规范

### 14.1 路由前缀

后台 API 建议统一使用：

```text
/admin-api
```

示例：

```text
POST   /admin-api/system/auth/login
GET    /admin-api/system/user
POST   /admin-api/system/user
PUT    /admin-api/system/user/{id}
DELETE /admin-api/system/user/{id}
```

### 14.2 统一响应结构

成功响应：

```json
{
  "code": 0,
  "message": "success",
  "data": {}
}
```

分页响应：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "items": [],
    "total": 0,
    "page": 1,
    "page_size": 20
  }
}
```

失败响应：

```json
{
  "code": 400,
  "message": "请求参数错误",
  "data": null
}
```

### 14.3 常用状态码

| code | 说明 |
|---:|---|
| 0 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未登录或登录过期 |
| 403 | 无权限访问 |
| 404 | 资源不存在 |
| 422 | 表单验证失败 |
| 500 | 服务器错误 |

---

## 15. 权限设计

Starship Admin 建议采用 RBAC 权限模型。

### 15.1 核心对象

- 管理员
- 角色
- 菜单
- 权限节点
- 管理员角色关系
- 角色菜单关系
- 角色权限关系

### 15.2 权限层级

建议分为三类：

| 类型 | 说明 | 示例 |
|---|---|---|
| 目录 | 菜单分组 | 系统管理 |
| 菜单 | 页面入口 | 管理员管理 |
| 按钮 | 操作权限 | 新增、编辑、删除、导出 |

### 15.3 权限节点示例

```text
system.admin.index
system.admin.create
system.admin.update
system.admin.delete
system.admin.export

system.role.index
system.role.create
system.role.update
system.role.delete
system.role.permission

system.menu.index
system.menu.create
system.menu.update
system.menu.delete
```

### 15.4 前端权限控制

前端根据后端返回的权限节点控制：

- 页面路由
- 菜单显示
- 按钮显示
- 操作入口显示

### 15.5 后端权限控制

后端必须基于权限节点进行二次校验。

不能只依赖前端按钮隐藏。

---

## 16. 数据库规范

### 16.1 表前缀

建议统一使用：

```text
sa_
```

示例：

```text
sa_system_admin
sa_system_role
sa_system_menu
sa_system_permission
sa_monitor_operation_log
sa_monitor_login_log
sa_generator_table
sa_generator_column
```

### 16.2 表命名规则

建议格式：

```text
sa_{module}_{business}
```

示例：

```text
sa_system_admin
sa_system_role
sa_monitor_operation_log
sa_generator_table
```

### 16.3 通用字段

业务表建议统一包含：

```sql
`id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`created_by` bigint unsigned DEFAULT NULL COMMENT '创建人ID',
`updated_by` bigint unsigned DEFAULT NULL COMMENT '更新人ID',
`created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
`updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
`deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`)
```

如需要排序、状态、备注，可以增加：

```sql
`status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0=禁用,1=启用',
`sort` int NOT NULL DEFAULT 0 COMMENT '排序',
`remark` varchar(500) DEFAULT NULL COMMENT '备注'
```

### 16.4 字段命名规则

- 使用小写字母
- 使用下划线分隔
- 不使用拼音
- 不使用数据库关键字
- 布尔状态优先使用 `is_` 前缀
- 时间字段统一使用 `_at` 后缀

示例：

```text
user_id
role_id
menu_id
login_at
created_at
is_enabled
```

---

## 17. System 模块建议表

第一版可优先设计这些表：

```text
sa_system_admin              # 管理员表
sa_system_role               # 角色表
sa_system_menu               # 菜单表
sa_system_permission         # 权限节点表
sa_system_admin_role         # 管理员角色关联表
sa_system_role_menu          # 角色菜单关联表
sa_system_role_permission    # 角色权限关联表
sa_system_dict_type          # 字典类型表
sa_system_dict_data          # 字典数据表
sa_system_config             # 系统配置表
sa_system_attachment         # 附件表
sa_system_plugin             # 插件注册表
sa_system_plugin_config      # 插件配置表
```

可选：

```text
sa_system_dept               # 部门表
sa_system_post               # 岗位表
```

---

## 18. System 模块 — 监控相关表

监控能力已并入 `System` 模块，表名仍使用 `monitor_*` 前缀。第一版已实现：

```text
monitor_operation_logs       # 操作日志表
monitor_login_logs           # 登录日志表
monitor_exception_logs       # 异常日志表
```

后续可选扩展：

```text
monitor_api_logs             # 接口日志表
monitor_queue_failed         # 队列失败日志表
monitor_schedule_logs        # 定时任务日志表
```

---

## 19. Generator 模块建议表

第一版可优先设计这些表：

```text
sa_generator_table           # 生成表配置
sa_generator_column          # 生成字段配置
sa_generator_template        # 生成模板配置，可选
sa_generator_record          # 生成记录，可选
```

---

## 20. CRUD 代码生成器设计

### 20.1 输入来源

代码生成器建议支持：

- 选择已有数据库表
- 读取表字段
- 读取字段类型
- 读取字段注释
- 读取索引信息
- 手动配置字段展示方式
- 手动配置搜索、表单、列表、验证规则

### 20.2 字段配置

每个字段建议支持配置：

| 配置项 | 说明 |
|---|---|
| 字段名 | 数据库字段名 |
| 字段注释 | 用作表格标题、表单标题 |
| 字段类型 | 数据库字段类型 |
| TS 类型 | 前端类型 |
| 是否列表显示 | 是否显示在列表 |
| 是否搜索 | 是否作为搜索条件 |
| 是否表单 | 是否显示在表单 |
| 表单组件 | Input、Select、DatePicker 等 |
| 验证规则 | 必填、长度、格式等 |
| 字典绑定 | 是否绑定数据字典 |
| 排序 | 显示排序 |

### 20.3 生成内容

后端生成：

```text
Model
Controller
Request
Service
Route
Migration，可选
Seeder，可选
Permission
Menu
```

前端生成：

```text
api.ts
types.ts
index.vue
form.vue
columns.ts
schema.ts
route.ts，可选
```

### 20.4 生成原则

- 生成器只生成基础 CRUD
- 复杂业务逻辑由开发者在 Service 中扩展
- 生成器不能覆盖开发者手写代码，除非明确确认
- 生成记录需要可追踪
- 菜单和权限节点应支持自动创建
- 生成后的代码应符合项目目录规范

---

## 21. 前端架构建议

后台前端基于 Vben Admin 5.x 改造，建议目录结构：

```text
apps/admin/src/
├─ api/                               # 全局 API
├─ assets/                            # 静态资源
├─ components/                        # 公共组件
├─ design/                            # 主题、样式、设计令牌
├─ layouts/                           # 布局
├─ router/                            # 路由
├─ stores/                            # 状态管理
├─ utils/                             # 工具函数
├─ views/                             # 页面
└─ modules/                           # 模块前端页面，可选
```

如果采用模块自包含方式，也可以由后端模块的 `front/` 目录生成前端文件，再同步或软链接到 `apps/admin/src/modules/`。

推荐第一版保持简单：

```text
apps/admin/src/views/system/
apps/admin/src/views/monitor/
apps/admin/src/views/generator/
```

后期再考虑模块前端自包含。

---

## 22. 登录认证建议

后台登录认证建议选择一种方案并固定。

推荐优先考虑：

```text
Laravel Sanctum
```

原因：

- Laravel 官方生态
- 适合 SPA 前后端分离后台
- 使用成本较低
- 易于和 Laravel 中间件集成

后台登录建议支持：

- 账号密码登录
- 验证码，可选
- 登录失败次数限制
- Token 登录态
- 退出登录
- 获取当前管理员信息
- 获取当前管理员菜单
- 获取当前管理员权限节点

---

## 23. 文件上传建议

文件上传建议统一由 `System` 模块管理。

支持存储驱动：

- local
- public
- s3
- oss，可后期扩展
- cos，可后期扩展
- qiniu，可后期扩展

附件表建议记录：

- 文件原名
- 文件路径
- 文件 URL
- 文件大小
- MIME 类型
- 存储驱动
- 上传人
- 上传时间

---

## 24. 日志审计建议

后台系统必须具备基础审计能力。

建议记录：

- 操作人
- 操作模块
- 操作动作
- 请求方法
- 请求地址
- 请求参数
- IP 地址
- User-Agent
- 响应状态
- 请求耗时
- 操作时间

敏感字段需要脱敏，例如：

```text
password
password_confirmation
token
secret
authorization
```

---

## 25. 快速启动规划

第一版 README 可以先预留快速启动命令。

### 25.1 后端启动

```bash
cd backend

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate --seed

# 创建 public/storage 软链接（附件 public 磁盘必需，详见 docs/deployment/public-storage-link.md）
php artisan storage:link

php artisan serve
```

### 25.2 public/storage 软链接

使用 **public 磁盘** 上传附件时，需将 `backend/public/storage` 链接到 `backend/storage/app/public`，否则上传后的图片/文件 URL 会 404。

**推荐命令（在 `backend` 目录下）：**

```bash
php artisan storage:link
```

本地前后端分离开发时，Vite 会把 `/storage` 代理到 `http://127.0.0.1:8000`，因此软链接只需在后端创建一次。

完整说明（含 Windows 手动创建、生产部署、排错）见：[docs/deployment/public-storage-link.md](docs/deployment/public-storage-link.md)

### 25.3 前端启动

```bash
cd apps/admin

pnpm install

pnpm dev
```

### 25.4 常用地址

```text
后台前端：http://localhost:5173
后端 API：http://localhost:8000/admin-api
```

---

## 26. 开发流程建议

建议开发流程：

```text
1. 先完成 backend Laravel 宿主应用
2. 完成模块加载机制
3. 完成 System 模块（含登录、菜单、权限、监控日志）
4. 完成 Generator 模块
5. 完成插件发现、注册、启用、禁用的最小闭环
6. 用 Generator 生成一个 Demo CRUD 验证闭环
7. 补充模块开发规范
8. 补充部署文档
9. 再开始建设独立业务系统或解决方案
```

---

## 27. 第一阶段验收标准

第一阶段完成后，至少需要达到以下标准：

### 27.1 System 模块

- 可以登录后台
- 可以管理管理员
- 可以管理角色
- 可以管理菜单
- 可以分配角色权限
- 可以动态生成后台菜单
- 可以控制按钮权限
- 可以维护数据字典
- 可以维护系统配置
- 可以上传文件
- 可以查看登录/操作/异常日志（监控中心）

### 27.2 Generator 模块

- 可以读取数据库表
- 可以读取字段信息
- 可以配置字段展示方式
- 可以生成后端 CRUD 代码
- 可以生成前端页面
- 可以生成菜单
- 可以生成权限节点
- 可以生成后直接访问后台页面

### 27.3 插件能力预留

- 可以扫描 `plugins/` 目录
- 可以读取 `plugin.json`
- 可以在后台查看插件列表
- 可以启用和禁用本地插件
- 插件可以注册 ServiceProvider
- 插件可以注册后台 API 路由
- 插件可以加载配置
- 插件可以同步菜单和权限节点
- 插件配置可以保存到数据库
- 插件异常可以记录到 System 监控日志

---

## 28. 文档规划

建议在 `docs/` 下补充以下文档：

```text
docs/
├─ architecture/
│  ├─ overview.md                     # 总体架构
│  ├─ backend.md                      # 后端架构
│  ├─ frontend.md                     # 前端架构
│  └─ module-loader.md                # 模块加载机制
│
├─ modules/
│  ├─ module-spec.md                  # 模块开发规范
│  ├─ system.md                       # System 模块设计（含监控中心）
│  └─ generator.md                    # Generator 模块设计
│
├─ plugins/
│  ├─ overview.md                     # 插件体系总览
│  ├─ plugin-json.md                  # plugin.json 规范
│  ├─ lifecycle.md                    # 插件生命周期
│  ├─ hooks.md                        # 插件扩展点
│  └─ security.md                     # 插件安全边界
│
├─ solutions/
│  ├─ overview.md                     # 独立业务系统规划
│  ├─ cms.md                          # CMS 系统设计，可选
│  ├─ shop.md                         # 商城系统设计，可选
│  └─ saas.md                         # SaaS 系统设计，可选
│
├─ database/
│  ├─ naming.md                       # 数据库命名规范
│  ├─ system-tables.md                # System 模块表设计（含 monitor_*）
│  └─ generator-tables.md             # Generator 模块表设计
│
├─ api/
│  ├─ response.md                     # 响应结构规范
│  ├─ auth.md                         # 登录认证接口
│  └─ permission.md                   # 权限接口
│
├─ generator/
│  ├─ design.md                       # 生成器设计
│  ├─ templates.md                    # 生成模板规范
│  └─ field-mapping.md                # 字段映射规则
│
└─ deployment/
   ├─ local.md                        # 本地开发部署
   ├─ production.md                   # 生产部署
   └─ nginx.md                        # Nginx 配置
```

---

## 29. 路线图

### 第一阶段：后台底座

目标：完成可运行、可登录、可授权、可生成 CRUD 的后台框架。

重点：

- Laravel 宿主应用
- Vue 后台前端
- 模块加载机制
- System 模块（含监控中心）
- Generator 模块
- 插件机制最小闭环：发现、注册、启用、禁用、配置

### 第二阶段：通用业务系统

目标：基于 Starship Admin 底座孵化相对通用的独立业务系统或业务中心。

可选系统：

- CMS 内容管理系统
- Member 会员中心系统
- Payment 支付中心系统
- Notify 通知中心系统
- Storage 文件资源中心系统
- ImportExport 导入导出中心系统
- Plugin 插件管理能力增强

### 第三阶段：行业业务系统

目标：基于框架沉淀行业解决方案，而不是继续把行业能力塞进主框架。

可选系统：

- Shop 商城系统
- Order 订单中心系统
- Merchant 商家工作台系统
- O2O 业务系统
- SaaS 多租户系统
- Store 门店系统
- Marketing 营销系统

---

## 30. 不建议第一版做的事情

第一版不建议做：

- 不建议一开始内置完整商城
- 不建议一开始内置完整 CMS
- 不建议一开始做复杂 SaaS 多租户
- 不建议一开始做完整插件市场、在线分发和商业授权，但建议预留本地插件机制
- 不建议一开始做多前端应用
- 不建议一开始加入 `apps/web`
- 不建议一开始加入根目录 `themes`
- 不建议把生成器做得过度复杂
- 不建议在 Controller 写复杂业务逻辑
- 不建议只做前端按钮隐藏而不做后端权限校验

---

## 31. 推荐最终第一版结构

第一版最终建议聚焦为：

```text
starship-admin/
├─ backend/
├─ apps/
│  └─ admin/
├─ modules/
│  ├─ System/
│  └─ Generator/
├─ plugins/
│  └─ .gitkeep
├─ packages/
│  ├─ contracts/
│  └─ shared/
├─ docs/
├─ scripts/
├─ README.md
├─ README.en.md
└─ LICENSE
```

这个结构更适合作为 Starship Admin 的第一版基础框架。

后续等 `System`、`Generator` 两个底座模块稳定之后，再根据实际业务建设独立系统。

如果正式产品化，建议拆成独立仓库：

```text
starship-cms
starship-shop
starship-member
starship-payment
starship-order
starship-o2o
starship-saas
```

如果需要放在同一个大仓库里，建议使用 `solutions/`，不要直接放进 `modules/`：

```text
solutions/cms
solutions/shop
solutions/member
solutions/payment
solutions/order
solutions/o2o
solutions/saas
```

如果确实需要官网或用户端，再增加：

```text
apps/website
apps/portal
apps/merchant
```

如果确实需要 Blade / CMS 主题，再增加：

```text
themes/default
themes/corporate
```

插件目录建议提前预留，第一阶段可以先使用示例插件验证机制：

```text
plugins/SmsAliyun
plugins/StorageQiniu
plugins/NotifyWebhook
```

---

## 32. 总结

Starship Admin 的第一阶段应该聚焦于后台框架底座，而不是业务大而全。

最合理的建设路线是：

```text
System 模块（含监控中心）
  ↓
Generator 模块
  ↓
插件机制最小闭环
  ↓
Demo CRUD 验证
  ↓
独立业务系统建设
```

只要 `System`、`Generator` 两个底座模块稳定，并且插件机制完成最小闭环，Starship Admin 就可以成为后续 CMS、商城、O2O、SaaS、会员、支付、订单等独立业务系统的统一开发底座。
