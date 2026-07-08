-- 监控中心：操作日志 / 登录日志 删除与批量删除按钮权限（已有环境增量执行）
-- 表前缀默认 sa_，若 DB_PREFIX 不同请自行替换

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

SET @oplog_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/monitor/operation-logs' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @oplog_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Delete' AS `name`, '删除' AS `title`, 'monitor.operation_logs.delete' AS `permission`, 2 AS `sort`
    UNION ALL SELECT 'BatchDelete', '批量删除', 'monitor.operation_logs.batch_delete', 3
) AS src
WHERE @oplog_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

SET @loginlog_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/monitor/login-logs' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @loginlog_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Delete' AS `name`, '删除' AS `title`, 'monitor.login_logs.delete' AS `permission`, 2 AS `sort`
    UNION ALL SELECT 'BatchDelete', '批量删除', 'monitor.login_logs.batch_delete', 3
) AS src
WHERE @loginlog_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

SET @exlog_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/monitor/exception-logs' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @exlog_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Delete' AS `name`, '删除' AS `title`, 'monitor.exception_logs.delete' AS `permission`, 2 AS `sort`
    UNION ALL SELECT 'BatchDelete', '批量删除', 'monitor.exception_logs.batch_delete', 3
) AS src
WHERE @exlog_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'monitor.operation_logs.delete', 'monitor.operation_logs.batch_delete',
      'monitor.login_logs.delete', 'monitor.login_logs.batch_delete',
      'monitor.exception_logs.delete', 'monitor.exception_logs.batch_delete'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` rm
      WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );
