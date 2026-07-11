# starship/core

Starship Admin 框架内核包（Layer 1）。

## 包含

- `Module/` — 模块扫描（`ModuleManager`、`module.json`）
- `Plugin/` — 插件引擎（发现、安装、启用、路由/迁移注册）
- `Models/` — `Plugin`、`PluginConfig` 持久化模型
- `Support/ApiResponse.php` — 统一 API 响应
- `Console/Commands/` — `starship:plugin:*` 命令
- `Providers/CoreServiceProvider.php` — 注册 Module + Plugin

## 使用

`backend/composer.json` 通过 path repository 引用：

```json
{
  "require": {
    "starship/core": "@dev"
  },
  "repositories": [
    { "type": "path", "url": "../packages/starship-core" }
  ]
}
```

`backend/bootstrap/providers.php`：

```php
return [
    App\Providers\AppServiceProvider::class,
    Starship\Core\Providers\CoreServiceProvider::class,
];
```

## 配置

`config/starship.php`（由 CoreServiceProvider 合并）：

| 环境变量 | 默认值 | 说明 |
|----------|--------|------|
| `STARSHIP_MODULES_PATH` | `{repo}/modules` | 底座模块目录 |
| `STARSHIP_PLUGINS_PATH` | `{repo}/plugins` | 插件目录 |

## 依赖原则

- 可依赖 `laravel/framework`
- 不依赖 `Modules\System\*` 等业务模块

详见 [docs/architecture/framework.md](../../docs/architecture/framework.md)。
