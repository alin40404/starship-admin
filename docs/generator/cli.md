# Generator CLI 使用说明

Starship Generator 提供以下 Artisan 命令，统一归属 **`starship:generator:*`** 命名空间，与 `starship:module:*`、`starship:plugin:*` 保持一致。

## 命令列表

| 命令 | 说明 |
|---|---|
| `php artisan starship:generator:tables` | 列出数据库物理表 |
| `php artisan starship:generator:inspect {table}` | 查看字段及智能推断配置 |
| `php artisan starship:generator:import {table}` | 导入表结构到生成器配置 |
| `php artisan starship:generator:crud {table}` | 生成或删除 CRUD 代码 |

## 快速开始

```bash
cd backend

# 1. 查看已有物理表
php artisan starship:generator:tables

# 2. 查看字段推断（{table} 为物理表名，可省略 sa_ 前缀）
php artisan starship:generator:inspect {table}

# 3. 预览将生成的文件
php artisan starship:generator:crud {table} --preview

# 4. 执行生成
php artisan starship:generator:crud {table} --module=System --business=业务名称

# 5. 若文件已存在，需要覆盖时
php artisan starship:generator:crud {table} --force
```

## 常用参数

```bash
php artisan starship:generator:crud {table} \
  --module=System \
  --class=YourModel \
  --business=业务名称 \
  --route-group=org \
  --api-prefix=your-api \
  --route-path=/org/your-path \
  --permission-prefix=system.your_api \
  --menu-parent=/org \
  --preview \
  --force \
  --skip-menu
```

## 删除模式

```bash
# 预览将删除的文件 / 菜单
php artisan starship:generator:crud {table} --module=System --delete --preview

# 删除生成的代码、菜单与 generator_tables 配置
php artisan starship:generator:crud {table} --module=System --delete

# 同时删除物理表
php artisan starship:generator:crud {table} --module=System --delete --drop-table

# 跳过确认
php artisan starship:generator:crud {table} --module=System --delete --force
```

删除范围：
- 后端 Model / Controller / Permissions / admin-generator 路由文件
- 前端 api / constants / views
- `sa_menus` 中对应页面与按钮权限（可用 `--skip-menu` 跳过）
- `generator_tables` / `generator_columns` / `generator_records`（若存在配置）
- 物理表（仅 `--drop-table` 时）

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
- `locales/langs/zh-CN/{module}.json`（合并 entity 翻译 key）
- `locales/langs/en-US/{module}.json`（合并 entity 翻译 key）

**后端语言包（首次生成时自动 ensure）**
- `resources/lang/zh_CN/messages.php`
- `resources/lang/en/messages.php`
