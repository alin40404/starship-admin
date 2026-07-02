-- 分类管理菜单（/platform/categories，平台设置 → 附件管理下方，已有库可执行，幂等）
-- 表前缀默认 sa_

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

SET @platform_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/platform' AND `type` = 1 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (
    `parent_id`, `type`, `name`, `title`, `path`, `component`, `permission`, `icon`, `sort`,
    `other_settings`, `status`, `created_at`, `updated_at`
)
SELECT @platform_parent_id, 2, 'CategoryMgmt', '分类管理', '/platform/categories',
       '/platform/category/index', 'system.categories', 'ep:collection-tag', 304,
       '{"hide_menu":0,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":1}', 1, NOW(), NOW()
WHERE @platform_parent_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_menus` m
      WHERE m.`path` = '/platform/categories' AND m.`type` = 2 AND m.`deleted_at` IS NULL
  );

UPDATE `sa_menus`
SET `parent_id` = @platform_parent_id,
    `sort` = 304,
    `path` = '/platform/categories',
    `component` = '/platform/category/index',
    `name` = 'CategoryMgmt',
    `title` = '分类管理',
    `permission` = 'system.categories',
    `icon` = 'ep:collection-tag',
    `updated_at` = NOW()
WHERE `path` IN ('/platform/attachment-categories', '/platform/categories')
  AND `type` = 2
  AND `deleted_at` IS NULL
  AND @platform_parent_id IS NOT NULL;

UPDATE `sa_menus`
SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `path` = '/platform/attachment-categories'
  AND `type` = 2
  AND `deleted_at` IS NULL;

UPDATE `sa_menus`
SET `sort` = 305, `updated_at` = NOW()
WHERE `path` = '/platform/regions' AND `type` = 2 AND `deleted_at` IS NULL;

SET @category_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/platform/categories' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @category_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.categories.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Create', '新增', 'system.categories.create', 2
    UNION ALL SELECT 'Update', '编辑', 'system.categories.update', 3
    UNION ALL SELECT 'Delete', '删除', 'system.categories.delete', 4
) AS src
WHERE @category_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

UPDATE `sa_menus`
SET `permission` = REPLACE(`permission`, 'system.attachment_categories', 'system.categories'),
    `updated_at` = NOW()
WHERE `permission` LIKE 'system.attachment_categories%'
  AND `deleted_at` IS NULL;

INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'system.categories',
      'system.categories.query',
      'system.categories.create',
      'system.categories.update',
      'system.categories.delete'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` rm
      WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );
