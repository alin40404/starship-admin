-- 存储配置默认项（合并至系统配置 /platform/configs，已有库可执行，幂等）
-- 表前缀默认 sa_

INSERT INTO `sa_configs` (
    `group_name`, `name`, `key`, `value`, `type`, `sort`, `remark`, `created_at`, `updated_at`
)
SELECT src.`group_name`, src.`name`, src.`key`, src.`value`, src.`type`, src.`sort`, src.`remark`, NOW(), NOW()
FROM (
    SELECT 'storage' AS `group_name`, '默认存储驱动' AS `name`, 'app.storage.driver' AS `key`, 'public' AS `value`, 'select' AS `type`, 10 AS `sort`, '附件上传默认使用的存储磁盘，公共存储可通过 /storage 访问' AS `remark`
    UNION ALL SELECT 'storage', '上传大小上限', 'app.upload.max_size', '20', 'number', 20, '单文件上传最大体积（MB）'
    UNION ALL SELECT 'storage', '允许上传扩展名', 'app.upload.allowed_ext', '["jpg","jpeg","png","gif","webp","pdf","doc","docx","xls","xlsx","zip"]', 'json', 30, 'JSON 数组，列出允许上传的文件扩展名'
    UNION ALL SELECT 'storage', '公共访问地址', 'storage.public_url', '', 'string', 40, '附件外链前缀，留空则使用 APP_URL/storage；可填 CDN 或独立静态域名'
    UNION ALL SELECT 'storage', 'OSS AccessKey ID', 'storage.oss.access_key', '', 'string', 50, '选择阿里云 OSS 驱动时填写'
    UNION ALL SELECT 'storage', 'OSS AccessKey Secret', 'storage.oss.secret_key', '', 'string', 60, '选择阿里云 OSS 驱动时填写'
    UNION ALL SELECT 'storage', 'OSS Bucket', 'storage.oss.bucket', '', 'string', 70, '对象存储 Bucket 名称'
    UNION ALL SELECT 'storage', 'OSS Endpoint', 'storage.oss.endpoint', '', 'string', 80, '如 oss-cn-hangzhou.aliyuncs.com'
    UNION ALL SELECT 'storage', 'OSS 地域', 'storage.oss.region', 'cn-hangzhou', 'string', 90, '如 cn-hangzhou、cn-beijing'
) AS src
WHERE NOT EXISTS (
    SELECT 1 FROM `sa_configs` AS c WHERE c.`key` = src.`key`
);

-- 将原运行参数中的存储项迁移至 storage 分组
UPDATE `sa_configs` AS c
INNER JOIN (
    SELECT 'app.storage.driver' AS `key`, 'storage' AS `group_name`, '默认存储驱动' AS `name`, '附件上传默认使用的存储磁盘，公共存储可通过 /storage 访问' AS `remark`, 10 AS `sort`, 'select' AS `type`
    UNION ALL SELECT 'app.upload.max_size', 'storage', '上传大小上限', '单文件上传最大体积（MB）', 20, 'number'
    UNION ALL SELECT 'app.upload.allowed_ext', 'storage', '允许上传扩展名', 'JSON 数组，列出允许上传的文件扩展名', 30, 'json'
    UNION ALL SELECT 'storage.public_url', 'storage', '公共访问地址', '附件外链前缀，留空则使用 APP_URL/storage；可填 CDN 或独立静态域名', 40, 'string'
    UNION ALL SELECT 'storage.oss.access_key', 'storage', 'OSS AccessKey ID', '选择阿里云 OSS 驱动时填写', 50, 'string'
    UNION ALL SELECT 'storage.oss.secret_key', 'storage', 'OSS AccessKey Secret', '选择阿里云 OSS 驱动时填写', 60, 'string'
    UNION ALL SELECT 'storage.oss.bucket', 'storage', 'OSS Bucket', '对象存储 Bucket 名称', 70, 'string'
    UNION ALL SELECT 'storage.oss.endpoint', 'storage', 'OSS Endpoint', '如 oss-cn-hangzhou.aliyuncs.com', 80, 'string'
    UNION ALL SELECT 'storage.oss.region', 'storage', 'OSS 地域', '如 cn-hangzhou、cn-beijing', 90, 'string'
) AS meta ON meta.`key` = c.`key`
SET c.`group_name` = meta.`group_name`,
    c.`name` = meta.`name`,
    c.`remark` = meta.`remark`,
    c.`sort` = meta.`sort`,
    c.`type` = meta.`type`,
    c.`updated_at` = NOW();

-- 默认存储驱动统一为公共存储（修正历史运行参数遗留的 local）
UPDATE `sa_configs`
SET `value` = 'public', `updated_at` = NOW()
WHERE `key` = 'app.storage.driver' AND (`value` IS NULL OR `value` = '' OR `value` = 'local');

-- 存储配置已合并至平台设置 / 系统配置，移除资源管理下的独立菜单
UPDATE `sa_menus` SET `deleted_at` = NOW(), `updated_at` = NOW()
WHERE `path` = '/resource/storage' AND `type` = 2 AND `deleted_at` IS NULL;
