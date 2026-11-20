# 开发仓兼发布仓：`starship/skeleton` 发布约定

> 本仓库同时承担：**框架研发**（`master`）与 **应用模板发布**（`release` / 版本 tag）。  
> **内核 `starship-admin/core` 走 Packagist**；前端随仓交付。详见 [packagist.md](./packagist.md)。

---

## 1. 分支职责

| 分支 / 标签 | 用途 |
|-------------|------|
| `master`（或 `main`） | 日常开发：path `@dev` 调 core、完整业务模块与文档 |
| `release` | 下游业务起点；**core 用 Packagist**；底座模块仅 System + Generator + Member |
| `v1.x.y` / `core-v1.x.y` | 模板 tag / core 镜像 tag（core 正式 tag 在独立发布仓） |

---

## 2. 依赖策略

| 组件 | `master`（开发） | `release` / 下游 |
|------|------------------|---------------------------|
| `starship-admin/core` | path `../packages/core` `@dev` | Packagist `^1.0`（无 path、无 `packages/core`） |
| System / Generator / Member | path `../modules/*` | 同仓 `modules/` + path（源码可改，不上 Packagist） |
| Shop / Cms / GameShop 等 | 同仓研发 | **不进** `release` |
| 前端 `apps/admin` | 全集 | 同仓；可裁剪 playground / docs |

---

## 3. 下游怎么用

**前提**：`starship-admin/core` 已在 Packagist（`composer show starship-admin/core`）。

```bash
git clone -b release git@gitee.com:allanchan004/starship-admin.git my-admin
cd my-admin/backend
composer install          # Packagist 拉 core；System/Generator/Member 用 path
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve

cd ../apps/admin && pnpm install && pnpm dev
```

业务项目在此底座上新增自己的 `modules/*`（`starship:module:make` 或导入），勿回写本仓 `master` 的业务样例。

---

## 4. 维护者：刷新 `release`

```bash
git checkout master && git pull
git checkout release
git merge master          # 或按需 cherry-pick
# 确认 backend：core 仍为 ^1.0、无 packages/core path
# 确认未把 Shop/Cms/GameShop 再带进 release
git push origin release
```

约定：框架演进在 `master`；`release` 只承载对外模板，不堆长期业务提交。

---

## 5. 发布检查清单

- [x] 根 `composer.json`：`name` = `starship/skeleton`
- [x] `starship-admin/core` 已在 Packagist 可安装（`v1.0.0`）
- [x] `release` 上 `backend` 已去掉 core 的 path，改为 `^1.0`
- [x] 底座模块：System + Generator + Member（path）
- [x] `release` 已移除 Shop / Cms / GameShop 与 `packages/core`
- [ ] `php artisan test`（FrameworkSmokeTest）通过（下游环境）
- [ ] `GET /admin-api/ping` 可用（下游环境）
- [ ] 前端可 `pnpm dev`

---

## 6. Packagist 分阶段

1. **已完成** `starship-admin/core`（独立仓 + Packagist）——见 [packagist.md](./packagist.md)
2. **已完成** `release` 的 backend 依赖与树精简
3. **可选** 将本仓（模板）登记为 Packagist 的 `starship/skeleton`
4. System / Generator / Member 上 Packagist：**非近期目标**（源码模块可改）

---

## 7. 相关文档

- [packagist.md](./packagist.md) — core 发包与 subtree
- [skeleton-backend.md](./skeleton-backend.md)
- [framework.md](./framework.md)
