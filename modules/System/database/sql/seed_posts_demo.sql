-- 岗位管理演示数据（已有库可执行，幂等）
-- 表前缀默认 sa_，若 config('starship.table_prefix') 不同请自行替换

INSERT INTO `sa_posts` (`name`, `code`, `sort`, `status`, `remark`, `created_at`, `updated_at`)
SELECT src.`name`, src.`code`, src.`sort`, src.`status`, src.`remark`, NOW(), NOW()
FROM (
    SELECT '总经理' AS `name`, 'ceo' AS `code`, 0 AS `sort`, 1 AS `status`, '公司最高管理者' AS `remark`
    UNION ALL SELECT '研发总监', 'rd_director', 10, 1, '研发中心负责人'
    UNION ALL SELECT '前端工程师', 'frontend_engineer', 11, 1, '对应前端组'
    UNION ALL SELECT '后端工程师', 'backend_engineer', 12, 1, '对应后端组'
    UNION ALL SELECT '测试工程师', 'qa_engineer', 13, 1, '对应测试组'
    UNION ALL SELECT '市场经理', 'marketing_manager', 20, 1, '市场营销部'
    UNION ALL SELECT '销售专员', 'sales_specialist', 21, 1, NULL
    UNION ALL SELECT '人事专员', 'hr_specialist', 30, 1, '人力资源部'
    UNION ALL SELECT '财务专员', 'finance_specialist', 40, 0, '示例：禁用状态'
) AS src
WHERE NOT EXISTS (
    SELECT 1 FROM `sa_posts` AS p
    WHERE p.`code` = src.`code` AND p.`deleted_at` IS NULL
);

UPDATE `sa_posts` AS p
INNER JOIN (
    SELECT 'ceo' AS `code`, '总经理' AS `name`, 0 AS `sort`, 1 AS `status`, '公司最高管理者' AS `remark`
    UNION ALL SELECT 'rd_director', '研发总监', 10, 1, '研发中心负责人'
    UNION ALL SELECT 'frontend_engineer', '前端工程师', 11, 1, '对应前端组'
    UNION ALL SELECT 'backend_engineer', '后端工程师', 12, 1, '对应后端组'
    UNION ALL SELECT 'qa_engineer', '测试工程师', 13, 1, '对应测试组'
    UNION ALL SELECT 'marketing_manager', '市场经理', 20, 1, '市场营销部'
    UNION ALL SELECT 'sales_specialist', '销售专员', 21, 1, NULL
    UNION ALL SELECT 'hr_specialist', '人事专员', 30, 1, '人力资源部'
    UNION ALL SELECT 'finance_specialist', '财务专员', 40, 0, '示例：禁用状态'
) AS src ON p.`code` = src.`code` AND p.`deleted_at` IS NULL
SET p.`name` = src.`name`,
    p.`sort` = src.`sort`,
    p.`status` = src.`status`,
    p.`remark` = src.`remark`,
    p.`updated_at` = NOW();
