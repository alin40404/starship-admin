# Changelog

本文件记录 `starship-admin/core` 的对外可见变更。版本号遵循 [SemVer](https://semver.org/lang/zh-CN/)；**以 git tag 为准**（`composer.json` 不写死 `version`）。

## [1.2.6] — 2026-11-26

### Changed

- `starship:install` 命令按 prepare / migrate / seed / finalize 分步显示进度与百分比
- `starship:install` 交互输入数据库密码、超级管理员密码改为明文显示，超管密码默认 `admin123`（与账号/邮箱默认值写法一致）

## [1.2.5] — 2026-11-26

### Changed

- Web 安装向导在 PHP 内置开发服务器（`php artisan serve` / `php -S`）下禁用；`GET /install/status` 返回 `web_install_allowed` / `runtime`，请改用 `php artisan starship:install` 或正式 Web 服务
- 移除安装流程中针对 `artisan serve` 的特殊兼容（`syncProcess`、读 `.env` 旁路等），安装逻辑简化

## [1.2.4] — 2026-11-26

### Fixed

- 安装 migrate/seed 改为直接读取 `.env` 刷新数据库配置，避免 `env()` 仍返回进程内旧的 `sqlite`

## [1.2.3] — 2026-11-26

### Fixed

- 安装写入数据库配置后同步进程环境变量，避免 `artisan serve` 同进程仍沿用启动时的 `DB_CONNECTION=sqlite`

## [1.2.2] — 2026-11-26

### Fixed

- 安装 `prepare` 不再写 `.env`，避免 `php artisan serve` 热重启导致下一步 `migrate` 502
- 安装不再把 `SYSTEM_ADMIN_*` 明文写入 `.env`（seed 仅用请求注入）

### Changed

- `APP_NAME` 改在 `finalize` 持久化到 `.env`

## [1.2.1] — 2026-11-26

### Added

- `EnsureInstalled` 中间件与别名 `admin.installed`：未安装时拦截业务 `admin-api`（安装接口 / `ping` 放行）

## [1.2.0] — 2026-11-26

### Added

- 安装引导支持站点名称配置
- 安装改为分步执行（migrate / seed / storage:link），并可通过 API 反馈真实进度

### Changed

- 安装表单必填校验与错误提示更明确

## [1.1.0] — 2026-11-25

### Added

- 项目安装引导：`InstallService`、`starship:install` CLI、公开 API `admin-api/install/*`、安装锁 `storage/app/starship.installed`

## [1.0.0] — 2026-11-20

### Added

- Module / Plugin / Theme 引擎（发现、安装、启用、禁用、卸载、导入导出）
- 公共 Contract（Sms / Mail / Storage / Payment / Member / Admin / Notification / Permission / Config）
- `EventDispatcherInterface` 与 `PluginDescriptionResolving`
- `theme_asset()` / `plugin_asset()` 与静态资源回落路由
- `CoreServiceProvider` 自动发现（`extra.laravel.providers`）

### Notes

- 首个稳定发行版；Composer 包名为 **`starship-admin/core`**（Packagist 上 `starship/*` vendor 已被第三方占用）
- 下游约束建议 `starship-admin/core: ^1.0`
- PHP 命名空间仍为 `Starship\Core\*`
- 发布制品来自 monorepo `packages/core` 的 subtree 独立仓 tag `v1.0.0`
