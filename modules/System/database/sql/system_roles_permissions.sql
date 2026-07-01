-- 角色管理（/permission/roles）按钮权限
-- 权限标识与 modules/System/app/Support/RolePermissions.php 保持一致
-- 表前缀默认 sa_，若 config('starship.table_prefix') 不同请自行替换
-- 适用：已初始化库、System 模块 Seeder 已跑过但未包含角色按钮权限时执行

SET @role_parent_id := (
    SELECT `id`
    FROM `sa_menus`
    WHERE `path` = '/permission/roles'
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
    @role_parent_id,
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
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.roles.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Create', '新增角色', 'system.roles.create', 2
    UNION ALL SELECT 'Update', '编辑', 'system.roles.update', 3
    UNION ALL SELECT 'Delete', '删除', 'system.roles.delete', 4
    UNION ALL SELECT 'BatchDelete', '批量删除', 'system.roles.batch_delete', 5
    UNION ALL SELECT 'BatchEnable', '批量启用', 'system.roles.batch_enable', 6
    UNION ALL SELECT 'BatchDisable', '批量禁用', 'system.roles.batch_disable', 7
) AS src
WHERE @role_parent_id IS NOT NULL
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
      'system.roles.query',
      'system.roles.create',
      'system.roles.update',
      'system.roles.delete',
      'system.roles.batch_delete',
      'system.roles.batch_enable',
      'system.roles.batch_disable'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `sa_role_menu` AS rm
      WHERE rm.`role_id` = r.`id`
        AND rm.`menu_id` = m.`id`
  );
