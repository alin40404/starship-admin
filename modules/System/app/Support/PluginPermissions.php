<?php

namespace Modules\System\App\Support;

/**
 * 插件管理（/dev/plugins）按钮权限，与前端 constants/plugin-permissions.ts 保持一致。
 */
final class PluginPermissions
{
    public const QUERY = 'system.plugins.query';

    public const DISCOVER = 'system.plugins.discover';

    public const INSTALL = 'system.plugins.install';

    public const ENABLE = 'system.plugins.enable';

    public const DISABLE = 'system.plugins.disable';

    public const UNINSTALL = 'system.plugins.uninstall';

    public const CONFIG = 'system.plugins.config';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Discover', 'title' => '发现插件', 'permission' => self::DISCOVER, 'sort' => 2],
            ['name' => 'Install', 'title' => '安装', 'permission' => self::INSTALL, 'sort' => 3],
            ['name' => 'Enable', 'title' => '启用', 'permission' => self::ENABLE, 'sort' => 4],
            ['name' => 'Disable', 'title' => '禁用', 'permission' => self::DISABLE, 'sort' => 5],
            ['name' => 'Uninstall', 'title' => '卸载', 'permission' => self::UNINSTALL, 'sort' => 6],
            ['name' => 'Config', 'title' => '配置', 'permission' => self::CONFIG, 'sort' => 7],
        ];
    }
}
