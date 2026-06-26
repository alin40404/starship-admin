# Starship Admin

Starship Admin 是一个基于 **Laravel + Vue 3** 的企业级后台开发框架，目标是提供一套 **开箱即用、可扩展、易二开、适合长期维护** 的中后台开发底座。

它参考 FastAdmin、BuildAdmin 等后台框架的设计思路，结合 Laravel 现代后端生态与 Vue 3 前端工程化能力，为企业管理系统、运营后台、CMS 系统、SaaS 平台管理端、商家工作台、电商后台、O2O 系统、门店管理系统等独立业务系统提供统一的后台基础能力、权限体系、模块化机制和 CRUD 代码生成能力。

---

## 1. 项目定位

Starship Admin 不是一个“大而全”的业务系统，也不是一开始就内置商城、CMS、O2O、SaaS、会员、支付、订单等完整业务系统。

它的核心定位是：

> 提供一套稳定、统一、可复用的后台开发基础框架，让开发者可以基于它快速构建不同类型的企业级业务系统。

第一阶段不建议急着开发复杂业务系统，而是先把后台框架最核心的底座能力做好。

第一阶段重点建设三个核心模块：

- `System`：系统基础模块
- `Monitor`：日志与监控模块
- `Generator`：CRUD 代码生成器模块

这三个底座模块稳定之后，再基于 Starship Admin 孵化独立业务系统或行业解决方案，例如：

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

### 5.2 Monitor 模块

`Monitor` 是日志与监控模块，负责系统运行过程中的审计、追踪和问题排查。

建议包含：

- 操作日志
- 登录日志
- 接口访问日志
- 异常日志
- 队列失败日志
- 定时任务日志
- 系统运行状态，可选
- 在线用户，可选
- 请求耗时统计，可选

### 5.3 Generator 模块

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

---

## 6. 后续独立业务系统方向

第一阶段完成后，不建议把 CMS、商城、O2O、SaaS 等继续简单描述为“模块”。

更准确的定义是：

> `System`、`Monitor`、`Generator` 是 Starship Admin 的底座模块；CMS、商城、O2O、SaaS、会员、支付、订单等应该是基于底座延伸出来的独立业务系统或业务中心。

建议演进顺序：

| 阶段 | 名称 | 准确定义 | 说明 |
|---|---|---|---|
| 第一阶段 | System | 底座模块 | 系统基础能力，包括管理员、角色、菜单、权限、配置、字典、文件上传 |
| 第一阶段 | Monitor | 底座模块 | 日志、审计、异常、接口访问、运行监控 |
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

## 7. 推荐仓库结构

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
│  ├─ System/                         # 系统基础模块
│  ├─ Monitor/                        # 日志与监控模块
│  └─ Generator/                      # CRUD 代码生成器模块
│
├─ packages/                          # 代码级共享能力
│  ├─ contracts/                      # 扩展契约、接口定义
│  └─ shared/                         # 共享常量、枚举、协议
│
├─ docs/                              # 项目文档
│  ├─ architecture/                   # 架构设计
│  ├─ modules/                        # 模块开发规范
│  ├─ generator/                      # 代码生成器设计
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

## 8. 目录命名建议

### 8.1 `backend`

保留 `backend`。

原因：

- 对 Laravel 后端宿主应用表达清晰
- 适合中文团队理解
- 与 `apps/admin` 前端目录形成清晰边界

不建议改成 `server`，除非项目后续整体转向更强的 Node monorepo 风格。

### 8.2 `apps`

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

### 8.3 `modules`

保留 `modules`，但要收窄它的含义。

`modules` 只用于放 Starship Admin 底座内部的功能模块，例如：

```text
modules/System
modules/Monitor
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

- `System`、`Monitor`、`Generator` 属于底座能力模块，不是普通插件
- CMS、商城、O2O、SaaS 更适合定义为独立业务系统或解决方案
- `plugins` 更适合后期第三方插件市场或外部服务适配器

### 8.4 `plugins`

第一版不建议增加 `plugins`。

后期如果要支持第三方插件，可以再新增：

```text
plugins/
├─ SmsAliyun/
├─ StorageQiniu/
└─ PaymentWechat/
```

建议规则：

- `modules`：底座功能模块
- `solutions`：独立业务系统或行业解决方案，可选
- `plugins`：第三方插件、外部服务适配器

### 8.5 `solutions`

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

### 8.6 `packages`

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

### 8.7 `themes`

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

## 9. 模块目录规范

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

## 10. `module.json` 规范

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

## 11. 模块命名规范

### 11.1 模块目录

使用大驼峰：

```text
modules/System
modules/Monitor
modules/Generator
```

### 11.2 模块标识

使用小写：

```text
system
monitor
generator
```

### 11.3 PHP 命名空间

使用大驼峰：

```php
Modules\System
Modules\Monitor
Modules\Generator
```

### 11.4 前端路由路径

使用小写短横线：

```text
/system/user
/system/role
/system/menu
/monitor/operation-log
/generator/table
```

### 11.5 权限节点

使用点分结构：

```text
system.user.index
system.user.create
system.user.update
system.user.delete
system.user.export
```

---

## 12. 后端架构分层

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

### 12.1 Controller

负责：

- 接收请求
- 调用 Request 验证
- 调用 Service
- 返回统一响应

不建议在 Controller 写复杂业务逻辑。

### 12.2 Request

负责：

- 参数验证
- 参数规则
- 字段提示
- 场景区分

### 12.3 Service

负责：

- 业务流程编排
- 权限相关业务判断
- 数据处理
- 事务控制

### 12.4 Model

负责：

- 数据模型定义
- 关联关系
- 访问器
- 修改器
- 查询作用域

### 12.5 Repository，可选

如果业务较复杂，可以增加 Repository。

如果只是简单 CRUD，可以先不强制使用 Repository，避免过度设计。

---

## 13. API 规范

### 13.1 路由前缀

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

### 13.2 统一响应结构

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

### 13.3 常用状态码

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

## 14. 权限设计

Starship Admin 建议采用 RBAC 权限模型。

### 14.1 核心对象

- 管理员
- 角色
- 菜单
- 权限节点
- 管理员角色关系
- 角色菜单关系
- 角色权限关系

### 14.2 权限层级

建议分为三类：

| 类型 | 说明 | 示例 |
|---|---|---|
| 目录 | 菜单分组 | 系统管理 |
| 菜单 | 页面入口 | 管理员管理 |
| 按钮 | 操作权限 | 新增、编辑、删除、导出 |

### 14.3 权限节点示例

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

### 14.4 前端权限控制

前端根据后端返回的权限节点控制：

- 页面路由
- 菜单显示
- 按钮显示
- 操作入口显示

### 14.5 后端权限控制

后端必须基于权限节点进行二次校验。

不能只依赖前端按钮隐藏。

---

## 15. 数据库规范

### 15.1 表前缀

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

### 15.2 表命名规则

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

### 15.3 通用字段

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

### 15.4 字段命名规则

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

## 16. System 模块建议表

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
```

可选：

```text
sa_system_dept               # 部门表
sa_system_post               # 岗位表
```

---

## 17. Monitor 模块建议表

第一版可优先设计这些表：

```text
sa_monitor_operation_log     # 操作日志表
sa_monitor_login_log         # 登录日志表
sa_monitor_api_log           # 接口日志表
sa_monitor_exception_log     # 异常日志表
sa_monitor_queue_failed      # 队列失败日志表，可选
sa_monitor_schedule_log      # 定时任务日志表，可选
```

---

## 18. Generator 模块建议表

第一版可优先设计这些表：

```text
sa_generator_table           # 生成表配置
sa_generator_column          # 生成字段配置
sa_generator_template        # 生成模板配置，可选
sa_generator_record          # 生成记录，可选
```

---

## 19. CRUD 代码生成器设计

### 19.1 输入来源

代码生成器建议支持：

- 选择已有数据库表
- 读取表字段
- 读取字段类型
- 读取字段注释
- 读取索引信息
- 手动配置字段展示方式
- 手动配置搜索、表单、列表、验证规则

### 19.2 字段配置

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

### 19.3 生成内容

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

### 19.4 生成原则

- 生成器只生成基础 CRUD
- 复杂业务逻辑由开发者在 Service 中扩展
- 生成器不能覆盖开发者手写代码，除非明确确认
- 生成记录需要可追踪
- 菜单和权限节点应支持自动创建
- 生成后的代码应符合项目目录规范

---

## 20. 前端架构建议

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

## 21. 登录认证建议

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

## 22. 文件上传建议

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

## 23. 日志审计建议

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

## 24. 快速启动规划

第一版 README 可以先预留快速启动命令。

### 24.1 后端启动

```bash
cd backend

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate --seed

php artisan serve
```

### 24.2 前端启动

```bash
cd apps/admin

pnpm install

pnpm dev
```

### 24.3 常用地址

```text
后台前端：http://localhost:5173
后端 API：http://localhost:8000/admin-api
```

---

## 25. 开发流程建议

建议开发流程：

```text
1. 先完成 backend Laravel 宿主应用
2. 完成模块加载机制
3. 完成 System 模块
4. 完成后台登录、菜单、权限
5. 完成 Monitor 模块
6. 完成 Generator 模块
7. 用 Generator 生成一个 Demo CRUD 验证闭环
8. 补充模块开发规范
9. 补充部署文档
10. 再开始建设独立业务系统或解决方案
```

---

## 26. 第一阶段验收标准

第一阶段完成后，至少需要达到以下标准：

### 26.1 System 模块

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

### 26.2 Monitor 模块

- 可以查看登录日志
- 可以查看操作日志
- 可以查看接口日志
- 可以查看异常日志
- 关键操作可以被审计
- 敏感字段可以脱敏

### 26.3 Generator 模块

- 可以读取数据库表
- 可以读取字段信息
- 可以配置字段展示方式
- 可以生成后端 CRUD 代码
- 可以生成前端页面
- 可以生成菜单
- 可以生成权限节点
- 可以生成后直接访问后台页面

---

## 27. 文档规划

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
│  ├─ system.md                       # System 模块设计
│  ├─ monitor.md                      # Monitor 模块设计
│  └─ generator.md                    # Generator 模块设计
│
├─ solutions/
│  ├─ overview.md                     # 独立业务系统规划
│  ├─ cms.md                          # CMS 系统设计，可选
│  ├─ shop.md                         # 商城系统设计，可选
│  └─ saas.md                         # SaaS 系统设计，可选
│
├─ database/
│  ├─ naming.md                       # 数据库命名规范
│  ├─ system-tables.md                # System 模块表设计
│  ├─ monitor-tables.md               # Monitor 模块表设计
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

## 28. 路线图

### 第一阶段：后台底座

目标：完成可运行、可登录、可授权、可生成 CRUD 的后台框架。

重点：

- Laravel 宿主应用
- Vue 后台前端
- 模块加载机制
- System 模块
- Monitor 模块
- Generator 模块

### 第二阶段：通用业务系统

目标：基于 Starship Admin 底座孵化相对通用的独立业务系统或业务中心。

可选系统：

- CMS 内容管理系统
- Member 会员中心系统
- Payment 支付中心系统
- Notify 通知中心系统
- Storage 文件资源中心系统
- ImportExport 导入导出中心系统

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

## 29. 不建议第一版做的事情

第一版不建议做：

- 不建议一开始内置完整商城
- 不建议一开始内置完整 CMS
- 不建议一开始做复杂 SaaS 多租户
- 不建议一开始做插件市场
- 不建议一开始做多前端应用
- 不建议一开始加入 `apps/web`
- 不建议一开始加入根目录 `themes`
- 不建议把生成器做得过度复杂
- 不建议在 Controller 写复杂业务逻辑
- 不建议只做前端按钮隐藏而不做后端权限校验

---

## 30. 推荐最终第一版结构

第一版最终建议聚焦为：

```text
starship-admin/
├─ backend/
├─ apps/
│  └─ admin/
├─ modules/
│  ├─ System/
│  ├─ Monitor/
│  └─ Generator/
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

后续等 `System`、`Monitor`、`Generator` 三个底座模块稳定之后，再根据实际业务建设独立系统。

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

如果确实需要第三方插件，再增加：

```text
plugins/SmsAliyun
plugins/StorageQiniu
plugins/PaymentWechat
```

---

## 31. 总结

Starship Admin 的第一阶段应该聚焦于后台框架底座，而不是业务大而全。

最合理的建设路线是：

```text
System 模块
  ↓
Monitor 模块
  ↓
Generator 模块
  ↓
Demo CRUD 验证
  ↓
独立业务系统建设
```

只要 `System`、`Monitor`、`Generator` 三个底座模块稳定，Starship Admin 就可以成为后续 CMS、商城、O2O、SaaS、会员、支付、订单等独立业务系统的统一开发底座。
