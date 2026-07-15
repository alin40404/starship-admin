# 主题导出 / 导入 CLI

| 语义 | 开发态路径 | 导出包目录 |
|------|------------|------------|
| Blade + assets + lang | `themes/{slug}/` | `theme/` |

主题与插件分开：主题**没有**管理端 `backend/` 打包段。详见 [Starship_Theme_开发规范.md](./Starship_Theme_开发规范.md)。

## 命令

在 Laravel `backend/` 目录下执行。

### 脚手架

```bash
php artisan starship:theme:make acme-landing
php artisan starship:theme:make Acme --title="Acme 官网" --description="企业落地页"
```

生成 `themes/{slug}/`（theme.json、layouts、home、assets、lang）。再按生命周期 install / enable / link。

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

### 删除（物理删除）

```bash
php artisan starship:theme:delete acme --force
```

管理端需权限 `system.themes.delete`。策略：**须先卸载**（状态变为未安装）后才能删除。启用中、当前激活主题、以及 `STARSHIP_PROTECTED_THEMES`（默认 `default`）不可删。
