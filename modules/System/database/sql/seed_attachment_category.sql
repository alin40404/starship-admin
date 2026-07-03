-- 附件分类字典（已有库可执行，幂等）
-- 表前缀默认 sa_

INSERT INTO `sa_dict_types` (`name`, `code`, `status`, `remark`, `created_at`, `updated_at`)
SELECT '附件分类', 'attachment_category', 1, '附件管理分类', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `sa_dict_types` AS t WHERE t.`code` = 'attachment_category' AND t.`deleted_at` IS NULL
);

SET @attachment_category_type_id := (
    SELECT `id` FROM `sa_dict_types`
    WHERE `code` = 'attachment_category' AND `deleted_at` IS NULL LIMIT 1
);

INSERT INTO `sa_dict_items` (`dict_type_id`, `label`, `value`, `sort`, `status`, `created_at`, `updated_at`)
SELECT @attachment_category_type_id, src.`label`, src.`value`, src.`sort`, 1, NOW(), NOW()
FROM (
    SELECT '图片' AS `label`, 'image' AS `value`, 10 AS `sort`
    UNION ALL SELECT '视频', 'video', 20
    UNION ALL SELECT '音频', 'audio', 25
    UNION ALL SELECT '文档', 'document', 30
    UNION ALL SELECT '资源', 'resources', 40
) AS src
WHERE @attachment_category_type_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `sa_dict_items` AS i
      WHERE i.`dict_type_id` = @attachment_category_type_id AND i.`value` = src.`value` AND i.`deleted_at` IS NULL
  );

UPDATE `sa_dict_items`
SET `status` = 0, `updated_at` = NOW()
WHERE `dict_type_id` = @attachment_category_type_id
  AND `value` IN ('default', 'avatar')
  AND `deleted_at` IS NULL;

UPDATE `sa_attachments`
SET `category` = 'image', `updated_at` = NOW()
WHERE `category` = 'avatar';

-- 历史 default / 空分类请在部署后执行 migrate，将按 MIME 自动重算

-- 附件表 category 字段（若曾使用 group_id 请手动迁移数据后删除 group_id）
SET @has_category := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sa_attachments'
      AND COLUMN_NAME = 'category'
);

SET @sql_add_category := IF(
    @has_category = 0,
    'ALTER TABLE `sa_attachments` ADD COLUMN `category` VARCHAR(50) NULL COMMENT ''附件分类，对应字典 attachment_category'' AFTER `storage`, ADD INDEX `sa_attachments_category_index` (`category`)',
    'SELECT 1'
);

PREPARE stmt FROM @sql_add_category;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 文件分组已合并至字典 attachment_category，移除独立菜单
UPDATE `sa_menus` SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `path` = '/resource/file-groups' AND `type` = 2 AND `deleted_at` IS NULL;

-- 清理旧版文件分组按钮权限（若存在）
UPDATE `sa_menus` SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `permission` IN (
    'system.file_groups.query',
    'system.file_groups.create',
    'system.file_groups.update',
    'system.file_groups.delete',
    'system.file_groups.batch_delete'
) AND `deleted_at` IS NULL;
