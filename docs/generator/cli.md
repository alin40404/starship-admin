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

# 4. 执行生成（--module 与 module.json 的 name 一致，小写即可，大小写不敏感）
php artisan starship:generator:crud {table} --module=system --business=业务名称

# 5. 若文件已存在，需要覆盖时
php artisan starship:generator:crud {table} --force
```

## 常用参数

```bash
php artisan starship:generator:crud {table} \
  --module=system \
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
php artisan starship:generator:crud {table} --module=system --delete --preview

# 删除生成的代码、菜单与 generator_tables 配置
php artisan starship:generator:crud {table} --module=system --delete

# 同时删除物理表
php artisan starship:generator:crud {table} --module=system --delete --drop-table

# 跳过确认
php artisan starship:generator:crud {table} --module=system --delete --force
```

**`--module` 与删除：**

- 若 `generator_tables` 中已有该表记录：**省略 `--module` 即可**，自动使用记录中的 `module_slug`（如 `demo_products` 不会误推断为 `Demo` 模块）。
- 若指定 `--module`：**必须与记录中的 `module_slug` 一致**（大小写不敏感，`system` 与 `System` 等价），否则会报错并中止，避免误删其他模块产物。
- 若无 DB 记录：按 `--module` 及当前参数**推断路径**删除文件（不删 `generator_tables`）；未指定时按表名前缀推断（如 `demo_products` → `Demo`，通常需显式传 `--module`）。
- 省略 `--module` 时：有记录按记录删除；无记录则按表名推断。

删除范围：
- 后端 Model / Controller / Permissions / admin-generator 路由文件
- 前端 api / constants / views
- `sa_menus` 中对应页面与按钮权限（可用 `--skip-menu` 跳过）
- `generator_tables` / `generator_columns` / `generator_records`（若存在配置）
- 物理表（仅 `--drop-table` 时）

## 生成策略（已确认）

- 落盘位置：用户可选模块（`--module`，与 `module.json` 的 `name` 一致，推荐小写如 `system`、`generator`），模块必须已存在
- **`status` 字段推断**：
  - `tinyint` / `int` 等整型 → 启用/禁用（`0` / `1`），Switch + 批量改状态
  - `varchar` 等字符串 → 从字段注释解析枚举（如 `状态: normal,hidden`），Switch 使用对应字符串值；未写注释时默认 `normal` / `hidden`
  - 重新生成时会读取数据库 `information_schema` 注释，不依赖旧的 `generator_columns.validation_rules`
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
- `locales/langs/zh-CN/{module}.{entity}.json`（实体独立翻译，如 `system.products.json`）
- `locales/langs/en-US/{module}.{entity}.json`
- `locales/langs/zh-CN/{module}.json`（仅合并菜单名 `menu.*`，不写入实体字段）

卸载 `--delete` 时会删除实体独立翻译文件，并从 `{module}.json` 移除对应菜单 key，不影响同模块其他实体。

**后端语言包（首次生成时自动 ensure）**
- `resources/lang/zh_CN/messages.php`
- `resources/lang/en/messages.php`
