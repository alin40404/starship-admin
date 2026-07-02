-- 分类管理（FastAdmin category 模块）表结构与种子数据，已有库可执行，幂等
-- 表前缀默认 sa_

CREATE TABLE IF NOT EXISTS `sa_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '栏目类型，对应字典 category_type',
  `name` varchar(30) NOT NULL COMMENT '分类名称',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '分类别名',
  `flag` varchar(100) DEFAULT NULL COMMENT '标志: hot,index,recommend',
  `image` varchar(255) DEFAULT NULL COMMENT '图片',
  `keywords` varchar(255) DEFAULT NULL COMMENT '关键字',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `diyname` varchar(30) DEFAULT NULL COMMENT '自定义名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '权重排序',
  `status` varchar(30) NOT NULL DEFAULT 'normal' COMMENT '状态: normal,hidden',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sa_categories_sort_id_index` (`sort`,`id`),
  KEY `sa_categories_parent_id_index` (`parent_id`),
  KEY `sa_categories_type_index` (`type`),
  KEY `sa_categories_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类表';

INSERT INTO `sa_dict_types` (`name`, `code`, `status`, `remark`, `created_at`, `updated_at`)
SELECT '栏目类型', 'category_type', 1, '分类管理栏目类型，参考 FastAdmin categorytype', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `sa_dict_types` AS t WHERE t.`code` = 'category_type' AND t.`deleted_at` IS NULL
);

SET @category_type_id := (
    SELECT `id` FROM `sa_dict_types`
    WHERE `code` = 'category_type' AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_dict_items` (`dict_type_id`, `label`, `value`, `sort`, `status`, `created_at`, `updated_at`)
SELECT @category_type_id, src.`label`, src.`value`, src.`sort`, 1, NOW(), NOW()
FROM (
    SELECT '默认' AS `label`, 'default' AS `value`, 0 AS `sort`
    UNION ALL SELECT '单页', 'page', 10
    UNION ALL SELECT '文章', 'article', 20
    UNION ALL SELECT '测试', 'test', 30
) AS src
WHERE @category_type_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_dict_items` AS i
      WHERE i.`dict_type_id` = @category_type_id AND i.`value` = src.`value` AND i.`deleted_at` IS NULL
  );

INSERT INTO `sa_categories` (`id`, `parent_id`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `sort`, `status`, `created_at`, `updated_at`)
SELECT src.`id`, src.`parent_id`, src.`type`, src.`name`, src.`nickname`, src.`flag`, src.`image`, src.`keywords`, src.`description`, src.`diyname`, src.`sort`, 'normal', NOW(), NOW()
FROM (
    SELECT 1 AS `id`, 0 AS `parent_id`, 'page' AS `type`, '官方新闻' AS `name`, 'news' AS `nickname`, 'recommend' AS `flag`, '/assets/img/qrcode.png' AS `image`, '' AS `keywords`, '' AS `description`, 'news' AS `diyname`, 1 AS `sort`
    UNION ALL SELECT 2, 0, 'page', '移动应用', 'mobileapp', 'hot', '/assets/img/qrcode.png', '', '', 'mobileapp', 2
    UNION ALL SELECT 3, 2, 'page', '微信公众号', 'wechatpublic', 'index', '/assets/img/qrcode.png', '', '', 'wechatpublic', 3
    UNION ALL SELECT 4, 2, 'page', 'Android开发', 'android', 'recommend', '/assets/img/qrcode.png', '', '', 'android', 4
    UNION ALL SELECT 5, 0, 'page', '软件产品', 'software', 'recommend', '/assets/img/qrcode.png', '', '', 'software', 5
    UNION ALL SELECT 6, 5, 'page', '网站建站', 'website', 'recommend', '/assets/img/qrcode.png', '', '', 'website', 6
    UNION ALL SELECT 7, 5, 'page', '企业管理软件', 'company', 'index', '/assets/img/qrcode.png', '', '', 'company', 7
    UNION ALL SELECT 8, 6, 'page', 'PC端', 'website-pc', 'recommend', '/assets/img/qrcode.png', '', '', 'website-pc', 8
    UNION ALL SELECT 9, 6, 'page', '移动端', 'website-mobile', 'recommend', '/assets/img/qrcode.png', '', '', 'website-mobile', 9
    UNION ALL SELECT 10, 7, 'page', 'CRM系统', 'company-crm', 'recommend', '/assets/img/qrcode.png', '', '', 'company-crm', 10
    UNION ALL SELECT 11, 7, 'page', 'SASS平台软件', 'company-sass', 'recommend', '/assets/img/qrcode.png', '', '', 'company-sass', 11
    UNION ALL SELECT 12, 0, 'test', '测试1', 'test1', 'recommend', '/assets/img/qrcode.png', '', '', 'test1', 12
    UNION ALL SELECT 13, 0, 'test', '测试2', 'test2', 'recommend', '/assets/img/qrcode.png', '', '', 'test2', 13
) AS src
WHERE NOT EXISTS (SELECT 1 FROM `sa_categories` LIMIT 1);

-- 分类管理按钮权限（FastAdmin category 对齐）
SET @category_menu_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/platform/categories' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`, `type`, `name`, `title`, `path`, `component`, `permission`, `icon`, `sort`, `other_settings`, `status`, `created_at`, `updated_at`)
SELECT @category_menu_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}', 1, NOW(), NOW()
FROM (
    SELECT 'Append' AS `name`, '新增下级' AS `title`, 'system.categories.append' AS `permission`, 3 AS `sort`
    UNION ALL SELECT 'BatchDelete', '批量删除', 'system.categories.batch_delete', 6
    UNION ALL SELECT 'BatchStatus', '批量改状态', 'system.categories.batch_status', 7
    UNION ALL SELECT 'Status', '切换状态', 'system.categories.status', 8
    UNION ALL SELECT 'Sort', '调整排序', 'system.categories.sort', 9
) AS src
WHERE @category_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_menus` AS m
      WHERE m.`parent_id` = @category_menu_id AND m.`permission` = src.`permission` AND m.`deleted_at` IS NULL
  );

INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'system.categories.append',
      'system.categories.batch_delete',
      'system.categories.batch_status',
      'system.categories.status',
      'system.categories.sort'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` AS rm
      WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );
