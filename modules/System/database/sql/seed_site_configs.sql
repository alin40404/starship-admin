-- 系统配置默认项（已有库可执行，幂等）
-- 表前缀默认 sa_

UPDATE `sa_configs` SET `group_name` = 'basic', `updated_at` = NOW()
WHERE `key` = 'site.name' AND `group_name` = 'site';

INSERT INTO `sa_configs` (
    `group_name`, `name`, `key`, `value`, `type`, `sort`, `remark`, `created_at`, `updated_at`
)
SELECT src.`group_name`, src.`name`, src.`key`, src.`value`, src.`type`, src.`sort`, src.`remark`, NOW(), NOW()
FROM (
    SELECT 'basic' AS `group_name`, '系统名称' AS `name`, 'site.name' AS `key`, 'Starship Admin' AS `value`, 'string' AS `type`, 10 AS `sort`, '后台左上角及浏览器标题展示名称' AS `remark`
    UNION ALL SELECT 'basic', '系统 Logo', 'site.logo', '', 'string', 20, '建议尺寸 128×128，留空则使用默认图标'
    UNION ALL SELECT 'basic', '备案号', 'site.beian', '', 'string', 30, '网站 ICP 备案号，如：粤ICP备15000000号-1'
    UNION ALL SELECT 'basic', 'CDN 地址', 'site.cdn_url', '', 'string', 40, '全站静态资源使用第三方 CDN 时填写'
    UNION ALL SELECT 'basic', '版本号', 'site.version', '1.0.1', 'string', 50, '静态资源变更时可递增，用于缓存刷新'
    UNION ALL SELECT 'basic', '时区', 'site.timezone', 'Asia/Shanghai', 'string', 60, 'PHP 时区标识，如 Asia/Shanghai'
    UNION ALL SELECT 'basic', '禁止 IP', 'site.forbidden_ip', '', 'text', 70, '一行一条，匹配后将拒绝访问'
    UNION ALL SELECT 'basic', '默认主页', 'site.fixed_page', 'dashboard', 'string', 80, '登录后默认打开的菜单路由'
    UNION ALL SELECT 'sms', '启用短信', 'sms.enabled', '0', 'bool', 10, '关闭后系统将不发送短信通知'
    UNION ALL SELECT 'sms', '短信服务商', 'sms.provider', 'aliyun', 'select', 20, '选择已接入的短信平台'
    UNION ALL SELECT 'sms', 'AccessKey ID', 'sms.access_key', '', 'string', 30, '云厂商控制台获取的 AccessKey ID'
    UNION ALL SELECT 'sms', 'AccessKey Secret', 'sms.secret_key', '', 'string', 40, '云厂商控制台获取的 AccessKey Secret'
    UNION ALL SELECT 'sms', '短信签名', 'sms.sign_name', '', 'string', 50, '已在短信平台审核通过的签名名称'
    UNION ALL SELECT 'sms', '默认模板 ID', 'sms.template_code', '', 'string', 60, '验证码等场景的默认短信模板编号'
    UNION ALL SELECT 'sms', '接入地域', 'sms.region', 'cn-hangzhou', 'string', 70, '如 cn-hangzhou、ap-guangzhou'
    UNION ALL SELECT 'inbox', '启用站内信', 'inbox.enabled', '1', 'bool', 10, '关闭后不再写入新的站内消息'
    UNION ALL SELECT 'inbox', '消息保留天数', 'inbox.retention_days', '90', 'number', 20, '0 表示永久保留，过期消息将被清理'
    UNION ALL SELECT 'inbox', '列表每页条数', 'inbox.page_size', '20', 'number', 30, '消息中心列表默认分页大小'
    UNION ALL SELECT 'inbox', '系统消息通知', 'inbox.notify_system', '1', 'bool', 40, '是否允许发送系统类站内消息'
    UNION ALL SELECT 'inbox', '待办任务通知', 'inbox.notify_todo', '1', 'bool', 50, '是否允许发送待办类站内消息'
    UNION ALL SELECT 'inbox', '账户安全通知', 'inbox.notify_account', '1', 'bool', 60, '是否允许发送账户安全类站内消息'
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
    SELECT 'site.name' AS `key`, 'basic' AS `group_name`, '系统名称' AS `name`, '后台左上角及浏览器标题展示名称' AS `remark`, 10 AS `sort`
    UNION ALL SELECT 'site.logo', 'basic', '系统 Logo', '建议尺寸 128×128，留空则使用默认图标', 20
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
