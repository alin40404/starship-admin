-- 部门管理演示数据（已有库可执行，幂等）
-- 表前缀默认 sa_，若 DB_PREFIX 环境变量 不同请自行替换

SET @root_id := (
    SELECT `id` FROM `sa_departments`
    WHERE `parent_id` = 0 AND `name` = '总公司' AND `deleted_at` IS NULL
    LIMIT 1
);

INSERT INTO `sa_departments` (
    `parent_id`, `name`, `phone`, `email`, `sort`, `status`, `remark`,
    `created_at`, `updated_at`
)
SELECT 0, '总公司', '010-88880000', 'ceo@starship.org', 0, 1, '集团总部', NOW(), NOW()
WHERE @root_id IS NULL;

SET @root_id := (
    SELECT `id` FROM `sa_departments`
    WHERE `parent_id` = 0 AND `name` = '总公司' AND `deleted_at` IS NULL
    LIMIT 1
);

UPDATE `sa_departments`
SET `phone` = '010-88880000', `email` = 'ceo@starship.org',
    `remark` = '集团总部', `updated_at` = NOW()
WHERE `id` = @root_id;

UPDATE `sa_departments` AS d
INNER JOIN `sa_admins` AS a ON a.`username` = 'admin' AND a.`deleted_at` IS NULL
SET d.`leader_id` = a.`id`
WHERE d.`id` = @root_id AND d.`leader_id` IS NULL;

INSERT INTO `sa_departments` (`parent_id`, `name`, `phone`, `sort`, `status`, `remark`, `created_at`, `updated_at`)
SELECT @root_id, src.`name`, src.`phone`, src.`sort`, src.`status`, src.`remark`, NOW(), NOW()
FROM (
    SELECT '研发中心' AS `name`, '010-88880001' AS `phone`, 10 AS `sort`, 1 AS `status`, NULL AS `remark`
    UNION ALL SELECT '市场营销部', '010-88880002', 20, 1, NULL
    UNION ALL SELECT '人力资源部', '010-88880003', 30, 1, NULL
    UNION ALL SELECT '财务部', '010-88880004', 40, 0, '示例：禁用状态'
) AS src
WHERE @root_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_departments` AS d
      WHERE d.`parent_id` = @root_id AND d.`name` = src.`name` AND d.`deleted_at` IS NULL
  );

SET @rd_id := (
    SELECT `id` FROM `sa_departments`
    WHERE `parent_id` = @root_id AND `name` = '研发中心' AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_departments` (`parent_id`, `name`, `sort`, `status`, `created_at`, `updated_at`)
SELECT @rd_id, src.`name`, src.`sort`, 1, NOW(), NOW()
FROM (
    SELECT '前端组' AS `name`, 1 AS `sort`
    UNION ALL SELECT '后端组', 2
    UNION ALL SELECT '测试组', 3
) AS src
WHERE @rd_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_departments` AS d
      WHERE d.`parent_id` = @rd_id AND d.`name` = src.`name` AND d.`deleted_at` IS NULL
  );

-- 部门批量操作按钮权限（已有库若缺少则补）
SET @dept_menu_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/org/departments' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @dept_menu_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'BatchDelete' AS `name`, '批量删除' AS `title`, 'system.departments.batch_delete' AS `permission`, 6 AS `sort`
    UNION ALL SELECT 'BatchStatus', '批量改状态', 'system.departments.batch_status', 7
    UNION ALL SELECT 'Sort', '调整排序', 'system.departments.sort', 9
) AS src
WHERE @dept_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_menus` AS m
      WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL
  );

INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'system.departments.batch_delete',
      'system.departments.batch_status',
      'system.departments.sort'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` AS rm
      WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );
