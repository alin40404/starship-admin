# 开发仓兼发布仓：`starship/skeleton` 发布约定

> 本仓库同时承担：**框架研发**（`master`）与 **应用模板发布**（`release` / 版本 tag）。  
> **内核 `starship-admin/core` 走 Packagist**；前端随仓交付。详见 [packagist.md](./packagist.md)。

---

## 1. 分支职责

| 分支 / 标签 | 用途 |
|-------------|------|
| `master`（或 `main`） | 日常开发：path `@dev` 调 core、完整文档与前端 |
| `release` | 下游业务起点；**core 用 Packagist 版本约束** |
| `v1.x.y` / `core-v1.x.y` | 模板 tag / core 镜像 tag（core 正式 tag 在独立发布仓） |

---

## 2. 依赖策略

| 组件 | `master`（开发） | `release` / 下游 |
|------|------------------|---------------------------|
| `starship-admin/core` | path `../packages/core` `@dev` | Packagist `^1.0`（无 path） |
| System / Generator | path `../modules/*` | 同仓 `modules/` + path（源码可改，不上 Packagist） |
| 前端 `apps/admin` | 全集 | 同仓；可裁剪 playground / docs |

---

## 3. 下游怎么用

**前提**：`starship-admin/core` 已发布到 Packagist（见 [packagist.md](./packagist.md)）。

```bash
git clone -b release <本仓 URL> my-erp-admin
cd my-erp-admin/backend
composer install          # 从 Packagist 拉 core；modules 仍用 path
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve

cd ../apps/admin && pnpm install && pnpm dev
```

未上 Packagist 前，可临时在 clone 的项目里改回 path 指向同仓 `packages/core`（仅过渡）。

---

## 4. 维护者：更新发布分支

```bash
git checkout master && git pull

# core 已打 tag 并在 Packagist 可见后：
git checkout -B release

# 将 backend/composer.json 调整为（示例）：
#   require: "starship-admin/core": "^1.0"
#   repositories: 仅保留 System / Generator 的 path（去掉 core path）

git add backend/composer.json
git commit -m "chore(release): starship-admin/core 改用 Packagist"
git push -u origin release
```

约定：业务开发仍在 `master`；发布分支不堆长期业务提交。

---

## 5. 发布前检查清单

- [x] 根 `composer.json`：`name` = `starship/skeleton`
- [ ] `starship-admin/core` 已在 Packagist 可安装（`composer show starship-admin/core`）
- [ ] `release` 上 `backend` 已去掉 core 的 path，改为版本约束
- [ ] System / Generator 仍可通过 path 或目录加载
- [ ] `php artisan test`（FrameworkSmokeTest）通过
- [ ] `GET /admin-api/ping` 可用
- [ ] 前端可 `pnpm dev`

---

## 6. Packagist 分阶段

1. **先发** `starship-admin/core`（独立仓 + subtree）——见 [packagist.md](./packagist.md)  
2. **再改** `release` 的 backend 依赖  
3. **可选** 将本仓（模板）登记为 Packagist 的 `starship/skeleton`  
4. System / Generator 上 Packagist：**非近期目标**（源码模块可改）

---

## 7. 相关文档

- [packagist.md](./packagist.md) — core 发包与 subtree  
- [skeleton-backend.md](./skeleton-backend.md)  
- [framework.md](./framework.md)  
- [packages/core/README.md](../../packages/core/README.md)
