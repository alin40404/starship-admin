# Changelog

本文件记录 `starship-admin/core` 的对外可见变更。版本号遵循 [SemVer](https://semver.org/lang/zh-CN/)；**以 git tag 为准**（`composer.json` 不写死 `version`）。

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
