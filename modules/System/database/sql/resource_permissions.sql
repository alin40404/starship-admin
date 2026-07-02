-- 资源管理菜单页面组件与按钮权限（已有库可执行，幂等）
-- 表前缀默认 sa_

UPDATE `sa_menus` SET `component` = '/resource/attachment/index', `updated_at` = NOW()
WHERE `path` = '/resource/attachments' AND `type` = 2 AND `deleted_at` IS NULL;

SET @btn_other_settings := '{"hide_menu":1,"hide_submenu":0,"hide_in_breadcrumb":0,"hide_in_tab":0,"affix_tab":0,"keep_alive":0}';

SET @attachment_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/resource/attachments' AND `type` = 2 AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_menus` (`parent_id`,`type`,`name`,`title`,`path`,`component`,`permission`,`icon`,`sort`,`other_settings`,`status`,`created_at`,`updated_at`)
SELECT @attachment_parent_id, 3, src.`name`, src.`title`, NULL, NULL, src.`permission`, NULL, src.`sort`, @btn_other_settings, 1, NOW(), NOW()
FROM (
    SELECT 'Query' AS `name`, '查询' AS `title`, 'system.attachments.query' AS `permission`, 1 AS `sort`
    UNION ALL SELECT 'Upload', '上传附件', 'system.attachments.upload', 2
    UNION ALL SELECT 'Delete', '删除', 'system.attachments.delete', 3
    UNION ALL SELECT 'BatchDelete', '批量删除', 'system.attachments.batch_delete', 4
) AS src
WHERE @attachment_parent_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `sa_menus` m WHERE m.`permission` = src.`permission` AND m.`deleted_at` IS NULL);

INSERT INTO `sa_role_menu` (`role_id`, `menu_id`)
SELECT r.`id`, m.`id`
FROM `sa_roles` AS r
CROSS JOIN `sa_menus` AS m
WHERE r.`code` = 'super_admin'
  AND m.`permission` IN (
      'system.attachments.query',
      'system.attachments.upload',
      'system.attachments.delete',
      'system.attachments.batch_delete'
  )
  AND m.`deleted_at` IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_role_menu` rm
      WHERE rm.`role_id` = r.`id` AND rm.`menu_id` = m.`id`
  );

-- 文件分组已合并至字典 attachment_category，移除独立菜单
UPDATE `sa_menus` SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `path` = '/resource/file-groups' AND `type` = 2 AND `deleted_at` IS NULL;

-- 存储配置已合并至平台设置 / 系统配置，移除独立菜单
UPDATE `sa_menus` SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `path` = '/resource/storage' AND `type` = 2 AND `deleted_at` IS NULL;
