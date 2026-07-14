<?php

namespace Modules\System\App\Support;

/**
 * 主题管理（/dev/themes）按钮权限，与前端 constants/theme-permissions.ts 保持一致。
 */
final class ThemePermissions
{
    public const QUERY = 'system.themes.query';

    public const DISCOVER = 'system.themes.discover';

    public const INSTALL = 'system.themes.install';

    public const ENABLE = 'system.themes.enable';

    public const DISABLE = 'system.themes.disable';

    public const UNINSTALL = 'system.themes.uninstall';

    public const SWITCH = 'system.themes.switch';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Discover', 'title' => '发现主题', 'permission' => self::DISCOVER, 'sort' => 2],
            ['name' => 'Install', 'title' => '安装', 'permission' => self::INSTALL, 'sort' => 3],
            ['name' => 'Enable', 'title' => '启用', 'permission' => self::ENABLE, 'sort' => 4],
            ['name' => 'Disable', 'title' => '禁用', 'permission' => self::DISABLE, 'sort' => 5],
            ['name' => 'Uninstall', 'title' => '卸载', 'permission' => self::UNINSTALL, 'sort' => 6],
            ['name' => 'Switch', 'title' => '切换当前', 'permission' => self::SWITCH, 'sort' => 7],
        ];
    }
}
