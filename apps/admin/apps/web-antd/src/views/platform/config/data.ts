/** 站点配置分组（basic / email 分组） */
import type { ConfigRow } from '#/api/system/config';
import type { MenuTreeNode } from '#/api/system/menu';

import { $te } from '@vben/locales';

import { $t } from '#/locales';
import { menuDisplayLabel } from '#/views/permission/menu/data';

export function getConfigGroupLabels(): Record<string, string> {
  return {
    basic: $t('platform.config.groups.basic'),
    sms: $t('platform.config.groups.sms'),
    inbox: $t('platform.config.groups.inbox'),
    email: $t('platform.config.groups.email'),
    storage: $t('platform.config.groups.storage'),
  };
}

export function getConfigGroupDescriptions(): Record<string, string> {
  return {
    basic: $t('platform.config.groupDescriptions.basic'),
    sms: $t('platform.config.groupDescriptions.sms'),
    inbox: $t('platform.config.groupDescriptions.inbox'),
    email: $t('platform.config.groupDescriptions.email'),
    storage: $t('platform.config.groupDescriptions.storage'),
  };
}

/** 表单类配置分组（非运行参数） */
export const CONFIG_SETTINGS_GROUPS = [
  'basic',
  'sms',
  'inbox',
  'email',
  'storage',
] as const;

export type ConfigSettingsGroup = (typeof CONFIG_SETTINGS_GROUPS)[number];

export function configFieldLocaleKey(
  configKey: string,
  part: 'label' | 'remark',
): string {
  return `platform.config.fields.${configKey}.${part}`;
}

export function configFieldLabel(row: Pick<ConfigRow, 'key' | 'name'>): string {
  const localeKey = configFieldLocaleKey(row.key, 'label');
  return $te(localeKey) ? $t(localeKey) : row.name;
}

export function configFieldRemark(
  row: Pick<ConfigRow, 'key' | 'remark'>,
): string | undefined {
  const fallback = row.remark?.trim();
  const localeKey = configFieldLocaleKey(row.key, 'remark');
  if ($te(localeKey)) {
    return $t(localeKey);
  }
  return fallback || undefined;
}

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

/** 默认主页：从菜单路由下拉选择 */
export const SITE_FIXED_PAGE_KEY = 'site.fixed_page';

export function isAttachmentImageField(key: string): boolean {
  return (CONFIG_ATTACHMENT_IMAGE_KEYS as readonly string[]).includes(key);
}

export function isHomePathSelectField(key: string): boolean {
  return key === SITE_FIXED_PAGE_KEY;
}

function normalizeMenuPath(path: string): string {
  const trimmed = path.trim();
  return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
}

/** 可作为登录默认主页的菜单类型：目录 / 菜单 / 内嵌 */
function isHomePathMenuType(type: number): boolean {
  return type === 1 || type === 2 || type === 4;
}

/** 将菜单树扁平化为默认主页下拉选项 */
export function flattenMenuTreeForHomePathSelect(
  nodes: MenuTreeNode[],
  ancestors: string[] = [],
): { label: string; value: string }[] {
  const options: { label: string; value: string }[] = [];

  for (const node of nodes) {
    if (node.type === 3 || node.type === 5 || node.status !== 1) {
      continue;
    }

    const path = node.path?.trim();
    const title = menuDisplayLabel(node);
    const labelParts = [...ancestors, title];

    if (path && isHomePathMenuType(node.type)) {
      const normalized = normalizeMenuPath(path);
      options.push({
        value: normalized,
        label: `${labelParts.join(' / ')} (${normalized})`,
      });
    }

    if (node.children?.length) {
      options.push(
        ...flattenMenuTreeForHomePathSelect(node.children, labelParts),
      );
    }
  }

  return options;
}

/** 若当前值不在菜单列表中，保留一项以便回显 */
export function mergeLegacyHomePathOption(
  options: { label: string; value: string }[],
  currentValue: string,
): { label: string; value: string }[] {
  const normalized = normalizeMenuPath(currentValue);
  if (!normalized) {
    return options;
  }
  if (options.some((item) => item.value === normalized)) {
    return options;
  }
  return [
    {
      label: $t('platform.config.selectOptions.homePathLegacy', {
        path: normalized,
      }),
      value: normalized,
    },
    ...options,
  ];
}

/** 下拉类配置项选项（mail_type / mail_verify_type） */
export function getConfigSelectOptions(): Record<
  string,
  { label: string; value: string }[]
> {
  return {
    'mail.type': [{ label: 'SMTP', value: '1' }],
    'mail.verify_type': [
      { label: $t('platform.config.selectOptions.mailVerifyNone'), value: '0' },
      { label: 'TLS', value: '1' },
      { label: 'SSL', value: '2' },
    ],
    'sms.provider': [
      {
        label: $t('platform.config.selectOptions.smsAliyun'),
        value: 'aliyun',
      },
      {
        label: $t('platform.config.selectOptions.smsTencent'),
        value: 'tencent',
      },
      {
        label: $t('platform.config.selectOptions.smsHuawei'),
        value: 'huawei',
      },
    ],
    [STORAGE_DRIVER_KEY]: [
      {
        label: $t('platform.config.selectOptions.storagePublic'),
        value: 'public',
      },
      {
        label: $t('platform.config.selectOptions.storageLocal'),
        value: 'local',
      },
      {
        label: $t('platform.config.selectOptions.storageOss'),
        value: 'oss',
      },
      {
        label: $t('platform.config.selectOptions.storageCos'),
        value: 'cos',
      },
      {
        label: $t('platform.config.selectOptions.storageS3'),
        value: 's3',
      },
    ],
  };
}

export function getParamTypeTagOptions() {
  return [
    {
      color: 'default',
      label: $t('platform.config.paramType.string'),
      value: 'string',
    },
    {
      color: 'processing',
      label: $t('platform.config.paramType.number'),
      value: 'number',
    },
    {
      color: 'warning',
      label: $t('platform.config.paramType.bool'),
      value: 'bool',
    },
    {
      color: 'success',
      label: $t('platform.config.paramType.json'),
      value: 'json',
    },
  ] as const;
}
