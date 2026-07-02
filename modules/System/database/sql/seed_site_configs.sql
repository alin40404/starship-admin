-- 系统配置默认项（参考 FastAdmin / BuildAdmin，已有库可执行，幂等）
-- 表前缀默认 sa_

UPDATE `sa_configs` SET `group_name` = 'basic', `updated_at` = NOW()
WHERE `key` = 'site.name' AND `group_name` = 'site';

INSERT INTO `sa_configs` (
    `group_name`, `name`, `key`, `value`, `type`, `sort`, `remark`, `created_at`, `updated_at`
)
SELECT src.`group_name`, src.`name`, src.`key`, src.`value`, src.`type`, src.`sort`, src.`remark`, NOW(), NOW()
FROM (
    SELECT 'basic' AS `group_name`, '站点名称' AS `name`, 'site.name' AS `key`, 'Starship Admin' AS `value`, 'string' AS `type`, 10 AS `sort`, '后台左上角及浏览器标题展示名称' AS `remark`
    UNION ALL SELECT 'basic', '站点 Logo', 'site.logo', '', 'string', 20, '建议尺寸 128×128，留空则使用默认图标'
    UNION ALL SELECT 'basic', '备案号', 'site.beian', '', 'string', 30, '网站 ICP 备案号，如：粤ICP备15000000号-1'
    UNION ALL SELECT 'basic', 'CDN 地址', 'site.cdn_url', '', 'string', 40, '全站静态资源使用第三方 CDN 时填写'
    UNION ALL SELECT 'basic', '版本号', 'site.version', '1.0.1', 'string', 50, '静态资源变更时可递增，用于缓存刷新'
    UNION ALL SELECT 'basic', '时区', 'site.timezone', 'Asia/Shanghai', 'string', 60, 'PHP 时区标识，如 Asia/Shanghai'
    UNION ALL SELECT 'basic', '禁止 IP', 'site.forbidden_ip', '', 'text', 70, '一行一条，匹配后将拒绝访问'
    UNION ALL SELECT 'basic', '默认主页', 'site.fixed_page', 'dashboard', 'string', 80, '登录后默认打开的菜单路由'
    UNION ALL SELECT 'email', '邮件发送方式', 'mail.type', '1', 'select', 10, '目前支持 SMTP'
    UNION ALL SELECT 'email', 'SMTP 服务器', 'mail.smtp_host', 'smtp.qq.com', 'string', 20, '错误的配置可能导致邮件发送超时'
    UNION ALL SELECT 'email', 'SMTP 端口', 'mail.smtp_port', '465', 'string', 30, '不加密默认 25，SSL 默认 465，TLS 默认 587'
    UNION ALL SELECT 'email', 'SMTP 用户名', 'mail.smtp_user', '', 'string', 40, '填写完整邮箱账号'
    UNION ALL SELECT 'email', 'SMTP 密码', 'mail.smtp_pass', '', 'string', 50, '邮箱密码或 SMTP 授权码'
    UNION ALL SELECT 'email', 'SMTP 验证方式', 'mail.verify_type', '2', 'select', 60, '推荐使用 SSL'
    UNION ALL SELECT 'email', '发件人邮箱', 'mail.from', '', 'string', 70, '系统邮件的发件地址'
) AS src
WHERE NOT EXISTS (
    SELECT 1 FROM `sa_configs` AS c WHERE c.`key` = src.`key`
);

UPDATE `sa_configs` AS c
INNER JOIN (
    SELECT 'site.name' AS `key`, 'basic' AS `group_name`, '站点名称' AS `name`, '后台左上角及浏览器标题展示名称' AS `remark`, 10 AS `sort`
    UNION ALL SELECT 'site.logo', 'basic', '站点 Logo', '建议尺寸 128×128，留空则使用默认图标', 20
    UNION ALL SELECT 'site.beian', 'basic', '备案号', '网站 ICP 备案号，如：粤ICP备15000000号-1', 30
    UNION ALL SELECT 'site.cdn_url', 'basic', 'CDN 地址', '全站静态资源使用第三方 CDN 时填写', 40
    UNION ALL SELECT 'site.version', 'basic', '版本号', '静态资源变更时可递增，用于缓存刷新', 50
    UNION ALL SELECT 'site.timezone', 'basic', '时区', 'PHP 时区标识，如 Asia/Shanghai', 60
    UNION ALL SELECT 'site.forbidden_ip', 'basic', '禁止 IP', '一行一条，匹配后将拒绝访问', 70
    UNION ALL SELECT 'site.fixed_page', 'basic', '默认主页', '登录后默认打开的菜单路由', 80
) AS meta ON meta.`key` = c.`key`
SET c.`group_name` = meta.`group_name`,
    c.`name` = meta.`name`,
    c.`remark` = meta.`remark`,
    c.`sort` = meta.`sort`,
    c.`updated_at` = NOW();
