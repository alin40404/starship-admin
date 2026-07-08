-- 开发工具：插件管理按钮权限（已有环境增量执行）
-- 权限标识与 PluginPermissions 保持一致
-- 表前缀默认 sa_，若 DB_PREFIX 环境变量不同请自行替换

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

SET @plugins_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/dev/plugins' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @plugins_parent_id, 2, 'PluginMgmt', '插件管理', '/dev/plugins', '/dev/plugins/index', 'system.plugins', 'ep:connection', 603, '{"hide_menu":0,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":1}', 1, NOW(), NOW()
WHERE @plugins_parent_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_menus` m WHERE m.`path` = '/dev/plugins' AND m.`type` = 2 AND m.`deleted_at` IS NULL
  );

SET @plugins_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/dev/plugins' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @plugins_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.plugins.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Discover', '发现插件', 'system.plugins.discover', 2
    UNION ALL SELECT 'Install', '安装', 'system.plugins.install', 3
    UNION ALL SELECT 'Enable', '启用', 'system.plugins.enable', 4
    UNION ALL SELECT 'Disable', '禁用', 'system.plugins.disable', 5
    UNION ALL SELECT 'Config', '配置', 'system.plugins.config', 6
) AS src
WHERE @plugins_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'system.plugins', 'system.plugins.query', 'system.plugins.discover',
      'system.plugins.install', 'system.plugins.enable', 'system.plugins.disable',
      'system.plugins.config'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` rm
      WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );
