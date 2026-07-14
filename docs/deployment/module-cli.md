# 模块导出 / 导入 CLI

对应验收清单：[module-export-import-acceptance.md](./module-export-import-acceptance.md)

## 命令

在 `backend/` 下执行：

### 创建

```bash
php artisan starship:module:make shop
php artisan starship:module:make shop --title=商城
php artisan starship:module:make shop --title=商城 --no-update
```

| 选项 | 说明 |
|------|------|
| `--title=` | 显示名称（`module.json` 的 `title` 与语言包），默认与模块名相同 |
| `--force` | 覆盖已存在的关键文件 |
| `--no-composer` | 不自动改 `backend/composer.json` / 不跑 `composer update` |
| `--no-update` | 只写 `composer.json`，不执行 `composer update` |

### 删除时 Composer 清理

先关掉模块（`module.json` → `"enabled": false`）或直接删目录前，在 `backend/` 执行：

```bash
composer remove starship/shop
```

这会从 `require` / lock / `vendor` 卸掉包。path 仓库条目（`repositories` 里的 `../modules/Shop`）有时仍会留着，需手动删掉该段，或再跑一次 `composer update` 确认无报错。

完整建议顺序：

1. `enabled: false`（可选）并重启  
2. `composer remove starship/{slug}`  
3. 删除 `modules/Shop`（以及前端 `features/shop` 若有）  
4. 检查并去掉 `composer.json` 里残留的 path `repositories`  
5. 若 `autoload-dev` / `phpunit.xml` 挂过该模块 tests，一并删掉后 `composer dump-autoload`

### 导出

```bash
php artisan starship:module:export member
php artisan starship:module:export Member --output=D:/packs
php artisan starship:module:export member --dir
```

| 选项 | 说明 |
|------|------|
| `--output=` | 输出目录，默认 `storage/app/starship-modules` |
| `--dir` | 只输出目录，不打 zip |

产物结构（与验收清单一致）：

```text
member-0.2.0.zip  （或同名目录）
└── member-0.2.0/
    ├── manifest.json
    ├── backend/      # modules/Member
    └── admin-ui/     # apps/admin/web-antd/src/features/member
```

### 导入

```bash
php artisan starship:module:import storage/app/starship-modules/member-0.2.0.zip
php artisan starship:module:import ./member-0.2.0 --force --migrate --seed
php artisan starship:module:import ./member-0.2.0.zip --no-admin-ui
```

| 选项 | 说明 |
|------|------|
| `--force` | 覆盖已存在的 backend / admin-ui |
| `--migrate` | 运行该模块 `database/migrations` |
| `--seed` | 运行 manifest 中的 seeders（需已 autoload） |
| `--no-admin-ui` | 只导入 PHP 后端 |
| `--no-composer` | 不自动改 `backend/composer.json` / 不跑 `composer update` |
| `--no-update` | 只写 `composer.json`，不执行 `composer update` |

`make` / `import` 默认会写入 path + `require @dev` 并执行 `composer update starship/{slug}`。

## 导入后仍需人工确认

1. `module.json` 的 `enabled`（若用了 `--no-composer`，还需确认 path / require）  
2. 管理员角色权限  
3. 前端 `pnpm -F @vben/web-antd run dev` 或 build  

## 实现位置

- `packages/core/src/Console/Commands/ModuleExportCommand.php`
- `packages/core/src/Console/Commands/ModuleImportCommand.php`
- `packages/core/src/Module/ModulePackageBuilder.php`
- `packages/core/src/Module/ModuleComposerLinker.php`
