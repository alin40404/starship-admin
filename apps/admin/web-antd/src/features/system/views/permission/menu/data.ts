import type { Recordable } from '@vben/types';
import type { VbenFormSchema } from '#/adapter/form';
import { z } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { MenuTreeNode } from '#/features/system/api/menu';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { MENU_PERMISSIONS } from '#/features/system/constants/menu-permissions';
import { componentKeys } from '#/router/routes';
import { IconifyIcon } from '@vben/icons';
import { getPopupContainer } from '@vben/utils';
import { h } from 'vue';
import { $t, $te } from '@vben/locales';
import { resolveMenuI18nKey } from '#/utils/menu-i18n';

/** 1目录 2菜单 3按钮 4内嵌 5外链 */
export function getMenuTypeOptions() {
  return [
    { label: $t('permission.menu.typeOptions.catalog'), value: 1 },
    { label: $t('permission.menu.typeOptions.menu'), value: 2 },
    { label: $t('permission.menu.typeOptions.button'), value: 3 },
    { label: $t('permission.menu.typeOptions.embedded'), value: 4 },
    { label: $t('permission.menu.typeOptions.link'), value: 5 },
  ];
}

export function getMenuTypeTagOptions() {
  return [
    {
      color: 'processing',
      label: $t('permission.menu.typeOptions.catalog'),
      value: 1,
    },
    {
      color: 'default',
      label: $t('permission.menu.typeOptions.menu'),
      value: 2,
    },
    {
      color: 'error',
      label: $t('permission.menu.typeOptions.button'),
      value: 3,
    },
    {
      color: 'warning',
      label: $t('permission.menu.typeOptions.embedded'),
      value: 4,
    },
    {
      color: 'success',
      label: $t('permission.menu.typeOptions.link'),
      value: 5,
    },
  ];
}

/** 侧栏显示 visible：1 显示 / 0 隐藏 */
export function getMenuVisibleTagOptions() {
  return [
    {
      color: 'success',
      label: $t('permission.menu.visible.show'),
      value: 1,
    },
    {
      color: 'default',
      label: $t('permission.menu.visible.hide'),
      value: 0,
    },
  ];
}

/** 徽标样式展示名（搜索、筛选用） */
export const BADGE_VARIANT_DISPLAY: Record<string, string> = {
  default: 'Default',
  destructive: 'Destructive',
  primary: 'Primary',
  success: 'Success',
  warning: 'Warning',
};

/** 与 `menu-badge.vue` 中 variantsMap 语义一致，用于色块 */
export const BADGE_VARIANT_SWATCH: Record<string, string> = {
  default: '#22c55e',
  destructive: '#ef4444',
  primary: '#3b82f6',
  success: '#22c55e',
  warning: '#eab308',
};

/** 下拉项：色块 + 文案（VNode 仅用于 option 插槽；options 仍为字符串以便选中框回显） */
function renderBadgeVariantOptionRow(value: string) {
  const color = BADGE_VARIANT_SWATCH[value] ?? 'rgb(148 163 184)';
  const text = BADGE_VARIANT_DISPLAY[value] ?? value;
  return h(
    'span',
    { class: 'inline-flex min-w-0 max-w-full items-center gap-2' },
    [
      h('span', {
        class:
          'inline-block size-2.5 shrink-0 rounded-full border border-solid border-black/10',
        style: { backgroundColor: color },
      }),
      h('span', { class: 'truncate' }, text),
    ],
  );
}

export const BADGE_VARIANT_OPTIONS = (
  ['default', 'destructive', 'primary', 'success', 'warning'] as const
).map((value) => ({
  label: BADGE_VARIANT_DISPLAY[value],
  value,
}));

/** 列表/上级菜单下拉：与路由侧栏一致，优先 i18n，否则 DB title / 内部名称 */
export function menuDisplayLabel(
  row: Pick<MenuTreeNode, 'name' | 'title' | 'permission' | 'module'>,
): string {
  const key = resolveMenuI18nKey(row, $te);
  if (key) {
    return $t(key);
  }

  const t = row.title?.trim();
  return t ? t : (row.name ?? '');
}

/** 供 ApiTreeSelect：`labelField: meta.title`，图标在 `meta.icon`。 */
export function mapMenuTreeForApiSelect(
  nodes: MenuTreeNode[],
): Array<{
  children?: ReturnType<typeof mapMenuTreeForApiSelect>;
  id: number;
  meta: {
    icon: string | null;
    internalName: string;
    menuType: number;
    title: string;
  };
}> {
  return nodes
    .filter((n) => n.type !== 3)
    .map((n) => ({
    id: n.id,
    meta: {
      title: menuDisplayLabel(n),
      internalName: n.name ?? '',
      icon: n.icon ?? null,
      menuType: n.type,
    },
    children: n.children?.length
      ? mapMenuTreeForApiSelect(n.children)
      : undefined,
    }));
}

function renderParentMenuTitleSlot({
  label,
  meta,
}: {
  label: string;
  meta?: Recordable<any>;
}) {
  if (!label) {
    return '';
  }
  const iconName =
    meta?.menuType === 3
      ? 'carbon:security'
      : typeof meta?.icon === 'string' && meta.icon.trim()
        ? meta.icon.trim()
        : undefined;
  const coms: ReturnType<typeof h>[] = [];
  if (iconName) {
    coms.push(
      h(IconifyIcon, { class: 'size-4 shrink-0', icon: iconName }),
    );
  }
  coms.push(h('span', { class: 'min-w-0 truncate' }, label));
  return h('div', { class: 'flex min-w-0 items-center gap-1' }, coms);
}

export type MenuAccessChecker = {
  can: (code: string) => boolean;
  canAny: (codes: string[]) => boolean;
};

export function useColumns(
  onActionClick: OnActionClickFn<MenuTreeNode>,
  onStatusBeforeChange: (
    newVal: number,
    row: MenuTreeNode,
  ) => Promise<boolean>,
  access: MenuAccessChecker,
): VxeTableGridColumns {
  const canSort = access.can(MENU_PERMISSIONS.SORT);
  const canStatus = access.can(MENU_PERMISSIONS.STATUS);

  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    {
      align: 'left',
      field: 'title',
      fixed: 'left',
      slots: { default: 'title' },
      title: $t('permission.menu.title'),
      treeNode: true,
      width: 240,
    },
    {
      align: 'center',
      cellRender: { name: 'CellTag', options: getMenuTypeTagOptions() },
      field: 'type',
      title: $t('permission.menu.type'),
      width: 96,
    },
    {
      field: 'name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 140,
      title: $t('permission.menu.internalName'),
    },
    {
      align: 'left',
      field: 'permission',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: $t('permission.menu.permission'),
    },
    {
      align: 'center',
      field: 'module',
      slots: { default: 'module' },
      title: $t('permission.menu.module'),
      width: 168,
    },
    {
      align: 'left',
      field: 'path',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: $t('permission.menu.path'),
    },
    {
      field: 'component',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 180,
      title: $t('permission.menu.component'),
      visible: false,
    },
    {
      align: 'center',
      className: canSort ? 'menu-sort-editable-col' : undefined,
      editRender: canSort
        ? {
            name: 'VxeNumberInput',
            props: {
              min: 0,
              type: 'integer',
            },
            events: {
              keydown(params, event: KeyboardEvent) {
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
      title: $t('permission.menu.sort'),
      width: 100,
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: getMenuVisibleTagOptions(),
      },
      field: 'visible',
      title: $t('permission.menu.sidebarVisible'),
      width: 96,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          beforeChange(newVal: unknown, row: MenuTreeNode) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({
            disabled: !canStatus,
          }),
        },
        name: 'CellSwitch',
      },
      field: 'status',
      title: $t('permission.menu.status'),
      width: 100,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('permission.menu.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'append',
            show: (row: MenuTreeNode) =>
              row.type !== 3 && access.can(MENU_PERMISSIONS.APPEND),
            text: $t('system.shared.appendChild'),
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(MENU_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(MENU_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 200,
    },
  ];
}

function typeIn(
  values: Record<string, unknown>,
  types: number[],
): boolean {
  return types.includes(Number(values.type));
}

export function buildMenuFormSchema(params: {
  parentTreeLoader: () => Promise<MenuTreeNode[]>;
  /** 随编辑目标变化，驱动 ApiTreeSelect 随 params 重新请求（api 引用变化不会触发）。 */
  parentTreeRefreshKey?: string | number;
}): VbenFormSchema[] {
  const { parentTreeLoader, parentTreeRefreshKey = 'new' } = params;
  return [
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full flex flex-wrap gap-1',
        optionType: 'button',
        options: getMenuTypeOptions(),
      },
      defaultValue: 2,
      fieldName: 'type',
      formItemClass: 'col-span-2 md:col-span-2',
      label: $t('permission.menu.type'),
      rules: 'selectRequired',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('ui.placeholder.input'),
      },
      fieldName: 'name',
      label: $t('permission.menu.menuName'),
      rules: 'required',
    },
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: async () =>
          mapMenuTreeForApiSelect(await parentTreeLoader()),
        childrenField: 'children',
        class: 'w-full',
        params: { editingKey: parentTreeRefreshKey },
        filterTreeNode(input: string, node: Recordable<any>) {
          if (!input?.length) {
            return true;
          }
          const q = input.trim().toLowerCase();
          const display = String(node.meta?.title ?? node.label ?? '');
          const internal = String(node.meta?.internalName ?? '').toLowerCase();
          return (
            display.includes(input) ||
            internal.includes(q)
          );
        },
        getPopupContainer,
        labelField: 'meta.title',
        placeholder: $t('ui.placeholder.select'),
        showSearch: true,
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
        treeNodeFilterProp: 'label',
        valueField: 'id',
      },
      fieldName: 'parent_id',
      label: $t('permission.menu.parent'),
      renderComponentContent() {
        return {
          title({ label, meta }: { label: string; meta: Recordable<any> }) {
            return renderParentMenuTitleSlot({ label, meta });
          },
        };
      },
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('ui.placeholder.input'),
      },
      fieldName: 'title',
      label: $t('permission.menu.title'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('ui.placeholder.input'),
      },
      dependencies: {
        rules: (values) =>
          typeIn(values, [1, 2, 4]) ? 'required' : null,
        show: (values) => typeIn(values, [1, 2, 4]),
        triggerFields: ['type'],
      },
      fieldName: 'path',
      label: $t('permission.menu.routePath'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('permission.menu.activePathPlaceholder'),
      },
      dependencies: {
        show: (values) => typeIn(values, [2, 4]),
        triggerFields: ['type'],
      },
      fieldName: 'active_path',
      help: $t('permission.menu.activePathHelp'),
      label: $t('permission.menu.activePath'),
    },
    {
      component: 'AutoComplete',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        filterOption(input: string, option: { value: string }) {
          return option.value.toLowerCase().includes(input.toLowerCase());
        },
        options: componentKeys.map((v) => ({ value: v })),
        placeholder: $t('permission.menu.componentPlaceholder'),
      },
      dependencies: {
        rules: (values) => (Number(values.type) === 2 ? 'required' : null),
        show: (values) => typeIn(values, [2]),
        triggerFields: ['type'],
      },
      fieldName: 'component',
      label: $t('permission.menu.component'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: 'https://',
      },
      dependencies: {
        rules: (values) =>
          Number(values.type) === 5
            ? z
                .string()
                .min(1, $t('permission.menu.linkUrlRequired'))
                .url($t('permission.menu.invalidUrl'))
            : null,
        show: (values) => typeIn(values, [5]),
        triggerFields: ['type'],
      },
      fieldName: 'link_url',
      label: $t('permission.menu.linkUrl'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: 'https://',
      },
      dependencies: {
        rules: (values) =>
          Number(values.type) === 4
            ? z
                .string()
                .min(1, $t('permission.menu.iframeSrcRequired'))
                .url($t('permission.menu.invalidUrl'))
            : null,
        show: (values) => typeIn(values, [4]),
        triggerFields: ['type'],
      },
      fieldName: 'iframe_src',
      label: $t('permission.menu.iframeSrc'),
    },
    {
      component: 'IconPicker',
      componentProps: {
        class: 'w-full',
        prefix: 'ep',
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type'],
      },
      fieldName: 'icon',
      label: $t('permission.menu.icon'),
    },
    {
      component: 'IconPicker',
      componentProps: {
        class: 'w-full',
        prefix: 'ep',
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type'],
      },
      fieldName: 'active_icon',
      label: $t('permission.menu.activeIcon'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('ui.placeholder.input'),
      },
      dependencies: {
        rules: (values) => (Number(values.type) === 3 ? 'required' : null),
        show: (values) => typeIn(values, [1, 2, 3, 4, 5]),
        triggerFields: ['type'],
      },
      fieldName: 'permission',
      label: $t('permission.menu.permission'),
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: [
          { label: $t('system.shared.status.enabled'), value: 1 },
          { label: $t('system.shared.status.disabled'), value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      label: $t('permission.menu.status'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [
          { label: $t('permission.menu.badgeDot'), value: 'dot' },
          { label: $t('permission.menu.badgeText'), value: 'normal' },
        ],
        placeholder: $t('ui.placeholder.select'),
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type'],
      },
      fieldName: 'badge_type',
      label: $t('permission.menu.badgeType'),
    },
    {
      component: 'Input',
      componentProps(values: Record<string, unknown>) {
        return {
          allowClear: true,
          class: 'w-full',
          disabled: values.badge_type !== 'normal',
          placeholder: $t('ui.placeholder.input'),
        };
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type', 'badge_type'],
      },
      fieldName: 'badge',
      label: $t('permission.menu.badge'),
    },
    {
      component: 'Select',
      componentProps(values: Record<string, unknown>) {
        const v = values.badge_variants;
        const key =
          typeof v === 'string' && v && v in BADGE_VARIANT_SWATCH ? v : '';
        return {
          allowClear: true,
          class: ['w-full', ...(key ? [`menu-form-badge-variant--${key}`] : [])],
          filterOption(input: string, option: { value?: string }) {
            const val = String(option?.value ?? '');
            const display = BADGE_VARIANT_DISPLAY[val] ?? val;
            const q = input.trim().toLowerCase();
            return (
              display.toLowerCase().includes(q) ||
              val.toLowerCase().includes(q)
            );
          },
          options: BADGE_VARIANT_OPTIONS,
          placeholder: $t('ui.placeholder.select'),
          showSearch: true,
        };
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type', 'badge_variants'],
      },
      fieldName: 'badge_variants',
      label: $t('permission.menu.badgeVariants'),
      renderComponentContent() {
        return {
          option: (slotProps: Record<string, unknown>) => {
            const value = String(slotProps.value ?? '');
            return renderBadgeVariantOptionRow(value);
          },
        };
      },
    },
    {
      component: 'InputNumber',
      componentProps: {
        class: 'w-full',
        min: 0,
      },
      defaultValue: 0,
      fieldName: 'sort',
      label: $t('permission.menu.sort'),
    },
    {
      component: 'Textarea',
      componentProps: {
        class: 'w-full',
        rows: 2,
      },
      fieldName: 'remark',
      formItemClass: 'col-span-2 md:col-span-2 items-start',
      label: $t('permission.menu.remark'),
    },
    {
      component: 'Divider',
      componentProps: {
        class: '!my-0',
        orientation: 'center',
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type'],
      },
      fieldName: '_divider_other',
      formItemClass: 'col-span-2 md:col-span-2 !mt-3 !pb-0 !mb-5',
      hideLabel: true,
      renderComponentContent() {
        return {
          default: () => $t('permission.menu.otherSettings'),
        };
      },
    },
    // 两列网格：先左后右，与设计图一致 — 左：缓存/隐藏菜单/面包屑；右：固定标签/隐藏子菜单/标签栏
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      dependencies: {
        show: (values) => typeIn(values, [2]),
        triggerFields: ['type'],
      },
      fieldName: 'keep_alive',
      formItemClass: 'col-span-2 md:col-span-1',
      renderComponentContent() {
        return {
          default: () => $t('permission.menu.keepAlive'),
        };
      },
    },
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      dependencies: {
        show: (values) => typeIn(values, [2, 4]),
        triggerFields: ['type'],
      },
      fieldName: 'affix_tab',
      formItemClass: 'col-span-2 md:col-span-1',
      renderComponentContent() {
        return {
          default: () => $t('permission.menu.affixTab'),
        };
      },
    },
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type'],
      },
      fieldName: 'hide_menu',
      formItemClass: 'col-span-2 md:col-span-1',
      renderComponentContent() {
        return {
          default: () => $t('permission.menu.hideMenu'),
        };
      },
    },
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      dependencies: {
        show: (values) => typeIn(values, [1, 2]),
        triggerFields: ['type'],
      },
      fieldName: 'hide_submenu',
      formItemClass: 'col-span-2 md:col-span-1',
      renderComponentContent() {
        return {
          default: () => $t('permission.menu.hideSubmenu'),
        };
      },
    },
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      dependencies: {
        show: (values) => typeIn(values, [1, 2, 4]),
        triggerFields: ['type'],
      },
      fieldName: 'hide_in_breadcrumb',
      formItemClass: 'col-span-2 md:col-span-1',
      renderComponentContent() {
        return {
          default: () => $t('permission.menu.hideInBreadcrumb'),
        };
      },
    },
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      dependencies: {
        show: (values) => typeIn(values, [1, 2, 4]),
        triggerFields: ['type'],
      },
      fieldName: 'hide_in_tab',
      formItemClass: 'col-span-2 md:col-span-1',
      renderComponentContent() {
        return {
          default: () => $t('permission.menu.hideInTab'),
        };
      },
    },
  ];
}
