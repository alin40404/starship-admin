-- 附件管理移至平台设置（区域管理上方），并移除空的资源管理目录（已有库可执行，幂等）
-- 表前缀默认 sa_

SET @platform_parent_id := (
    SELECT `id` FROM `sa_menus`
    WHERE `path` = '/platform' AND `type` = 1 AND `deleted_at` IS NULL LIMIT 1
);

UPDATE `sa_menus`
SET `parent_id` = @platform_parent_id,
    `sort` = 303,
    `updated_at` = NOW()
WHERE `path` = '/resource/attachments'
  AND `type` = 2
  AND `deleted_at` IS NULL
  AND @platform_parent_id IS NOT NULL;

UPDATE `sa_menus`
SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `path` = '/resource'
  AND `type` = 1
  AND `deleted_at` IS NULL;
