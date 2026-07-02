-- 运行参数默认项（group_name = param，已有库可执行，幂等）
-- 表前缀默认 sa_

INSERT INTO `sa_configs` (
    `group_name`, `name`, `key`, `value`, `type`, `sort`, `remark`, `created_at`, `updated_at`
)
SELECT src.`group_name`, src.`name`, src.`key`, src.`value`, src.`type`, src.`sort`, src.`remark`, NOW(), NOW()
FROM (
    SELECT 'param' AS `group_name`, '默认分页大小' AS `name`, 'app.page_size' AS `key`, '15' AS `value`, 'number' AS `type`, 10 AS `sort`, '后台列表默认每页条数' AS `remark`
    UNION ALL SELECT 'param', '接口超时时间', 'app.api.timeout', '30', 'number', 20, 'HTTP 接口请求超时时间（秒）'
    UNION ALL SELECT 'param', '登录有效期', 'app.jwt.ttl', '120', 'number', 30, '管理员 Token 有效时长（分钟）'
    UNION ALL SELECT 'param', '登录验证码', 'app.login.captcha', 'false', 'bool', 40, '是否在登录页启用图形验证码'
    UNION ALL SELECT 'param', '维护模式', 'app.maintenance', 'false', 'bool', 50, '开启后普通管理员无法访问系统'
) AS src
WHERE NOT EXISTS (
    SELECT 1 FROM `sa_configs` AS c WHERE c.`key` = src.`key`
);

UPDATE `sa_configs` AS c
INNER JOIN (
    SELECT 'app.page_size' AS `key`, 'param' AS `group_name`, '默认分页大小' AS `name`, '后台列表默认每页条数' AS `remark`, 10 AS `sort`, 'number' AS `type`
    UNION ALL SELECT 'app.api.timeout', 'param', '接口超时时间', 'HTTP 接口请求超时时间（秒）', 20, 'number'
    UNION ALL SELECT 'app.jwt.ttl', 'param', '登录有效期', '管理员 Token 有效时长（分钟）', 30, 'number'
    UNION ALL SELECT 'app.login.captcha', 'param', '登录验证码', '是否在登录页启用图形验证码', 40, 'bool'
    UNION ALL SELECT 'app.maintenance', 'param', '维护模式', '开启后普通管理员无法访问系统', 50, 'bool'
) AS meta ON meta.`key` = c.`key`
SET c.`group_name` = meta.`group_name`,
    c.`name` = meta.`name`,
    c.`remark` = meta.`remark`,
    c.`sort` = meta.`sort`,
    c.`type` = meta.`type`,
    c.`updated_at` = NOW();
