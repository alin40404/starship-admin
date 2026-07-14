# 模块导出 / 导入 CLI

对应验收清单：[module-export-import-acceptance.md](./module-export-import-acceptance.md)

## 命令

在 `backend/` 下执行：

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

## 导入后仍需人工确认

1. `composer.json` path / require（新模块）  
2. `module.json` 的 `enabled`  
3. 管理员角色权限  
4. 前端 `pnpm -F @vben/web-antd run dev` 或 build  

## 实现位置

- `packages/core/src/Console/Commands/ModuleExportCommand.php`
- `packages/core/src/Console/Commands/ModuleImportCommand.php`
- `packages/core/src/Module/ModulePackageBuilder.php`
