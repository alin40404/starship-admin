# Changelog

本文件记录 `starship-admin/core` 的对外可见变更。版本号遵循 [SemVer](https://semver.org/lang/zh-CN/)；**以 git tag 为准**（`composer.json` 不写死 `version`）。

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
