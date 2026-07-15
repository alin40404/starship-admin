# 插件导出 / 导入 CLI

| 语义 | 开发态路径 | 导出包目录 |
|------|------------|------------|
| PHP + 前台 `frontend/` | `plugins/{Studly}/` | `plugin/` |
| 后台前端 | `apps/admin/web-antd/src/plugins/{slug}/` | `backend/` |

详见 [Starship_Plugin_开发规范.md](./Starship_Plugin_开发规范.md)。

## 命令

在 Laravel `backend/` 目录下执行。

### 导出

```bash
php artisan starship:plugin:export mail-smtp
php artisan starship:plugin:export mail-smtp --dir
```

产物：

```text
mail-smtp-1.0.0/
├── manifest.json
├── plugin/              # → plugins/MailSmtp（含 frontend/）
└── backend/             # → apps/admin/web-antd/src/plugins/mail-smtp
```

### 导入

```bash
php artisan starship:plugin:import ./mail-smtp-1.0.0.zip --force
php artisan starship:plugin:import ./mail-smtp-1.0.0 --no-backend
```

导入后：`starship:plugin:install` / `enable`，并重启前端 dev。
