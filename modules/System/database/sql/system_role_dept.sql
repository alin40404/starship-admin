-- 角色自定义数据范围关联表（data_scope = 5）
-- 表前缀默认 sa_，若 DB_PREFIX 环境变量 不同请自行替换
-- 适用：无法执行 migrate 时手动建表

CREATE TABLE IF NOT EXISTS `sa_role_dept` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
    `dept_id` bigint unsigned NOT NULL COMMENT '部门ID',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_role_dept` (`role_id`, `dept_id`),
    KEY `sa_role_dept_dept_id_index` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
