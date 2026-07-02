/** 站点配置分组（参考 FastAdmin basic / email 分组） */
export const CONFIG_GROUP_LABELS: Record<string, string> = {
  basic: '基础配置',
  sms: '短信配置',
  inbox: '站内信配置',
  email: '邮件配置',
};

export const CONFIG_GROUP_DESCRIPTIONS: Record<string, string> = {
  basic: '系统名称、版本号、时区等基础信息',
  sms: '短信网关接入与签名模板等发送参数',
  inbox: '站内消息开关、保留策略与通知类型',
  email: 'SMTP 邮件发送相关配置',
};

/** 表单类配置分组（非运行参数） */
export const CONFIG_SETTINGS_GROUPS = [
  'basic',
  'sms',
  'inbox',
  'email',
] as const;

export type ConfigSettingsGroup = (typeof CONFIG_SETTINGS_GROUPS)[number];

/** 下拉类配置项选项（参考 FastAdmin mail_type / mail_verify_type） */
export const CONFIG_SELECT_OPTIONS: Record<
  string,
  { label: string; value: string }[]
> = {
  'mail.type': [{ label: 'SMTP', value: '1' }],
  'mail.verify_type': [
    { label: '无', value: '0' },
    { label: 'TLS', value: '1' },
    { label: 'SSL', value: '2' },
  ],
  'sms.provider': [
    { label: '阿里云短信', value: 'aliyun' },
    { label: '腾讯云短信', value: 'tencent' },
    { label: '华为云短信', value: 'huawei' },
  ],
};

export const PARAM_TYPE_TAG_OPTIONS = [
  { color: 'default', label: '字符串', value: 'string' },
  { color: 'processing', label: '数字', value: 'number' },
  { color: 'warning', label: '布尔', value: 'bool' },
  { color: 'success', label: 'JSON', value: 'json' },
] as const;
