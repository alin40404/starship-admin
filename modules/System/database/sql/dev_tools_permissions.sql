-- 开发工具：代码生成 / 模块管理 按钮权限（已有环境增量执行）
-- 权限标识与 GeneratorPermissions、ModulePermissions 保持一致
-- 表前缀默认 sa_，若 DB_PREFIX 环境变量不同请自行替换

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

-- 代码生成（/dev/codegen）
SET @codegen_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/dev/codegen' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @codegen_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'generator.tables.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Import', '导入表', 'generator.tables.import', 2
    UNION ALL SELECT 'Update', '编辑配置', 'generator.tables.update', 3
    UNION ALL SELECT 'Delete', '删除配置', 'generator.tables.delete', 4
    UNION ALL SELECT 'Preview', '预览代码', 'generator.codegen.preview', 5
    UNION ALL SELECT 'Generate', '执行生成', 'generator.codegen.generate', 6
    UNION ALL SELECT 'RecordsQuery', '生成记录', 'generator.records.query', 7
) AS src
WHERE @codegen_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

-- 模块管理（/dev/modules）
SET @modules_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/dev/modules' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @modules_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.modules.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Update', '启用/禁用', 'system.modules.update', 2
) AS src
WHERE @modules_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

-- 超级管理员同步新按钮权限
INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'generator.tables.query', 'generator.tables.import', 'generator.tables.update',
      'generator.tables.delete', 'generator.codegen.preview', 'generator.codegen.generate',
      'generator.records.query',
      'system.modules.query', 'system.modules.update'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` rm
      WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );
