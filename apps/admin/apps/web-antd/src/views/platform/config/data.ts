/** 站点配置分组（参考 FastAdmin basic / email 分组） */
export const CONFIG_GROUP_LABELS: Record<string, string> = {
  basic: '基础配置',
  email: '邮件配置',
};

export const CONFIG_GROUP_DESCRIPTIONS: Record<string, string> = {
  basic: '系统名称、版本号、时区等基础信息',
  email: 'SMTP 邮件发送相关配置',
};

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
};

export const PARAM_TYPE_TAG_OPTIONS = [
  { color: 'default', label: '字符串', value: 'string' },
  { color: 'processing', label: '数字', value: 'number' },
  { color: 'warning', label: '布尔', value: 'bool' },
  { color: 'success', label: 'JSON', value: 'json' },
] as const;
