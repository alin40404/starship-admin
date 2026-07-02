-- 存储配置：新增腾讯云 COS / AWS S3 字段，并调整 OSS 排序（已有库可执行，幂等）
-- 表前缀默认 sa_

INSERT INTO `sa_configs` (
    `group_name`, `name`, `key`, `value`, `type`, `sort`, `remark`, `created_at`, `updated_at`
)
SELECT src.`group_name`, src.`name`, src.`key`, src.`value`, src.`type`, src.`sort`, src.`remark`, NOW(), NOW()
FROM (
    SELECT 'storage' AS `group_name`, 'COS SecretId' AS `name`, 'storage.cos.secret_id' AS `key`, '' AS `value`, 'string' AS `type`, 210 AS `sort`, '选择腾讯云 COS 驱动时填写' AS `remark`
    UNION ALL SELECT 'storage', 'COS SecretKey', 'storage.cos.secret_key', '', 'string', 220, '选择腾讯云 COS 驱动时填写'
    UNION ALL SELECT 'storage', 'COS Bucket', 'storage.cos.bucket', '', 'string', 230, '对象存储 Bucket 名称'
    UNION ALL SELECT 'storage', 'COS 地域', 'storage.cos.region', 'ap-guangzhou', 'string', 240, '如 ap-guangzhou、ap-shanghai'
    UNION ALL SELECT 'storage', 'COS Endpoint', 'storage.cos.endpoint', '', 'string', 250, '留空则按地域自动生成，如 cos.ap-guangzhou.myqcloud.com'
    UNION ALL SELECT 'storage', 'S3 Access Key', 'storage.s3.access_key', '', 'string', 310, '选择 AWS S3 或 S3 兼容存储时填写'
    UNION ALL SELECT 'storage', 'S3 Secret Key', 'storage.s3.secret_key', '', 'string', 320, '选择 AWS S3 或 S3 兼容存储时填写'
    UNION ALL SELECT 'storage', 'S3 Bucket', 'storage.s3.bucket', '', 'string', 330, '对象存储 Bucket 名称'
    UNION ALL SELECT 'storage', 'S3 地域', 'storage.s3.region', 'us-east-1', 'string', 340, 'AWS 区域标识，如 us-east-1、ap-southeast-1'
    UNION ALL SELECT 'storage', 'S3 Endpoint', 'storage.s3.endpoint', '', 'string', 350, 'AWS 可留空；MinIO 等 S3 兼容存储填写完整地址'
) AS src
WHERE NOT EXISTS (
    SELECT 1 FROM `sa_configs` AS c WHERE c.`key` = src.`key`
);

UPDATE `sa_configs` AS c
INNER JOIN (
    SELECT 'app.storage.driver' AS `key`, 'storage' AS `group_name`, '默认存储驱动' AS `name`, '附件上传默认使用的存储磁盘，公共存储可通过 /storage 访问' AS `remark`, 10 AS `sort`, 'select' AS `type`
    UNION ALL SELECT 'app.upload.max_size', 'storage', '上传大小上限', '单文件上传最大体积（MB）', 20, 'number'
    UNION ALL SELECT 'app.upload.allowed_ext', 'storage', '允许上传扩展名', 'JSON 数组，列出允许上传的文件扩展名', 30, 'json'
    UNION ALL SELECT 'storage.public_url', 'storage', '公共访问地址', '云存储驱动时使用；公共存储留空则使用 APP_URL/storage', 40, 'string'
    UNION ALL SELECT 'storage.oss.access_key', 'storage', 'OSS AccessKey ID', '选择阿里云 OSS 驱动时填写', 110, 'string'
    UNION ALL SELECT 'storage.oss.secret_key', 'storage', 'OSS AccessKey Secret', '选择阿里云 OSS 驱动时填写', 120, 'string'
    UNION ALL SELECT 'storage.oss.bucket', 'storage', 'OSS Bucket', '对象存储 Bucket 名称', 130, 'string'
    UNION ALL SELECT 'storage.oss.endpoint', 'storage', 'OSS Endpoint', '如 oss-cn-hangzhou.aliyuncs.com，留空则按地域自动生成', 140, 'string'
    UNION ALL SELECT 'storage.oss.region', 'storage', 'OSS 地域', '如 cn-hangzhou、cn-beijing', 150, 'string'
    UNION ALL SELECT 'storage.cos.secret_id', 'storage', 'COS SecretId', '选择腾讯云 COS 驱动时填写', 210, 'string'
    UNION ALL SELECT 'storage.cos.secret_key', 'storage', 'COS SecretKey', '选择腾讯云 COS 驱动时填写', 220, 'string'
    UNION ALL SELECT 'storage.cos.bucket', 'storage', 'COS Bucket', '对象存储 Bucket 名称', 230, 'string'
    UNION ALL SELECT 'storage.cos.region', 'storage', 'COS 地域', '如 ap-guangzhou、ap-shanghai', 240, 'string'
    UNION ALL SELECT 'storage.cos.endpoint', 'storage', 'COS Endpoint', '留空则按地域自动生成，如 cos.ap-guangzhou.myqcloud.com', 250, 'string'
    UNION ALL SELECT 'storage.s3.access_key', 'storage', 'S3 Access Key', '选择 AWS S3 或 S3 兼容存储时填写', 310, 'string'
    UNION ALL SELECT 'storage.s3.secret_key', 'storage', 'S3 Secret Key', '选择 AWS S3 或 S3 兼容存储时填写', 320, 'string'
    UNION ALL SELECT 'storage.s3.bucket', 'storage', 'S3 Bucket', '对象存储 Bucket 名称', 330, 'string'
    UNION ALL SELECT 'storage.s3.region', 'storage', 'S3 地域', 'AWS 区域标识，如 us-east-1、ap-southeast-1', 340, 'string'
    UNION ALL SELECT 'storage.s3.endpoint', 'storage', 'S3 Endpoint', 'AWS 可留空；MinIO 等 S3 兼容存储填写完整地址', 350, 'string'
) AS meta ON meta.`key` = c.`key`
SET c.`group_name` = meta.`group_name`,
    c.`name` = meta.`name`,
    c.`remark` = meta.`remark`,
    c.`sort` = meta.`sort`,
    c.`type` = meta.`type`,
    c.`updated_at` = NOW();
