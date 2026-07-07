-- 管理员管理（/permission/admins）按钮权限
-- 权限标识与 modules/System/app/Support/AdminPermissions.php 保持一致
-- 表前缀默认 sa_，若 DB_PREFIX 环境变量 不同请自行替换
-- 适用：已初始化库、System 模块 Seeder 已跑过但未包含完整管理员按钮权限时执行

SET @admin_parent_id := (
    SELECT `id`
    FROM `sa_menus`
    WHERE `path` = '/permission/admins'
      AND `type` = 2
      AND `deleted_at` IS NULL
    LIMIT 1
);

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

INSERT INTO `sa_menus` (
    `parent_id`,
    `type`,
    `name`,
    `title`,
    `path`,
    `component`,
    `permission`,
    `icon`,
    `sort`,
    `other_settings`,
    `status`,
    `created_at`,
    `updated_at`
)
SELECT
    @admin_parent_id,
    3,
    src.`name`,
    src.`title`,
    NULL,
    NULL,
    src.`permission`,
    NULL,
    src.`sort`,
    @btn_other_settings,
    1,
    NOW(),
    NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.admins.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Create', '新增', 'system.admins.create', 2
    UNION ALL SELECT 'Update', '编辑', 'system.admins.update', 3
    UNION ALL SELECT 'Delete', '删除', 'system.admins.delete', 4
    UNION ALL SELECT 'BatchDelete', '批量删除', 'system.admins.batch_delete', 5
    UNION ALL SELECT 'BatchStatus', '批量改状态', 'system.admins.batch_status', 6
    UNION ALL SELECT 'Status', '切换状态', 'system.admins.status', 7
) AS src
WHERE @admin_parent_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `sa_menus` AS m
      WHERE m.`permission` = src.`permission`
        AND m.`deleted_at` IS NULL
  );

-- 为超级管理员角色同步新按钮权限（可选，按需执行）
INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'system.admins.query',
      'system.admins.create',
      'system.admins.update',
      'system.admins.delete',
      'system.admins.batch_delete',
      'system.admins.batch_status',
      'system.admins.status'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `sa_role_menu` AS rm
      WHERE rm.`role_id` = r.`id`
        AND rm.`menu_id` = m.`id`
  );
