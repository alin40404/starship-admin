# Starship Admin

Starship Admin 是一个基于 **Laravel + Vue 3** 的企业级后台开发框架，致力于提供 **开箱即用、可扩展、易二开** 的中后台解决方案。

它参考 FastAdmin、BuildAdmin 等后台框架的设计思路，结合 Laravel 现代后端生态与 Vue 3 前端工程化能力，打造一套适用于企业管理系统、运营后台、SaaS 平台、商家工作台、电商后台、O2O 管理系统等场景的通用开发底座。

---

## 项目定位

Starship Admin 的目标不是直接内置商城、CMS、O2O、SaaS 全套业务，而是提供一套稳定、统一、可复用的后台开发基础框架，让开发者可以基于它快速构建各种业务系统。

它聚焦于：

- 后台基础能力开箱即用
- 强约定、低心智负担的开发体验
- 前后端分离的现代工程架构
- 模块化扩展能力
- 通用 CRUD 代码生成能力
- 面向企业级项目的可维护性与可扩展性

---

## 技术栈

### 后端

- Laravel
- PHP 8.3+
- MySQL 8.0+
- Redis

### 前端

- Vue 3
- TypeScript
- Vite
- Vben Admin 5.x（基于 Vben Admin 5.x 改造的后台前端）

---

## 核心目标

- 提供完整的后台基础能力
- 支持 RBAC 权限管理
- 支持动态菜单与按钮权限
- 支持通用 CRUD 代码生成
- 支持模块化扩展
- 支持日志审计与系统配置
- 支持企业级二次开发
- 为 CMS、官网后台、SaaS 管理端、商家工作台、电商后台、O2O 后台等系统提供统一开发底座

---

## 核心特性

### 1. 开箱即用的后台基础能力

内置后台框架最常用的系统模块，包括：

- 管理员管理
- 角色管理
- 菜单管理
- 权限控制
- 登录与认证
- 数据字典
- 系统配置
- 文件上传
- 操作日志
- 登录日志

### 2. 现代化前后端分离架构

- 后端基于 Laravel
- 前端基于 Vue 3 + TypeScript + Vite
- 后台前端基于 Vben Admin 5.x 改造
- 前后端职责清晰，便于独立开发与部署

### 3. 模块化扩展机制

Starship Admin 采用模块化架构设计，框架核心能力与业务能力解耦。  
开发者可以基于统一规范扩展自己的业务模块，而无需破坏底层框架结构。

### 4. 通用 CRUD 代码生成器

提供面向后台开发场景的 CRUD 生成能力，帮助开发者快速生成：

- 数据模型
- 请求验证
- 控制器
- 服务层
- 权限节点
- 菜单
- 前端列表页
- 前端表单页

### 5. 面向企业项目的工程能力

- 统一响应结构
- 统一异常处理
- 统一权限规范
- 可维护的目录结构
- 可扩展的模块机制
- 可持续演进的代码组织方式

---

## 设计理念

Starship Admin 不是一个“大而全”的业务系统，而是一套适合作为长期项目底座的开发框架。

它遵循以下原则：

- **框架只做底座，不预装复杂业务**
- **能力可复用，业务可扩展**
- **约定优于配置**
- **优先考虑长期维护性**
- **优先考虑二次开发友好性**
- **优先考虑企业项目落地能力**

---

## 适用场景

Starship Admin 本身是后台开发框架，不直接预置完整业务，但可以作为这些系统的开发基础：

- 企业管理系统
- 运营管理后台
- CMS 内容管理后台
- 官网后台
- SaaS 平台管理端
- 商家工作台
- 电商管理后台
- O2O 管理系统
- 门店管理后台
- 平台运营后台

---

## 规划模块

第一阶段聚焦框架底座能力，不直接塞入重业务模块。

### 内置核心模块

- 管理员管理
- 角色管理
- 菜单管理
- 权限控制
- 登录与认证
- 操作日志
- 登录日志
- 文件上传
- 数据字典
- 系统配置
- 代码生成器
- 插件 / 模块机制

### 第一版建议内置模块

- `System`：系统基础模块
- `Monitor`：日志与监控模块
- `Generator`：CRUD 代码生成器模块

### 后续可扩展方向

后续可通过官方扩展模块或开发者自定义模块，扩展出：

- CMS 模块
- 商城模块
- 商家模块
- O2O 模块
- SaaS 模块
- 会员模块
- 支付模块
- 订单模块

---

## 仓库结构

```text
starship-admin/
├─ apps/
│  ├─ server/                         # Laravel 宿主应用
│  │  ├─ app/                         # 应用核心代码
│  │  ├─ bootstrap/                   # Laravel 启动引导
│  │  ├─ config/                      # 全局配置
│  │  ├─ database/                    # 数据库迁移与填充
│  │  ├─ public/                      # Web 根目录与静态资源
│  │  ├─ resources/                   # Blade、语言包、前端资源
│  │  ├─ routes/                      # 路由入口
│  │  ├─ storage/                     # 运行时存储
│  │  ├─ tests/                       # 测试
│  │  ├─ artisan                      # Laravel CLI
│  │  ├─ composer.json                # PHP 依赖配置
│  │  └─ .env.example                 # 环境变量模板
│  │
│  ├─ admin-web/                      # 基于 Vben Admin 5.x 改造的后台前端
│  │  ├─ src/                         # 前端源码
│  │  ├─ public/                      # 公共静态资源
│  │  ├─ scripts/                     # 构建/发布脚本
│  │  ├─ types/                       # TS 类型声明
│  │  ├─ package.json                 # Node 依赖配置
│  │  ├─ vite.config.ts               # Vite 配置
│  │  └─ .env.example                 # 前端环境变量模板
│  │
│  └─ front/                          # 可选前台前端
│     ├─ src/                         # 前台源码
│     ├─ public/                      # 公共静态资源
│     ├─ scripts/                     # 构建/发布脚本
│     ├─ types/                       # TS 类型声明
│     ├─ package.json                 # Node 依赖配置
│     ├─ vite.config.ts               # Vite 配置
│     └─ .env.example                 # 前台环境变量模板
│
├─ modules/                           # 系统功能模块目录
│  ├─ System/                         # 系统基础模块
│  │  ├─ module.json                  # 模块说明与 Provider 注册
│  │  ├─ app/                         # 后端业务代码（Models / Http / Providers）
│  │  ├─ routes/                     # 模块路由（如 admin-api）
│  │  ├─ database/migrations/        # 模块迁移（sa_* 表）
│  │  └─ config/、resources/、front/  # 配置、资源、模块专属前台（可选）
│  │
│  ├─ Monitor/                        # 日志/监控模块
│  │  ├─ module.json
│  │  └─ …
│  │
│  └─ Generator/                      # CRUD 代码生成器模块
│     ├─ module.json
│     └─ …
│
├─ themes/                            # Blade 主题预留目录
│  ├─ default/                        # 默认主题
│  └─ corporate/                      # 企业主题示例
│
├─ packages/                          # 共享能力目录
│  ├─ contracts/                      # 扩展契约
│  │  ├─ backend/                     # 后端契约接口
│  │  └─ frontend/                    # 前端契约定义
│  │
│  ├─ shared/                         # 共享常量/枚举/协议
│  │  ├─ constants/                   # 常量
│  │  ├─ enums/                       # 枚举
│  │  └─ schemas/                     # 协议/结构定义
│  │
│  └─ docs/                           # 架构设计文档
│     ├─ architecture/                # 架构设计
│     ├─ modules/                     # 模块规范
│     └─ generator/                   # 生成器设计
│
├─ README.md
├─ README.en.md
└─ LICENSE