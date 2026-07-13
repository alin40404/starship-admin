# Starship Admin 开发清单

> 更新日期：2026-07-13  
> 目录约定：**保留 `modules/`**，近期不改为 `starship/`。

---

## 1. 架构共识（已确认）

| 项 | 决策 |
|----|------|
| 内核 | `packages/core` → Composer `starship/core`，业务项目只读 |
| 底座模块 | `modules/System`、`modules/Generator`，源码在仓库内，不走 Composer 包 |
| 业务模块 | 新建目录放 `modules/{Name}/`，通过 `module.json` 注册 |
| 插件 | `plugins/`，完整生命周期（发现 / 安装 / 启用 / 禁用 / 卸载） |
| 主题 | `themes/` 占位，阶段二实现 ThemeManager |
| Monitor | 已并入 System，API `/admin-api/monitor/*`，权限 `monitor.*` |
| 暂不做 | 多租户 Module、Module/Plugin 市场、`modules/` 改名 |

---

## 2. 当前实现状态

| 能力 | 状态 | 说明 |
|------|------|------|
| ModuleManager | ✅ | 扫描 `module.json`、注册 Provider |
| Module 依赖校验 | ✅ | `require.modules` 启动时校验 |
| Plugin 发现 / 安装 / 启用 / 禁用 | ✅ | CLI + System 管理端 |
| Plugin 卸载 CLI | ✅ | `starship:plugin:uninstall` |
| `starship:module:make` | ✅ | 模块脚手架 |
| `starship:plugin:make` | ❌ | 插件脚手架 |
| Generator 端到端 Demo | ⚠️ | 表 → CRUD → 菜单 → 权限，待验收 |
| Contract 体系 | ❌ | 如 `StorageInterface` |
| Event / Hook | ❌ | 阶段三 |
| ThemeManager | ❌ | 阶段二 |
| 脚手架 `starship/admin` | ❌ | `composer create-project` 模板 |
| 框架 Feature Test | ❌ | 启动、模块、插件冒烟 |

图例：✅ 已完成 · 🚧 进行中 · ⚠️ 部分可用 · ❌ 未开始

---

## 3. 阶段划分

### 阶段一：底座夯实（当前，约 3～6 个月）

**目标**：Core + System + Generator + Plugin 生命周期闭环，新业务不进 `backend/app/`。

#### P0 — 插件与模块基础

- [x] Monitor 合并进 System
- [x] `starship:plugin:uninstall` CLI
- [ ] SmsAliyun 插件完整闭环（发现 → 安装 → 启用 → 配置 → 禁用 → 卸载）
- [x] Module `require.modules` 启动依赖校验
- [x] `starship:module:make {Name}` 脚手架
- [ ] Generator 一张表端到端 Demo 验收

#### P1 — 文档与规范对齐

- [ ] `starship:plugin:make` 脚手架
- [ ] 同步 `docs/deployment/Starship_Module_开发规范.md` 与真实目录（Providers 在 `app/Providers/`）
- [ ] API / 权限码文档与 `module.json` 保持一致
- [ ] System 插件管理页对接 uninstall

#### P2 — 框架产品化前置

- [ ] 最小 Contract（如 `StorageInterface`、`SmsInterface`）
- [ ] 框架级 Feature Test（`php artisan test` 冒烟）
- [ ] 项目模板 `starship/admin`

### 阶段二：主题体系（底座稳定后，约 1～2 个月）

- [ ] ThemeManager + `theme.json` 解析
- [ ] 主题发现 / 启用 / 切换
- [ ] CMS / 商城前台主题 Demo
- [ ] `themes/` 目录规范落地

### 阶段三：生态扩展（长期）

- [ ] EventBus + Hook 体系
- [ ] Contract 完整目录（`packages/core/Contract/`）
- [ ] 官方插件库（支付、存储、短信）
- [ ] 前端插件动态加载
- [ ] 插件市场（可选）

### 明确不做（近期）

- [ ] `modules/` → `starship/` 目录改名
- [ ] Module Composer 包发布 / Module 市场
- [ ] 多租户 Tenant Module
- [ ] Plugin 承载大型业务模块

---

## 4. Sprint 任务明细（阶段一 P0）

### 4.1 Plugin 卸载 CLI

```
php artisan starship:plugin:uninstall {plugin}
```

- 调用 `PluginLifecycle::uninstall`
- 前置：插件须已禁用
- 输出：成功 / 失败原因

### 4.2 Module 依赖校验

`module.json` 示例：

```json
{
  "require": {
    "modules": ["system"]
  }
}
```

- 启动时校验所有 **enabled** 模块的 `require.modules`
- 缺失依赖抛 `InvalidArgumentException`，阻止静默启动

### 4.3 模块脚手架

```
php artisan starship:module:make Shop
```

生成：

```
modules/Shop/
├── composer.json
├── module.json
├── app/Providers/ShopServiceProvider.php
├── routes/admin.php
├── database/migrations/.gitkeep
└── resources/lang/zh-CN/messages.php
```

命名空间：`Modules\Shop\App\...`  
Composer 包名：`starship/shop`（需在 `backend/composer.json` 添加 path 仓库）

### 4.4 Generator 端到端 Demo

验收路径：

1. 建表 `sa_demo_products`
2. `starship:generator:import` → 配置字段
3. `starship:generator:crud` → 生成后端 + 前端
4. 菜单 / 权限自动注册
5. 管理端 CRUD 可操作

---

## 5. 目录速查

```
starship-admin/
├── packages/core/          # 内核（ModuleManager、PluginEngine、CLI）
├── backend/                # Laravel 应用壳
├── modules/
│   ├── System/             # 系统 + 监控日志
│   └── Generator/          # 代码生成
├── plugins/
│   └── SmsAliyun/          # 短信插件示例
├── themes/                 # 占位
└── apps/admin/             # 管理端前端
```

---

## 6. 常用命令

```bash
# 模块
php artisan starship:module:make Erp

# 插件
php artisan starship:plugin:discover
php artisan starship:plugin:install sms-aliyun
php artisan starship:plugin:enable sms-aliyun
php artisan starship:plugin:disable sms-aliyun
php artisan starship:plugin:uninstall sms-aliyun

# 代码生成
php artisan starship:generator:tables
php artisan starship:generator:import {table}
php artisan starship:generator:crud {table}
```

---

## 7. 参考文档

- [framework.md](./framework.md) — 总体架构与演进路线
- [Starship_Module_Plugin_Theme_实现方案.md](./Starship_Module_Plugin_Theme_实现方案.md)
- [starship-framework-latest.md](./starship-framework-latest.md)
- [Starship_Module_开发规范.md](../deployment/Starship_Module_开发规范.md)
- [Starship_Plugin_开发规范.md](../deployment/Starship_Plugin_开发规范.md)
