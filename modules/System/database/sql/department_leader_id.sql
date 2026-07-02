-- 部门负责人改为关联管理员（已有库可执行，幂等）
-- 执行前请确认已存在 sa_admins 表；表前缀默认 sa_

SET @has_leader_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sa_departments'
      AND COLUMN_NAME = 'leader'
);

SET @sql_add_leader_id := IF(
    @has_leader_col > 0
    AND (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'sa_departments'
           AND COLUMN_NAME = 'leader_id') = 0,
    'ALTER TABLE `sa_departments` ADD COLUMN `leader_id` BIGINT UNSIGNED NULL COMMENT ''部门负责人ID'' AFTER `name`',
    'SELECT 1'
);
PREPARE stmt FROM @sql_add_leader_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql_drop_leader := IF(
    @has_leader_col > 0,
    'ALTER TABLE `sa_departments` DROP COLUMN `leader`',
    'SELECT 1'
);
PREPARE stmt FROM @sql_drop_leader;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_fk := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sa_departments'
      AND CONSTRAINT_NAME = 'sa_departments_leader_id_foreign'
);

SET @sql_add_fk := IF(
    @has_fk = 0
    AND (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'sa_departments'
           AND COLUMN_NAME = 'leader_id') > 0,
    'ALTER TABLE `sa_departments` ADD CONSTRAINT `sa_departments_leader_id_foreign` FOREIGN KEY (`leader_id`) REFERENCES `sa_admins` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql_add_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 总公司负责人默认关联 admin 账号
UPDATE `sa_departments` AS d
INNER JOIN `sa_admins` AS a ON a.`username` = 'admin' AND a.`deleted_at` IS NULL
SET d.`leader_id` = a.`id`
WHERE d.`parent_id` = 0
  AND d.`name` = '总公司'
  AND d.`deleted_at` IS NULL
  AND d.`leader_id` IS NULL;
