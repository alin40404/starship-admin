-- 组织架构（部门 / 岗位 / 用户组）按钮权限
-- 权限标识与 modules/System/app/Support/*Permissions.php 保持一致
-- 表前缀默认 sa_，若 config('starship.table_prefix') 不同请自行替换

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

-- 部门管理
SET @dept_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/org/departments' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @dept_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.departments.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Create', '新增部门', 'system.departments.create', 2
    UNION ALL SELECT 'Append', '新增下级', 'system.departments.append', 3
    UNION ALL SELECT 'Update', '编辑', 'system.departments.update', 4
    UNION ALL SELECT 'Delete', '删除', 'system.departments.delete', 5
    UNION ALL SELECT 'Status', '切换状态', 'system.departments.status', 6
) AS src
WHERE @dept_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

-- 岗位管理
SET @post_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/org/posts' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @post_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.posts.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Create', '新增岗位', 'system.posts.create', 2
    UNION ALL SELECT 'Update', '编辑', 'system.posts.update', 3
    UNION ALL SELECT 'Delete', '删除', 'system.posts.delete', 4
    UNION ALL SELECT 'BatchDelete', '批量删除', 'system.posts.batch_delete', 5
    UNION ALL SELECT 'BatchStatus', '批量改状态', 'system.posts.batch_status', 6
) AS src
WHERE @post_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

-- 用户组管理
SET @group_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/org/user-groups' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @group_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.user_groups.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Create', '新增用户组', 'system.user_groups.create', 2
    UNION ALL SELECT 'Update', '编辑', 'system.user_groups.update', 3
    UNION ALL SELECT 'Delete', '删除', 'system.user_groups.delete', 4
    UNION ALL SELECT 'BatchDelete', '批量删除', 'system.user_groups.batch_delete', 5
) AS src
WHERE @group_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

-- 超级管理员同步新按钮权限
INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'system.departments.query','system.departments.create','system.departments.append',
      'system.departments.update','system.departments.delete','system.departments.status',
      'system.posts.query','system.posts.create','system.posts.update','system.posts.delete',
      'system.posts.batch_delete','system.posts.batch_status',
      'system.user_groups.query','system.user_groups.create','system.user_groups.update',
      'system.user_groups.delete','system.user_groups.batch_delete'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` rm WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );

-- 更新菜单 component 指向实际页面（已有库）
UPDATE `sa_menus` SET `component` = '/org/department/index', `updated_at` = NOW()
WHERE `path` = '/org/departments' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `component` = '/org/post/index', `updated_at` = NOW()
WHERE `path` = '/org/posts' AND `type` = 2 AND `deleted_at` IS NULL;

UPDATE `sa_menus` SET `component` = '/org/user-group/index', `updated_at` = NOW()
WHERE `path` = '/org/user-groups' AND `type` = 2 AND `deleted_at` IS NULL;
