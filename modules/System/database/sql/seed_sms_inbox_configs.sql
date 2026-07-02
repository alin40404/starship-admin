-- 短信 / 站内信配置默认项（已有库可执行，幂等）
-- 表前缀默认 sa_

INSERT INTO `sa_configs` (
    `group_name`, `name`, `key`, `value`, `type`, `sort`, `remark`, `created_at`, `updated_at`
)
SELECT src.`group_name`, src.`name`, src.`key`, src.`value`, src.`type`, src.`sort`, src.`remark`, NOW(), NOW()
FROM (
    SELECT 'sms' AS `group_name`, '启用短信' AS `name`, 'sms.enabled' AS `key`, '0' AS `value`, 'bool' AS `type`, 10 AS `sort`, '关闭后系统将不发送短信通知' AS `remark`
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
) AS src
WHERE NOT EXISTS (
    SELECT 1 FROM `sa_configs` AS c WHERE c.`key` = src.`key`
);
