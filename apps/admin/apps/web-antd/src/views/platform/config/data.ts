/** 站点配置分组（basic / email 分组） */
import type { ConfigRow } from '#/api/system/config';
import type { MenuTreeNode } from '#/api/system/menu';

import { $te } from '@vben/locales';

import { $t } from '#/locales';
import { TABLE_CELL_EMPTY } from '#/constants/table';
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

/** 存储配置：公共访问地址（私有存储不展示） */
export const STORAGE_PUBLIC_URL_KEY = 'storage.public_url';

/** 存储配置：云驱动标识 */
export const STORAGE_CLOUD_DRIVERS = ['oss', 'cos', 's3'] as const;

export type StorageCloudDriver = (typeof STORAGE_CLOUD_DRIVERS)[number];

/** 按驱动决定存储配置项是否展示（仿短信 provider 切换） */
export function isStorageFieldVisible(key: string, driver: string): boolean {
  if ((STORAGE_COMMON_KEYS as readonly string[]).includes(key)) {
    return true;
  }

  if (key === STORAGE_PUBLIC_URL_KEY) {
    return driver !== 'local';
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
export const CONFIG_ATTACHMENT_IMAGE_KEYS = ['site.logo', 'site.favicon'] as const;

/** 默认主页：从菜单路由下拉选择 */
export const SITE_FIXED_PAGE_KEY = 'site.fixed_page';

export function isAttachmentImageField(key: string): boolean {
  return (CONFIG_ATTACHMENT_IMAGE_KEYS as readonly string[]).includes(key);
}

export function configAttachmentPlaceholder(key: string): string {
  if (key === 'site.favicon') {
    return $t('platform.config.faviconPlaceholder');
  }
  return $t('platform.config.logoPlaceholder');
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

export interface HomePathCascaderOption {
  children?: HomePathCascaderOption[];
  disabled?: boolean;
  label: string;
  value: string;
}

function menuNodeGroupValue(id: number): string {
  return `__menu_${id}`;
}

function isStoredHomePathValue(value: string): boolean {
  return value.startsWith('/');
}

/** 将菜单树转为默认主页 Cascader 联动选项 */
export function mapMenuTreeForHomePathCascader(
  nodes: MenuTreeNode[],
): HomePathCascaderOption[] {
  const result: HomePathCascaderOption[] = [];

  for (const node of nodes) {
    if (node.type === 3 || node.type === 5 || node.status !== 1) {
      continue;
    }

    const path = node.path?.trim();
    const selectable = Boolean(path && isHomePathMenuType(node.type));
    const children = node.children?.length
      ? mapMenuTreeForHomePathCascader(node.children)
      : undefined;

    if (!selectable && !children?.length) {
      continue;
    }

    result.push({
      label: menuDisplayLabel(node),
      value: selectable ? normalizeMenuPath(path!) : menuNodeGroupValue(node.id),
      disabled: !selectable && !children?.length,
      children: children?.length ? children : undefined,
    });
  }

  return result;
}

function homePathExistsInCascader(
  nodes: HomePathCascaderOption[],
  path: string,
): boolean {
  for (const node of nodes) {
    if (node.value === path) {
      return true;
    }
    if (node.children?.length && homePathExistsInCascader(node.children, path)) {
      return true;
    }
  }
  return false;
}

/** 若当前值不在菜单列表中，保留一项以便回显 */
export function mergeLegacyHomePathCascaderOption(
  options: HomePathCascaderOption[],
  currentValue: string,
): HomePathCascaderOption[] {
  const normalized = normalizeMenuPath(currentValue);
  if (!normalized || homePathExistsInCascader(options, normalized)) {
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

/** 路由 path → Cascader 选中路径 */
export function homePathToCascaderValue(
  options: HomePathCascaderOption[],
  path: string,
): string[] {
  const normalized = normalizeMenuPath(path);
  if (!normalized) {
    return [];
  }

  function walk(
    nodes: HomePathCascaderOption[],
    trail: string[],
  ): null | string[] {
    for (const node of nodes) {
      const current = [...trail, node.value];
      if (node.value === normalized) {
        return current;
      }
      if (node.children?.length) {
        const found = walk(node.children, current);
        if (found) {
          return found;
        }
      }
    }
    return null;
  }

  return walk(options, []) ?? (normalized ? [normalized] : []);
}

/** Cascader 选中路径 → 路由 path */
export function cascaderValueToHomePath(values: string[] | undefined): string {
  if (!values?.length) {
    return '';
  }
  for (let index = values.length - 1; index >= 0; index -= 1) {
    const value = values[index];
    if (value && isStoredHomePathValue(value)) {
      return value;
    }
  }
  return '';
}

/** 根据 Cascader 值解析各级菜单标题 */
export function resolveHomePathCascaderLabels(
  options: HomePathCascaderOption[],
  values: string[],
): string[] {
  const labels: string[] = [];
  let current = options;

  for (const value of values) {
    const node = current.find((item) => item.value === value);
    if (!node) {
      break;
    }
    labels.push(node.label);
    current = node.children ?? [];
  }

  return labels;
}

/** 选中后展示：工作台 / 控制台 (/dashboard/console) */
export function formatHomePathDisplay(
  labels: string[],
  homePath: string,
): string {
  const normalized = normalizeMenuPath(homePath);
  const title = labels.filter(Boolean).join(' / ');

  if (title && normalized) {
    return `${title} (${normalized})`;
  }
  if (title) {
    return title;
  }
  return normalized;
}

/** 站点默认展示时区（字典 site_timezone） */
export const SITE_TIMEZONE_KEY = 'site.timezone';

export const SITE_TIMEZONE_DICT_CODE = 'site_timezone';

export function mergeSiteTimezoneSelectOptions(
  options: { label: string; value: string }[],
  currentValue?: string,
): { label: string; value: string }[] {
  const normalized = currentValue?.trim();
  if (normalized && !options.some((item) => item.value === normalized)) {
    return [{ label: normalized, value: normalized }, ...options];
  }

  return options;
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

export function validateParamValue(
  type: string,
  value: ConfigRow['value'],
): boolean {
  const trimmed = String(value ?? '').trim();

  if (type === 'number') {
    return trimmed !== '' && Number.isFinite(Number(trimmed));
  }

  if (type === 'bool') {
    return ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'].includes(
      trimmed.toLowerCase(),
    );
  }

  if (type === 'json' && trimmed !== '') {
    try {
      JSON.parse(trimmed);
      return true;
    } catch {
      return false;
    }
  }

  return true;
}

export function validateParamSort(sort: unknown): boolean {
  const value = Number(sort);

  return Number.isFinite(value) && value >= 0 && Number.isInteger(value);
}

export const PARAM_VALUE_EDIT_SLOT = 'param_value_edit';

export const PARAM_VALUE_DEFAULT_SLOT = 'param_value_default';

export function isParamBoolTrue(value: ConfigRow['value']): boolean {
  return ['true', '1', 'yes', 'on'].includes(String(value ?? '').toLowerCase());
}

export function normalizeParamValueForSave(
  type: string,
  value: unknown,
): string {
  if (type === 'number') {
    return value === null || value === undefined || value === ''
      ? ''
      : String(value);
  }

  if (type === 'bool') {
    return String(value ?? 'false');
  }

  return String(value ?? '').trim();
}

export function formatParamJsonForEdit(value: ConfigRow['value']): string {
  const raw = String(value ?? '').trim();
  if (raw === '') {
    return '';
  }

  try {
    return JSON.stringify(JSON.parse(raw), null, 2);
  } catch {
    return raw;
  }
}

export function normalizeParamValueForEdit(
  type: string,
  value: ConfigRow['value'],
): ConfigRow['value'] {
  const raw = String(value ?? '');

  if (type === 'number') {
    const parsed = Number(raw);

    return Number.isFinite(parsed) ? String(parsed) : '';
  }

  if (type === 'bool') {
    return ['true', '1', 'yes', 'on'].includes(raw.toLowerCase())
      ? 'true'
      : 'false';
  }

  if (type === 'json') {
    return formatParamJsonForEdit(value);
  }

  return raw;
}

export function buildParamSortColumn(canManage: boolean) {
  return {
    align: 'center' as const,
    className: canManage ? 'param-sort-editable-col' : undefined,
    editRender: canManage
      ? {
          name: 'VxeNumberInput',
          props: {
            min: 0,
            type: 'integer',
          },
          events: {
            keydown(
              params: { $table: { clearEdit: () => void } },
              event: KeyboardEvent,
            ) {
              if (event.key !== 'Enter') {
                return;
              }
              event.preventDefault();
              (event.target as HTMLElement | null)?.blur?.();
              void params.$table.clearEdit();
            },
          },
        }
      : undefined,
    field: 'sort',
    title: $t('platform.config.param.sort'),
    width: 110,
  };
}

export function buildParamValueColumn(canManage: boolean) {
  return {
    align: 'left' as const,
    className: canManage ? 'param-value-editable-col' : undefined,
    editRender: canManage ? {} : undefined,
    field: 'value',
    formatter: ({ cellValue, row }: { cellValue: ConfigRow['value']; row: ConfigRow }) => {
      if (row.type === 'bool') {
        return '';
      }

      return cellValue ?? TABLE_CELL_EMPTY;
    },
    minWidth: 180,
    slots: {
      ...(canManage ? { edit: PARAM_VALUE_EDIT_SLOT } : {}),
      default: PARAM_VALUE_DEFAULT_SLOT,
    },
    title: $t('platform.config.param.value'),
  };
}
