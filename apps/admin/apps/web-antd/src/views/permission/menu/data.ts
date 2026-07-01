import type { Recordable } from '@vben/types';
import type { VbenFormSchema } from '#/adapter/form';
import { z } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { MenuTreeNode } from '#/api/system/menu';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { MENU_PERMISSIONS } from '#/constants/menu-permissions';
import { componentKeys } from '#/router/routes';
import { IconifyIcon } from '@vben/icons';
import { getPopupContainer } from '@vben/utils';
import { h } from 'vue';

/** 1目录 2菜单 3按钮 4内嵌 5外链 */
export const MENU_TYPE_OPTIONS = [
  { label: '目录', value: 1 },
  { label: '菜单', value: 2 },
  { label: '按钮', value: 3 },
  { label: '内嵌', value: 4 },
  { label: '外链', value: 5 },
];

export const MENU_TYPE_TAG_OPTIONS = [
  { color: 'processing', label: '目录', value: 1 },
  { color: 'default', label: '菜单', value: 2 },
  { color: 'error', label: '按钮', value: 3 },
  { color: 'warning', label: '内嵌', value: 4 },
  { color: 'success', label: '外链', value: 5 },
];

/** 侧栏显示 visible：1 显示 / 0 隐藏 */
export const MENU_VISIBLE_TAG_OPTIONS = [
  { color: 'success', label: '显示', value: 1 },
  { color: 'default', label: '隐藏', value: 0 },
];

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

/** 列表/上级菜单下拉：与路由侧栏一致，优先展示中文标题，否则内部名称 */
export function menuDisplayLabel(
  row: Pick<MenuTreeNode, 'name' | 'title'>,
): string {
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
      title: '标题',
      treeNode: true,
      width: 240,
    },
    {
      align: 'center',
      cellRender: { name: 'CellTag', options: MENU_TYPE_TAG_OPTIONS },
      field: 'type',
      title: '类型',
      width: 96,
    },
    {
      field: 'name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 140,
      title: '内部名称',
    },
    {
      align: 'left',
      field: 'permission',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '权限标识',
    },
    {
      align: 'left',
      field: 'path',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '路由路径',
    },
    {
      field: 'component',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 180,
      title: '页面组件',
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
      title: '排序',
      width: 100,
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: MENU_VISIBLE_TAG_OPTIONS,
      },
      field: 'visible',
      title: '侧栏显示',
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
      title: '状态',
      width: 100,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '菜单',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'append',
            show: (row: MenuTreeNode) =>
              row.type !== 3 && access.can(MENU_PERMISSIONS.APPEND),
            text: '新增下级',
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(MENU_PERMISSIONS.UPDATE),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(MENU_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
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
        options: MENU_TYPE_OPTIONS,
      },
      defaultValue: 2,
      fieldName: 'type',
      formItemClass: 'col-span-2 md:col-span-2',
      label: '类型',
      rules: 'selectRequired',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '请输入',
      },
      fieldName: 'name',
      label: '菜单名称',
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
        placeholder: '请选择',
        showSearch: true,
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
        treeNodeFilterProp: 'label',
        valueField: 'id',
      },
      fieldName: 'parent_id',
      label: '上级菜单',
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
        placeholder: '请输入',
      },
      fieldName: 'title',
      label: '标题',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '请输入',
      },
      dependencies: {
        rules: (values) =>
          typeIn(values, [1, 2, 4]) ? 'required' : null,
        show: (values) => typeIn(values, [1, 2, 4]),
        triggerFields: ['type'],
      },
      fieldName: 'path',
      label: '路由地址',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '须为已有菜单的路由地址',
      },
      dependencies: {
        show: (values) => typeIn(values, [2, 4]),
        triggerFields: ['type'],
      },
      fieldName: 'active_path',
      help: '跳转到当前路由时要在侧栏高亮的菜单路径；需与某条菜单的「路由地址」一致，以 / 开头。',
      label: '激活路径',
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
        placeholder: '选择或输入页面路径',
      },
      dependencies: {
        rules: (values) => (Number(values.type) === 2 ? 'required' : null),
        show: (values) => typeIn(values, [2]),
        triggerFields: ['type'],
      },
      fieldName: 'component',
      label: '页面组件',
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
            ? z.string().min(1, '请填写外链地址').url('请输入合法 URL')
            : null,
        show: (values) => typeIn(values, [5]),
        triggerFields: ['type'],
      },
      fieldName: 'link_url',
      label: '外链地址',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: 'https:// 开头的内嵌页面 URL',
      },
      dependencies: {
        rules: (values) =>
          Number(values.type) === 4
            ? z.string().min(1, '请填写内嵌地址').url('请输入合法 URL')
            : null,
        show: (values) => typeIn(values, [4]),
        triggerFields: ['type'],
      },
      fieldName: 'iframe_src',
      label: '内嵌地址',
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
      label: '图标',
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
      label: '激活图标',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '请输入',
      },
      dependencies: {
        rules: (values) => (Number(values.type) === 3 ? 'required' : null),
        show: (values) => typeIn(values, [1, 2, 3, 4, 5]),
        triggerFields: ['type'],
      },
      fieldName: 'permission',
      label: '权限标识',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: [
          { label: '已启用', value: 1 },
          { label: '已禁用', value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      label: '状态',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [
          { label: '圆点', value: 'dot' },
          { label: '文字', value: 'normal' },
        ],
        placeholder: '请选择',
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type'],
      },
      fieldName: 'badge_type',
      label: '徽标类型',
    },
    {
      component: 'Input',
      componentProps(values: Record<string, unknown>) {
        return {
          allowClear: true,
          class: 'w-full',
          disabled: values.badge_type !== 'normal',
          placeholder: '请输入',
        };
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type', 'badge_type'],
      },
      fieldName: 'badge',
      label: '徽标内容',
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
          placeholder: '请选择',
          showSearch: true,
        };
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type', 'badge_variants'],
      },
      fieldName: 'badge_variants',
      label: '徽标样式',
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
      label: '排序',
    },
    {
      component: 'Textarea',
      componentProps: {
        class: 'w-full',
        rows: 2,
      },
      fieldName: 'remark',
      formItemClass: 'col-span-2 md:col-span-2 items-start',
      label: '备注',
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
          default: () => '其它设置',
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
          default: () => '缓存标签页',
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
          default: () => '固定在标签',
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
          default: () => '隐藏菜单',
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
          default: () => '隐藏子菜单',
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
          default: () => '在面包屑中隐藏',
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
          default: () => '在标签栏中隐藏',
        };
      },
    },
  ];
}
