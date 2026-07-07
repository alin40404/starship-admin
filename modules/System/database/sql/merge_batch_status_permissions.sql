-- 将 batch_enable / batch_disable 合并为 batch_status（已有库迁移）
-- 表前缀默认 sa_，若 DB_PREFIX 环境变量 不同请自行替换
-- 执行后请重新登录以刷新权限

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

-- 1. 插入新 batch_status 按钮（各模块 parent 下，幂等）
INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT p.`id`, 3, 'BatchStatus', '批量改状态', NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT '/permission/admins' AS `path`, 'system.admins.batch_status' AS `permission`, 6 AS `sort`
    UNION ALL SELECT '/permission/roles', 'system.roles.batch_status', 6
    UNION ALL SELECT '/permission/menus', 'system.menus.batch_status', 7
    UNION ALL SELECT '/org/posts', 'system.posts.batch_status', 6
) AS src
INNER JOIN `sa_menus` AS p ON p.`path` = src.`path` AND p.`type` = 2 AND p.`deleted_at` IS NULL
WHERE NOT EXISTS (
    SELECT 1 FROM `sa_menus` AS m
    WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL
);

-- 2. 曾拥有 batch_enable 或 batch_disable 的角色，同步授予 batch_status
INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT rm.`role_id`, nm.`id`
FROM `sa_role_menu` AS rm
INNER JOIN `sa_menus` AS om ON om.`id` = rm.`menu_id` AND om.`deleted_at` IS NULL
INNER JOIN `sa_menus` AS nm ON nm.`deleted_at` IS NULL
    AND nm.`permission` = CONCAT(SUBSTRING_INDEX(om.`permission`, '.', 2), '.batch_status')
WHERE om.`permission` IN (
    'system.admins.batch_enable','system.admins.batch_disable',
    'system.roles.batch_enable','system.roles.batch_disable',
    'system.menus.batch_enable','system.menus.batch_disable',
    'system.posts.batch_enable','system.posts.batch_disable'
)
AND NOT EXISTS (
    SELECT 1 FROM `sa_role_menu` AS x
    WHERE x.`role_id` = rm.`role_id` AND x.`menu_id` = nm.`id`
);

-- 3. 移除旧按钮的角色授权
DELETE rm FROM `sa_role_menu` AS rm
INNER JOIN `sa_menus` AS m ON m.`id` = rm.`menu_id`
WHERE m.`permission` IN (
    'system.admins.batch_enable','system.admins.batch_disable',
    'system.roles.batch_enable','system.roles.batch_disable',
    'system.menus.batch_enable','system.menus.batch_disable',
    'system.posts.batch_enable','system.posts.batch_disable'
);

-- 4. 软删除旧 batch_enable / batch_disable 按钮
UPDATE `sa_menus`
SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `permission` IN (
    'system.admins.batch_enable','system.admins.batch_disable',
    'system.roles.batch_enable','system.roles.batch_disable',
    'system.menus.batch_enable','system.menus.batch_disable',
    'system.posts.batch_enable','system.posts.batch_disable'
)
  AND `deleted_at` IS NULL;
