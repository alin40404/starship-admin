import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { CategoryTreeNode, CategoryTypeOption } from '#/api/system/category';
import {
  fetchCategoryTree,
  mapCategoryForTreeSelect,
} from '#/api/system/category';
import { CATEGORY_PERMISSIONS } from '#/constants/category-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';

export type CategoryAccessChecker = {
  can: (code: string) => boolean;
};

export const CATEGORY_FLAG_OPTIONS = [
  { label: '热门', value: 'hot' },
  { label: '首页', value: 'index' },
  { label: '推荐', value: 'recommend' },
];

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '名称 / 别名' },
      fieldName: 'search',
      label: '关键词',
    },
  ];
}

export function buildFormSchema(params: {
  excludeId?: number;
  typeOptions: CategoryTypeOption[];
  onTypeChange?: (value: string) => void;
}): VbenFormSchema[] {
  const { excludeId, onTypeChange, typeOptions } = params;

  return [
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        onChange: (value: string) => onTypeChange?.(value),
        options: typeOptions.map((item) => ({
          label: item.label,
          value: item.value,
        })),
        placeholder: '栏目类型',
      },
      fieldName: 'type',
      label: '栏目类型',
      rules: 'required',
    },
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: async (queryParams?: { excludeId?: number; typeKey?: string }) =>
          loadCategoryTreeForParent(
            queryParams?.typeKey || undefined,
            queryParams?.excludeId,
          ),
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        params: {
          excludeId,
          typeKey: '',
        },
        placeholder: '上级分类（留空为顶级）',
        shouldFetch: (queryParams?: { typeKey?: string }) =>
          Boolean(queryParams?.typeKey),
        showSearch: true,
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
      },
      dependencies: {
        componentProps(values) {
          const type = values.type as string | undefined;
          return {
            disabled: !type,
            params: {
              excludeId,
              typeKey: type ?? '',
            },
            placeholder: type
              ? '上级分类（留空为顶级）'
              : '请先选择栏目类型',
          };
        },
        triggerFields: ['type'],
      },
      defaultValue: 0,
      fieldName: 'parent_id',
      label: '上级分类',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '分类名称' },
      fieldName: 'name',
      label: '分类名称',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '英文别名' },
      fieldName: 'nickname',
      label: '分类别名',
      rules: 'required',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        mode: 'multiple',
        options: CATEGORY_FLAG_OPTIONS,
        placeholder: '分类标志',
      },
      fieldName: 'flag',
      label: '分类标志',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'keywords',
      label: '关键字',
    },
    {
      component: 'AttachmentField',
      componentProps: {
        accept: 'image',
        class: 'w-full',
        multiple: false,
        placeholder: '图片 URL',
      },
      fieldName: 'image',
      formItemClass: 'col-span-full w-full items-start',
      label: '图片',
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', rows: 2 },
      fieldName: 'description',
      formItemClass: 'col-span-full w-full items-start',
      label: '描述',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: 'URL 自定义名称' },
      fieldName: 'diyname',
      label: '自定义名称',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: '权重',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: [
          { label: '正常', value: 'normal' },
          { label: '隐藏', value: 'hidden' },
        ],
      },
      defaultValue: 'normal',
      fieldName: 'status',
      label: '状态',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<CategoryTreeNode>,
  onStatusBeforeChange: (
    newVal: CategoryTreeNode['status'],
    row: CategoryTreeNode,
  ) => Promise<boolean>,
  access: CategoryAccessChecker,
  options: {
    showTypeColumn?: boolean;
    typeTagOptions?: Array<{ label: string; value: string }>;
  } = {},
): VxeTableGridColumns<CategoryTreeNode> {
  const { showTypeColumn = true, typeTagOptions = [] } = options;
  const canStatus = access.can(CATEGORY_PERMISSIONS.STATUS);
  const canSort = access.can(CATEGORY_PERMISSIONS.SORT);

  const columns: VxeTableGridColumns<CategoryTreeNode> = [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', title: 'ID', width: 72 },
  ];

  if (showTypeColumn) {
    columns.push({
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: typeTagOptions,
      },
      field: 'type',
      title: '类型',
      width: 100,
    });
  }

  columns.push(
    {
      align: 'left',
      field: 'name',
      minWidth: 180,
      title: '名称',
      treeNode: true,
    },
    {
      align: 'left',
      field: 'nickname',
      minWidth: 120,
      title: '别名',
    },
    {
      align: 'center',
      field: 'flag_labels',
      formatter: ({ cellValue }) =>
        Array.isArray(cellValue) && cellValue.length > 0
          ? cellValue.join('、')
          : TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '标志',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          getSrc: (row: CategoryTreeNode) => row.image,
        },
        name: 'CellImage',
        props: { height: 40, width: 40 },
      },
      field: 'image',
      title: '图片',
      width: 80,
    },
    {
      align: 'center',
      className: canSort ? 'category-sort-editable-col' : undefined,
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
      title: '权重',
      width: 100,
    },
    {
      field: 'status',
      title: '状态',
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: CategoryTreeNode) {
            return onStatusBeforeChange(
              newVal as CategoryTreeNode['status'],
              row,
            );
          },
          getSwitchProps: () => ({
            checkedChildren: '正常',
            checkedValue: 'normal',
            disabled: !canStatus,
            unCheckedChildren: '隐藏',
            unCheckedValue: 'hidden',
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '分类',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'append',
            show: () => access.can(CATEGORY_PERMISSIONS.APPEND),
            text: '新增下级',
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(CATEGORY_PERMISSIONS.UPDATE),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(CATEGORY_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 220,
    },
  );

  return columns;
}

export async function loadCategoryTreeForParent(
  type?: string,
  excludeId?: number,
): Promise<ReturnType<typeof mapCategoryForTreeSelect>> {
  const tree = await fetchCategoryTree(type ? { type } : undefined);

  function filter(nodes: CategoryTreeNode[]): CategoryTreeNode[] {
    return nodes
      .filter((node) => node.id !== excludeId)
      .map((node) => ({
        ...node,
        children: node.children?.length ? filter(node.children) : [],
      }));
  }

  const filtered = excludeId ? filter(tree) : tree;

  return [{ label: '无', value: 0 }, ...mapCategoryForTreeSelect(filtered)];
}
