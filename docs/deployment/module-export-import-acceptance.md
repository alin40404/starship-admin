# 模块导出 / 导入验收清单（Member 试点）

> 目标：在 **不依赖 CLI / 不上市场** 的前提下，验证「A 系统开发 → 打包 → B 系统导入 → 可用」。  
> 通过本清单后，再实现 `starship:module:export` / `import`。  
> 日期：2026-07-14

---

## 0. 范围与前提

### 0.1 本轮验收对象

| 部分 | 路径 |
|------|------|
| 后端模块 | `modules/Member/` |
| 管理端前端 | `apps/admin/web-antd/src/features/member/` |

### 0.2 不在本轮

- 后台上传 zip UI  
- Composer / Packagist 发包  
- 前端物理迁入 `modules/Member/resources/admin`  
- 自动处理跨模块私有依赖之外的定制代码  

### 0.3 环境前提（导出方 A / 导入方 B 均需满足）

- [ ] B 已有可运行底座：`starship/core` + `modules/System` 已启用  
- [ ] B 管理端为同一代 `web-antd`（已含 `src/features` + `access.ts` 映射）  
- [ ] PHP / Composer / Node / pnpm 版本与项目文档一致  
- [ ] A 上 Member 功能本身已验收通过（CRUD、菜单、权限、前台登录可选）

---

## 1. 交付物约定（手工包结构）

手工导出目录（或 zip 解压后）必须长这样：

```text
member-0.1.0/
├── manifest.json
├── backend/                 # = modules/Member 全文
│   ├── module.json
│   ├── composer.json
│   ├── app/
│   ├── database/
│   ├── routes/
│   └── resources/
└── admin-ui/                # = web-antd/src/features/member 全文
    ├── api/
    ├── constants/
    └── views/
```

### 1.1 `manifest.json` 最低字段（本轮手写）

```json
{
  "name": "member",
  "title": "会员中心",
  "version": "0.1.0",
  "backend_target": "modules/Member",
  "admin_ui_target": "apps/admin/web-antd/src/features/member",
  "require": {
    "modules": ["system"]
  },
  "seeders": [
    "Modules\\Member\\Database\\Seeders\\MemberMenuSeeder"
  ],
  "menus": {
    "paths": ["/member", "/member/members"]
  },
  "permissions": {
    "prefixes": ["member."]
  },
  "menu_component": "/member/index"
}
```

### 1.2 导出完整性检查（A）

- [ ] `backend/` 含 `module.json`、迁移、`MemberMenuSeeder`、Admin API 路由、Provider  
- [ ] `admin-ui/` 含 `api/index.ts`、`constants/member-permissions.ts`、`views/member/{index.vue,data.ts,modules/form.vue}`  
- [ ] `backend/module.json` 与 `manifest.json` 的 `name` / `require` / `menus` / `permissions` / `seeders` 一致  
- [ ] 包内**无** `node_modules`、无 `.env`、无编译 `dist`  
- [ ] `admin-ui` 内 import 使用 `#/features/member/...` 或壳能力（`#/adapter`、`#/api/request`、`#/features/system/...`），无指向 A 项目私有路径  

---

## 2. 导入步骤（B，手工）

按顺序执行，任一步失败则整单不通过。

### 2.1 落盘

- [ ] 将 `backend/` 拷到 B：`modules/Member/`（目标目录原先不存在）  
- [ ] 将 `admin-ui/` 拷到 B：`apps/admin/web-antd/src/features/member/`  
- [ ] 将 `manifest.json` 存档到 B 项目旁或 `modules/Member/manifest.export.json`（便于对照，可选）

### 2.2 后端

在 B 的 Laravel 应用根（通常 `backend/`）执行：

- [ ] `composer dump-autoload`（若模块有独立 PSR-4 / path，按其约定）  
- [ ] 确认 Module 扫描到 `member`（启动无「缺少依赖模块」报错；`require.modules` 含 `system`）  
- [ ] 运行 Member 迁移（表 `members` / 带前缀时为 `{prefix}members`）  
- [ ] 执行 `Modules\Member\Database\Seeders\MemberMenuSeeder`  
- [ ] 给测试管理员角色勾选 `member.*` 权限（或超级管理员已放行）

### 2.3 前端

在 `apps/admin`：

- [ ] `pnpm install`（仅当依赖有变；Member 一般不增第三方包）  
- [ ] `pnpm -F @vben/web-antd run dev` 能启动  
- [ ] 若生产构建：`pnpm -F @vben/web-antd run build` 成功  

---

## 3. 功能验收（B，导入后）

### 3.1 后台

登录 B 管理端后：

- [ ] 侧栏出现「会员」相关菜单（path：`/member`、`/member/members`）  
- [ ] 打开会员列表页（component：`/member/index` → `features/member/views/member/index.vue`）  
- [ ] 列表接口成功（`/admin-api/.../members` 按路由实际前缀）  
- [ ] 新增会员成功  
- [ ] 编辑会员成功  
- [ ] 启用/停用状态成功  
- [ ] 删除会员成功  
- [ ] 无权限账号看不到按钮或接口 403（抽测 `member.members.create` 等）

### 3.2 前台（若 B 启用主题 / 会员 web 路由）

- [ ] `/member/login`、`/member/register` 可访问（Blade）  
- [ ] 注册 / 登录 / 退出闭环可用  
- [ ] （可选）账号页可访问  

> 若 B 暂不测前台，本小节标注「跳过」并写明原因，不阻塞后台验收通过。

---

## 4. 负面与回归（B）

- [ ] 删除或禁用 Member 后，侧栏不再出现其菜单（或按 ModuleManager 约定过滤）  
- [ ] 不导入 `admin-ui` 仅导入 backend 时：API 可通但前端打不开页（预期；用于证明前端块必要）——**抽测一次即可**  
- [ ] System 原有页面（管理员、菜单）不受影响  

---

## 5. 通过标准

**后台通过**（做 CLI 的入场条件）：

1. §1 交付物齐全  
2. §2 导入步骤可在另一目录/干净项目复现  
3. §3.1 全部勾选  
4. §4 回归无阻断问题  

**完整通过**（含前台）：后台通过 + §3.2 通过。

记录模板：

```text
验收人：
日期：
导出方仓库/分支：
导入方仓库/分支：
结果：后台通过 / 完整通过 / 失败
失败项：
备注：
```

---

## 6. CLI（已实现，验收时优先用）

文档：[module-cli.md](./module-cli.md)

| 手工步骤 | CLI |
|----------|-----|
| 拷贝 `modules/{Name}` + `features/{name}` | `php artisan starship:module:export {name}` |
| 写 `manifest.json` | export 自动生成并校验 |
| 解压到目标路径 | `php artisan starship:module:import {path} [--force]` |
| migrate + seeder | `import --migrate --seed`（可分开手工跑） |
| 提示重建前端 | 命令结束打印下一步 |

**验收建议**：用 `export member` 产出包 → 在干净环境 `import --force --migrate --seed` → 再跑 §3。

**仍非本阶段**：管理端上传安装页、远程市场、版本自动升级。

---

## 7. 相关路径速查（Member）

| 项 | 值 |
|----|-----|
| 权限前缀 | `member.` |
| 菜单 path | `/member`、`/member/members` |
| 菜单 component | `/member/index` |
| 前端入口 | `apps/admin/web-antd/src/features/member/views/member/index.vue` |
| Seeder | `Modules\Member\Database\Seeders\MemberMenuSeeder` |
| 依赖 | `system` |
