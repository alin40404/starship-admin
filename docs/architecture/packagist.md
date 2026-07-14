# Packagist 发布约定（core + 模板）

> 决策：**前端**随应用仓（本仓 / `release`）交付，不走 npm 发包。  
> **`starship/core`** 走 Packagist（Composer）。  
> **`modules/System`、`modules/Generator`**：按架构共识仍以源码进业务项目（`modules/`），**近期不单独上 Packagist**（与 checklist「底座模块不走 Composer 包」一致）。若日后改口径再拆包。

---

## 1. 包一览

| 制品 | 交付方式 | 包名 |
|------|----------|------|
| 内核 | **Packagist** | `starship/core` |
| 应用模板 | 本仓 `release` + 日后 Packagist `starship/skeleton` | `starship/skeleton` |
| System / Generator | 模板仓内 `modules/` 源码 | （可选日后 `starship/system`） |
| 管理端前端 | 模板仓内 `apps/admin` | 非 Packagist |

---

## 2. 为何 core 要单独发包

Packagist **一仓一包**。本仓是 monorepo，不能把整个 `starship-admin` 注册成 `starship/core`。

推荐：

```text
本仓 packages/core  ──subtree split──►  独立仓 starship-core
                                              │
                                              ▼
                                         Packagist: starship/core
```

日常仍在 monorepo 改 `packages/core`；发版时 split + tag + Packagist 更新。

---

## 3. 发布 `starship/core` 步骤

### 3.1 一次性：独立仓库 + Packagist

1. 建空仓（例：`github.com/<org>/starship-core`），仅用于发布产物  
2. Packagist → Submit → 填该仓 URL → 包名确认为 `starship/core`  
3. 打开 GitHub Service Hook / Packagist 自动更新  

### 3.2 每次发版（在 monorepo 根）

```bash
# 确认测试
cd packages/core && composer test

# 回到 monorepo 根，将 packages/core 拆到独立仓并推送 tag
git subtree split --prefix=packages/core -b split/core
git push <starship-core-remote> split/core:master

# 在 tags 上打 SemVer（不要在 composer.json 写死 version）
git tag -a core-v0.1.0 split/core -m "starship/core 0.1.0"
# 或在独立仓内：
# git tag -a v0.1.0 -m "0.1.0" && git push --tags
git push <starship-core-remote> --tags
```

更稳妥的长期方案：在 **独立仓** 内打 `v0.1.0`，本仓用 subtree push：

```bash
git subtree push --prefix=packages/core <starship-core-remote> master
# 再到 starship-core 仓打 tag v0.1.0 并 push tags
```

Tag 规范建议：独立仓用 `v0.1.0`；本仓若需镜像可用 `core-v0.1.0`，避免与模板 tag `v0.1.0` 冲突。

### 3.3 发包就绪检查（packages/core）

- [x] `composer.json` **无** `"version"` 字段（版本来自 git tag）
- [x] `name` = `starship/core`，`type` = `library`
- [x] `.gitattributes` 导出时忽略 tests（可选）
- [ ] 独立仓已创建并已在 Packagist 提交
- [ ] 至少一个 SemVer tag（如 `v0.1.0`）

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
    "starship/core": "@dev",
    "starship/system": "@dev",
    "starship/generator": "@dev"
  }
}
```

### 发布分支 / 下游业务项目（目标）

```json
{
  "require": {
    "starship/core": "^0.1"
  },
  "repositories": [
    { "type": "path", "url": "../modules/System" },
    { "type": "path", "url": "../modules/Generator" }
  ]
}
```

- **core**：只从 Packagist 装  
- **System / Generator**：仍 path 到同仓 `modules/`（源码可改）  
- **前端**：`apps/admin` 仍在仓内  

`release` 合并前应把 `backend/composer.json` 调成「core 用版本约束、去掉 core 的 path」。

---

## 5. 与 `starship/skeleton` 模板的关系

| 步骤 | 说明 |
|------|------|
| 1 | 先发 `starship/core` 到 Packagist |
| 2 | `release` 的 backend 改为 `require starship/core:^0.1` |
| 3 | 业务：`git clone -b release` 或日后 `create-project starship/skeleton` |
| 4 | （可选）再把整个模板仓挂上 Packagist |

---

## 6. 相关文件

- `packages/core/composer.json`
- `packages/core/.gitattributes`
- [release-as-template.md](./release-as-template.md)
- [packages/core/README.md](../../packages/core/README.md)
