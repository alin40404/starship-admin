import type { Recordable } from '@vben/types';
import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { MenuTreeNode } from '#/api/system/menu';
import { TABLE_CELL_EMPTY } from '#/constants/table';
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

export const BADGE_VARIANT_OPTIONS = [
  { label: 'default', value: 'default' },
  { label: 'destructive', value: 'destructive' },
  { label: 'primary', value: 'primary' },
  { label: 'success', value: 'success' },
  { label: 'warning', value: 'warning' },
];

/** 供 ApiTreeSelect：`labelField: meta.title`，图标在 `meta.icon`。 */
export function mapMenuTreeForApiSelect(
  nodes: MenuTreeNode[],
): Array<{
  children?: ReturnType<typeof mapMenuTreeForApiSelect>;
  id: number;
  meta: { icon: string | null; menuType: number; title: string };
}> {
  return nodes.map((n) => ({
    id: n.id,
    meta: {
      title: n.title?.trim() ? `${n.name}（${n.title}）` : n.name,
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

export function useColumns(
  onActionClick: OnActionClickFn<MenuTreeNode>,
): VxeTableGridColumns {
  return [
    {
      align: 'left',
      field: 'name',
      fixed: 'left',
      slots: { default: 'title' },
      title: '菜单名称',
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
      field: 'title',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '标题',
      visible: false,
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
    { field: 'sort', title: '排序', width: 72 },
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
      cellRender: { name: 'CellTag' },
      field: 'status',
      title: '状态',
      width: 88,
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
            text: '新增下级',
            type: 'link',
          },
          {
            code: 'edit',
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
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
          const title: string = node.meta?.title ?? node.label ?? '';
          if (!title) {
            return false;
          }
          return title.includes(input);
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
        rules: (values) => (Number(values.type) !== 5 ? 'required' : null),
        show: (values) => typeIn(values, [1, 2, 3, 4, 5]),
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
        placeholder: '内嵌页面 URL',
      },
      dependencies: {
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
      componentProps: {
        class: 'w-full',
        placeholder: '请输入',
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type'],
      },
      fieldName: 'badge',
      label: '徽标内容',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: BADGE_VARIANT_OPTIONS,
        placeholder: '请选择',
      },
      dependencies: {
        show: (values) => !typeIn(values, [3]),
        triggerFields: ['type'],
      },
      fieldName: 'badge_variants',
      label: '徽标样式',
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
        show: (values) => typeIn(values, [2, 4]),
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
        show: (values) => typeIn(values, [1, 2, 4, 5]),
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
        show: (values) => typeIn(values, [1, 2, 4, 5]),
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
        show: (values) => typeIn(values, [1, 2, 4, 5]),
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
