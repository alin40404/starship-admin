/** 站点配置分组（basic / email 分组） */
export const CONFIG_GROUP_LABELS: Record<string, string> = {
  basic: '基础配置',
  sms: '短信配置',
  inbox: '站内信配置',
  email: '邮件配置',
  storage: '存储配置',
};

export const CONFIG_GROUP_DESCRIPTIONS: Record<string, string> = {
  basic: '系统名称、版本号、时区等基础信息',
  sms: '短信网关接入与签名模板等发送参数',
  inbox: '站内消息开关、保留策略与通知类型',
  email: 'SMTP 邮件发送相关配置',
  storage: '附件存储驱动、上传限制与云存储接入参数',
};

/** 表单类配置分组（非运行参数） */
export const CONFIG_SETTINGS_GROUPS = [
  'basic',
  'sms',
  'inbox',
  'email',
  'storage',
] as const;

export type ConfigSettingsGroup = (typeof CONFIG_SETTINGS_GROUPS)[number];

/** 存储配置：默认驱动字段 key */
export const STORAGE_DRIVER_KEY = 'app.storage.driver';

/** 存储配置：各驱动始终显示的字段 */
export const STORAGE_COMMON_KEYS = [
  STORAGE_DRIVER_KEY,
  'app.upload.max_size',
  'app.upload.allowed_ext',
] as const;

/** 存储配置：云驱动共用的公共访问地址 */
export const STORAGE_CLOUD_COMMON_KEYS = ['storage.public_url'] as const;

/** 存储配置：云驱动标识 */
export const STORAGE_CLOUD_DRIVERS = ['oss', 'cos', 's3'] as const;

export type StorageCloudDriver = (typeof STORAGE_CLOUD_DRIVERS)[number];

/** 按驱动决定存储配置项是否展示（仿短信 provider 切换） */
export function isStorageFieldVisible(key: string, driver: string): boolean {
  if ((STORAGE_COMMON_KEYS as readonly string[]).includes(key)) {
    return true;
  }

  if ((STORAGE_CLOUD_COMMON_KEYS as readonly string[]).includes(key)) {
    return (STORAGE_CLOUD_DRIVERS as readonly string[]).includes(driver);
  }

  if (key.startsWith('storage.oss.')) {
    return driver === 'oss';
  }

  if (key.startsWith('storage.cos.')) {
    return driver === 'cos';
  }

  if (key.startsWith('storage.s3.')) {
    return driver === 's3';
  }

  return true;
}

/** 使用附件图片上传组件的配置项 key */
export const CONFIG_ATTACHMENT_IMAGE_KEYS = ['site.logo'] as const;

export function isAttachmentImageField(key: string): boolean {
  return (CONFIG_ATTACHMENT_IMAGE_KEYS as readonly string[]).includes(key);
}

/** 下拉类配置项选项（mail_type / mail_verify_type） */
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
  [STORAGE_DRIVER_KEY]: [
    { label: '公共存储', value: 'public' },
    { label: '本地存储', value: 'local' },
    { label: '阿里云 OSS', value: 'oss' },
    { label: '腾讯云 COS', value: 'cos' },
    { label: 'AWS S3', value: 's3' },
  ],
};

export const PARAM_TYPE_TAG_OPTIONS = [
  { color: 'default', label: '字符串', value: 'string' },
  { color: 'processing', label: '数字', value: 'number' },
  { color: 'warning', label: '布尔', value: 'bool' },
  { color: 'success', label: 'JSON', value: 'json' },
] as const;
