# Packagist 发布约定（core + 模板）

> 决策：**前端**随应用仓（本仓 / `release`）交付，不走 npm 发包。  
> **`starship-admin/core`** 走 Packagist（Composer）。  
> **`modules/System`、`modules/Generator`**：按架构共识仍以源码进业务项目（`modules/`），**近期不单独上 Packagist**（与 checklist「底座模块不走 Composer 包」一致）。若日后改口径再拆包。

---

## 1. 包一览

| 制品 | 交付方式 | 包名 |
|------|----------|------|
| 内核 | **Packagist** | `starship-admin/core` |
| 应用模板 | 本仓 `release` + 日后 Packagist `starship/skeleton` | `starship/skeleton` |
| System / Generator | 模板仓内 `modules/` 源码 | （可选日后 `starship/system`） |
| 管理端前端 | 模板仓内 `apps/admin` | 非 Packagist |

---

## 2. 为何 core 要单独发包

Packagist **一仓一包**。本仓是 monorepo，不能把整个 `starship-admin` 注册成 `starship-admin/core`。

推荐（**GitHub 为 Packagist 源仓**；Gitee 可选镜像）：

```text
本仓 packages/core
        │ subtree split
        ├──────────────────►  github.com/.../starship-core  ──► Packagist: starship-admin/core
        └──────────────────►  gitee.com/.../starship-core   （可选国内镜像，不注册 Packagist）
```

日常仍在 monorepo 改 `packages/core`；发版时 split + 推 GitHub tag + Packagist 更新。

> Packagist 与 GitHub 集成最顺（App / webhook）。**只向 Packagist 提交一个 Git URL**（选 GitHub）；Gitee 仅作代码镜像即可。

---

## 3. 发布 `starship-admin/core` 步骤

### 3.1 一次性：GitHub 独立仓 + Packagist（+ 可选 Gitee）

1. GitHub（账号 `alin40404`）建空仓：`https://github.com/alin40404/starship-core`（Public，仅发布产物）  
2. （可选）Gitee（账号 `allanchan004`）建同名空仓作镜像  
3. 本仓添加 remote：

```bash
git remote add starship-core git@github.com:alin40404/starship-core.git
# 可选镜像：
git remote add starship-core-gitee git@gitee.com:allanchan004/starship-core.git
```

4. Packagist → [Submit](https://packagist.org/packages/submit) → 填 **GitHub** 仓 URL → 确认包名 `starship-admin/core`  
5. GitHub 仓库 → Settings / Integrations → 启用 Packagist 自动更新（或 Packagist 侧 GitHub App）

### 3.2 每次发版（在 monorepo 根）

```bash
# 确认测试（需 PHP ^8.3）
cd packages/core && composer test

# 回到 monorepo 根，将 packages/core 拆到独立仓并推送 tag
git subtree split --prefix=packages/core -b split/core
git push starship-core split/core:main
git tag -a v1.0.0 split/core -m "starship-admin/core 1.0.0"
git tag -a core-v1.0.0 split/core -m "starship-admin/core 1.0.0"   # 本仓镜像 tag，可选
git push starship-core v1.0.0 core-v1.0.0

# 可选：同步 Gitee 镜像
git push starship-core-gitee split/core:main
git push starship-core-gitee v1.0.0 core-v1.0.0
```

推荐直接跑脚本：

```powershell
# 仅 GitHub
./scripts/publish-core.ps1 -Version 1.0.0 -Remotes starship-core

# GitHub + Gitee 同时推
./scripts/publish-core.ps1 -Version 1.0.0 -Remotes starship-core,starship-core-gitee
```

Tag 规范：独立仓 / Packagist 用 `v1.0.0`；本仓若需镜像可用 `core-v1.0.0`，避免与模板 tag 冲突。

### 3.3 发包就绪检查（packages/core）

- [x] `composer.json` **无** `"version"` 字段（版本来自 git tag）
- [x] `name` = `starship-admin/core`（因 Packagist 上 `starship/*` 已被占用），`type` = `library`
- [x] `extra.laravel.providers` 已声明 `CoreServiceProvider`
- [x] `.gitattributes` 导出时忽略 tests / lock（可选）
- [x] `CHANGELOG.md` 已写 `1.0.0`
- [ ] 独立仓已创建并已在 Packagist 提交
- [ ] 至少一个 SemVer tag（如 `v1.0.0`）

---

## 4. monorepo vs 下游依赖

### 本仓 `master`（开发）

`backend/composer.json` **继续 path**，改内核立即生效：

```json
{
  "repositories": [
    { "type": "path", "url": "../packages/core" },
    { "type": "path", "url": "../modules/System" },
    { "type": "path", "url": "../modules/Generator" }
  ],
  "require": {
    "starship-admin/core": "@dev",
    "starship/system": "@dev",
    "starship/generator": "@dev"
  }
}
```

### 发布分支 / 下游业务项目（`release`，已落地）

```json
{
  "require": {
    "starship-admin/core": "^1.0",
    "starship/system": "@dev",
    "starship/generator": "@dev",
    "starship/member": "@dev"
  },
  "repositories": [
    { "type": "path", "url": "../modules/System" },
    { "type": "path", "url": "../modules/Generator" },
    { "type": "path", "url": "../modules/Member" }
  ]
}
```

- **core**：只从 Packagist 装（`release` 无 `packages/core`）  
- **System / Generator / Member**：path 到同仓 `modules/`（源码可改）  
- **前端**：`apps/admin` 仍在仓内  
- **Shop / Cms / GameShop**：不进 `release`

---

## 5. 与 `starship/skeleton` 模板的关系

| 步骤 | 说明 |
|------|------|
| 1 | 先发 `starship-admin/core` 到 Packagist |
| 2 | `release` 的 backend 改为 `require starship-admin/core:^1.0` |
| 3 | 业务：`git clone -b release` 或日后 `create-project starship/skeleton` |
| 4 | （可选）再把整个模板仓挂上 Packagist |

---

## 6. 相关文件

- `packages/core/composer.json`
- `packages/core/.gitattributes`
- [release-as-template.md](./release-as-template.md)
- [packages/core/README.md](../../packages/core/README.md)
