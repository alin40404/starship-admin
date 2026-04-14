import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { MenuTreeNode } from '#/api/system/menu';
import { TABLE_CELL_EMPTY } from '#/constants/table';

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

export function mapMenuToTreeSelect(
  nodes: MenuTreeNode[],
): { label: string; value: number; children?: any[] }[] {
  return nodes.map((n) => ({
    label: n.title?.trim() ? `${n.name}（${n.title}）` : n.name,
    value: n.id,
    children: n.children?.length
      ? mapMenuToTreeSelect(n.children)
      : undefined,
  }));
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
      title: '组件路径',
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
  parentTreeData: { label: string; value: number; children?: any[] }[];
}): VbenFormSchema[] {
  const { parentTreeData } = params;
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
      formItemClass: 'col-span-full',
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
      component: 'TreeSelect',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        placeholder: '请选择',
        showSearch: true,
        treeData: parentTreeData,
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
      },
      fieldName: 'parent_id',
      formItemClass: 'col-span-full',
      label: '上级菜单',
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
        show: (values) => typeIn(values, [1, 2, 4, 5]),
        triggerFields: ['type'],
      },
      fieldName: 'path',
      label: '路由地址',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '请输入组件路径',
      },
      dependencies: {
        show: (values) => typeIn(values, [2]),
        triggerFields: ['type'],
      },
      fieldName: 'component',
      label: '前端组件',
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
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: [
          { label: '缓存', value: 1 },
          { label: '不缓存', value: 0 },
        ],
      },
      defaultValue: 0,
      dependencies: {
        show: (values) => typeIn(values, [2]),
        triggerFields: ['type'],
      },
      fieldName: 'keep_alive',
      label: '页面缓存',
    },
    {
      component: 'Divider',
      fieldName: '_divider_other',
      formItemClass: 'col-span-full !mb-0 mt-2',
      hideLabel: true,
      renderComponentContent() {
        return {
          default: () => '其它设置',
        };
      },
    },
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      fieldName: 'hide_menu',
      formItemClass: 'col-span-1',
      label: '隐藏菜单',
    },
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      fieldName: 'hide_submenu',
      formItemClass: 'col-span-1',
      label: '隐藏子菜单',
    },
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      fieldName: 'hide_in_breadcrumb',
      formItemClass: 'col-span-1',
      label: '在面包屑中隐藏',
    },
    {
      component: 'Checkbox',
      componentProps: {},
      defaultValue: false,
      fieldName: 'hide_in_tab',
      formItemClass: 'col-span-1',
      label: '在标签栏中隐藏',
    },
    {
      component: 'Textarea',
      componentProps: {
        class: 'w-full',
        rows: 2,
      },
      fieldName: 'remark',
      formItemClass: 'col-span-full items-start',
      label: '备注',
    },
  ];
}
