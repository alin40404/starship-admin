# Generator CLI 使用说明

参考 FastAdmin / BuildAdmin 的命令行 CRUD 生成模式，Starship 提供以下 Artisan 命令。

## 命令列表

| 命令 | 说明 |
|---|---|
| `php artisan generator:tables` | 列出数据库物理表 |
| `php artisan generator:inspect {table}` | 查看字段及智能推断配置 |
| `php artisan generator:import {table}` | 导入表结构到生成器配置 |
| `php artisan generator:crud {table}` | 生成 CRUD 代码 |

## 快速开始（Demo 表）

```bash
cd backend

# 1. 执行迁移（含 sa_system_demo_article 演示表）
php artisan migrate

# 2. 查看演示表字段推断
php artisan generator:inspect sa_system_demo_article

# 3. 预览将生成的文件
php artisan generator:crud sa_system_demo_article --preview

# 4. 执行生成
php artisan generator:crud sa_system_demo_article --module=System --business=演示文章

# 5. 若文件已存在，需要覆盖时
php artisan generator:crud sa_system_demo_article --force
```

## 常用参数

```bash
php artisan generator:crud sa_system_demo_article \
  --module=System \
  --class=DemoArticle \
  --business=演示文章 \
  --route-group=org \
  --api-prefix=demo-articles \
  --route-path=/org/demo-articles \
  --permission-prefix=system.demo_articles \
  --menu-parent=/org \
  --preview \
  --force \
  --skip-menu
```

## 生成策略（已确认）

- 落盘位置：用户可选模块（`--module`），模块必须已存在
- 冲突策略：默认跳过已存在文件，`--force` 覆盖
- 菜单注册：写入 DB `component` 字段，不改 `menu.ts`
- 路由注册：写入目标模块 `routes/admin-generator/{table_suffix}.php`，由 `routes/admin.php` 中的 loader 自动加载
- 审计：写入 `sa_generator_records`，并触发 `generator.code.generated` 事件

## 生成产物

**后端（目标模块）**
- `app/Models/{Class}.php`
- `app/Http/Controllers/Admin/{Class}Controller.php`
- `app/Support/{Class}Permissions.php`
- `routes/admin-generator/{table_suffix}.php` 管理端独立路由文件
- `routes/admin-generator/_loader.php` 管理端路由加载器（首次生成时自动创建）

**前置条件（每个模块一次性配置）**

在目标模块 `routes/admin.php` 的对应路由组内添加：

```php
require __DIR__.'/admin-generator/_loader.php';
```

**前端**
- `api/system/{entity}.ts`
- `constants/{entity}-permissions.ts`
- `views/{group}/{entity}/index.vue`
- `views/{group}/{entity}/data.ts`
- `views/{group}/{entity}/modules/form.vue`
