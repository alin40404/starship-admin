-- 1. 删除「接口文档」占位菜单（/dev/api-docs）
-- 2. 将 Laravel 框架表重命名为带 sa_ 前缀（配合 DB_PREFIX=sa_）
--
-- 适用场景：早期 migrate 时 prefix 为空，业务表已有 sa_ 前缀，改用 Laravel DB_PREFIX 后框架表对不上。
-- 表前缀默认 sa_，若 DB_PREFIX 环境变量不同请自行替换表名。
-- 若某张表已改名或不存在，对应 RENAME 语句会报错，跳过即可。

-- ---------------------------------------------------------------------------
-- 1. 删除接口文档菜单
-- ---------------------------------------------------------------------------

SET @menu_id := (
    SELECT `id`
    FROM `sa_menus`
    WHERE `path` = '/dev/api-docs'
      AND `type` = 2
      AND `deleted_at` IS NULL
    LIMIT 1
);

DELETE rm
FROM `sa_role_menu` AS rm
WHERE @menu_id IS NOT NULL
  AND rm.`menu_id` = @menu_id;

UPDATE `sa_menus`
SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE @menu_id IS NOT NULL
  AND `id` = @menu_id
  AND `deleted_at` IS NULL;

-- ---------------------------------------------------------------------------
-- 2. Laravel 框架表加 sa_ 前缀
-- ---------------------------------------------------------------------------

RENAME TABLE `migrations` TO `sa_migrations`;
RENAME TABLE `personal_access_tokens` TO `sa_personal_access_tokens`;
RENAME TABLE `sessions` TO `sa_sessions`;
RENAME TABLE `cache` TO `sa_cache`;
RENAME TABLE `cache_locks` TO `sa_cache_locks`;
RENAME TABLE `jobs` TO `sa_jobs`;
RENAME TABLE `job_batches` TO `sa_job_batches`;
RENAME TABLE `failed_jobs` TO `sa_failed_jobs`;
RENAME TABLE `users` TO `sa_users`;
RENAME TABLE `password_reset_tokens` TO `sa_password_reset_tokens`;
