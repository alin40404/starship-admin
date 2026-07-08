-- 监控中心：更新菜单权限、移除在线用户/定时任务、补充按钮权限
-- 表前缀默认 sa_，若 DB_PREFIX 不同请自行替换

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

-- 1. 物理删除「在线用户」「定时任务」菜单及角色关联
DELETE rm
FROM `sa_role_menu` AS rm
INNER JOIN `sa_menus` AS m ON m.`id` = rm.`menu_id`
WHERE m.`path` IN ('/monitor/online-users', '/monitor/scheduled-tasks')
   OR m.`permission` IN ('system.online_users', 'system.scheduled_tasks');

DELETE FROM `sa_menus`
WHERE `path` IN ('/monitor/online-users', '/monitor/scheduled-tasks')
   OR `permission` IN ('system.online_users', 'system.scheduled_tasks');

-- 2. 更新监控中心页面菜单 permission 与 component
UPDATE `sa_menus`
SET `permission` = 'monitor.operation_logs',
    `component` = '/monitor/operation-log/index',
    `updated_at` = NOW()
WHERE `path` = '/monitor/operation-logs' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus`
SET `permission` = 'monitor.login_logs',
    `component` = '/monitor/login-log/index',
    `updated_at` = NOW()
WHERE `path` = '/monitor/login-logs' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus`
SET `permission` = 'monitor.exception_logs',
    `component` = '/monitor/exception-log/index',
    `updated_at` = NOW()
WHERE `path` = '/monitor/exception-logs' AND `type` = 2 AND `deleted_at` IS NULL;

-- 3. 补充按钮权限
SET @oplog_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/monitor/operation-logs' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @oplog_parent_id, 3, 'Query', '查询', NULL, NULL, 'monitor.operation_logs.query', NULL, 1, @btn_other_settings, 1, NOW(), NOW()
WHERE @oplog_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = 'monitor.operation_logs.query' AND m.`deleted_at` IS NULL);

SET @loginlog_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/monitor/login-logs' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @loginlog_parent_id, 3, 'Query', '查询', NULL, NULL, 'monitor.login_logs.query', NULL, 1, @btn_other_settings, 1, NOW(), NOW()
WHERE @loginlog_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = 'monitor.login_logs.query' AND m.`deleted_at` IS NULL);

SET @exlog_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/monitor/exception-logs' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @exlog_parent_id, 3, 'Query', '查询', NULL, NULL, 'monitor.exception_logs.query', NULL, 1, @btn_other_settings, 1, NOW(), NOW()
WHERE @exlog_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = 'monitor.exception_logs.query' AND m.`deleted_at` IS NULL);

-- 4. 超级管理员同步新按钮权限
INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'monitor.operation_logs', 'monitor.operation_logs.query',
      'monitor.login_logs', 'monitor.login_logs.query',
      'monitor.exception_logs', 'monitor.exception_logs.query'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` rm
      WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );
