-- 平台设置（系统配置 / 字典 / 区域）按钮权限
-- 权限标识与 modules/System/app/Support/*Permissions.php 保持一致
-- 表前缀默认 sa_，若 config('starship.table_prefix') 不同请自行替换

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

-- 系统配置
SET @config_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/platform/configs' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @config_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.configs.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Update', '保存配置', 'system.configs.update', 2
    UNION ALL SELECT 'Delete', '删除', 'system.configs.delete', 3
    UNION ALL SELECT 'BatchDelete', '批量删除', 'system.configs.batch_delete', 4
) AS src
WHERE @config_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

-- 字典管理
SET @dict_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/platform/dicts' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @dict_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.dicts.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Create', '新增', 'system.dicts.create', 2
    UNION ALL SELECT 'Update', '编辑', 'system.dicts.update', 3
    UNION ALL SELECT 'Delete', '删除', 'system.dicts.delete', 4
) AS src
WHERE @dict_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

-- 区域管理
SET @region_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/platform/regions' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @region_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.regions.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Create', '新增区域', 'system.regions.create', 2
    UNION ALL SELECT 'Append', '新增下级', 'system.regions.append', 3
    UNION ALL SELECT 'Update', '编辑', 'system.regions.update', 4
    UNION ALL SELECT 'Delete', '删除', 'system.regions.delete', 5
    UNION ALL SELECT 'BatchDelete', '批量删除', 'system.regions.batch_delete', 6
) AS src
WHERE @region_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

-- 超级管理员同步新按钮权限
INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'system.configs.query', 'system.configs.update',
      'system.dicts.query', 'system.dicts.create', 'system.dicts.update', 'system.dicts.delete',
      'system.regions.query', 'system.regions.create', 'system.regions.append',
      'system.regions.update', 'system.regions.delete', 'system.regions.batch_delete'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` rm
      WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );
