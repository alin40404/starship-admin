# 主题导出 / 导入 CLI

| 语义 | 开发态路径 | 导出包目录 |
|------|------------|------------|
| Blade + assets + lang | `themes/{slug}/` | `theme/` |

主题与插件分开：主题**没有**管理端 `backend/` 打包段。详见 [Starship_Theme_开发规范.md](./Starship_Theme_开发规范.md)。

## 命令

在 Laravel `backend/` 目录下执行。

### 导出

```bash
php artisan starship:theme:export default
php artisan starship:theme:export corporate --dir
```

默认输出：`storage/app/starship-themes/{slug}-{version}.zip`（`--dir` 则落目录）。

产物：

```text
default-1.1.0/
├── manifest.json
└── theme/                 # → themes/default
    ├── theme.json
    ├── assets/
    ├── lang/
    └── views/
```

### 导入

```bash
php artisan starship:theme:import ./default-1.1.0.zip --force
php artisan starship:theme:import ./default-1.1.0
```

导入后建议：

```bash
php artisan starship:theme:install {slug}
php artisan starship:theme:enable {slug}
php artisan starship:theme:link {slug}
```
