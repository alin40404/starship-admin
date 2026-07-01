import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { DepartmentTreeNode } from '#/api/system/department';
import { fetchDepartmentTree, mapDeptForTreeSelect } from '#/api/system/department';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { DEPARTMENT_PERMISSIONS } from '#/constants/department-permissions';

export type DepartmentAccessChecker = {
  can: (code: string) => boolean;
};

export function useColumns(
  onActionClick: OnActionClickFn<DepartmentTreeNode>,
  onStatusBeforeChange: (
    newVal: number,
    row: DepartmentTreeNode,
  ) => Promise<boolean>,
  access: DepartmentAccessChecker,
): VxeTableGridColumns {
  const canUpdate = access.can(DEPARTMENT_PERMISSIONS.UPDATE);

  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    {
      field: 'name',
      minWidth: 180,
      title: '部门名称',
      treeNode: true,
    },
    {
      field: 'leader',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 100,
      title: '负责人',
    },
    {
      field: 'phone',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '联系电话',
    },
    { field: 'sort', title: '排序', width: 80 },
    {
      field: 'status',
      title: '状态',
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: DepartmentTreeNode) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({ disabled: !canUpdate }),
        },
        name: 'CellSwitch',
      },
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '部门',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'append',
            show: () => access.can(DEPARTMENT_PERMISSIONS.APPEND),
            text: '新增下级',
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(DEPARTMENT_PERMISSIONS.UPDATE),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(DEPARTMENT_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 220,
    },
  ];
}

export function buildDepartmentFormSchema(params: {
  parentTreeLoader: () => Promise<ReturnType<typeof mapDeptForTreeSelect>>;
}): VbenFormSchema[] {
  return [
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: params.parentTreeLoader,
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        placeholder: '上级部门（留空为顶级）',
        showSearch: true,
        treeDefaultExpandAll: true,
        treeIcon: false,
        treeLine: false,
      },
      defaultValue: 0,
      fieldName: 'parent_id',
      label: '上级部门',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'name',
      label: '部门名称',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'leader',
      label: '负责人',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'phone',
      label: '联系电话',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'email',
      label: '邮箱',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
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
          { label: '启用', value: 1 },
          { label: '禁用', value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      label: '状态',
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', rows: 2 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: '备注',
    },
  ];
}

export async function loadDepartmentTreeForParent(
  excludeId?: number,
): Promise<ReturnType<typeof mapDeptForTreeSelect>> {
  const tree = await fetchDepartmentTree(true);
  if (!excludeId) {
    return [{ label: '顶级部门', value: 0 }, ...mapDeptForTreeSelect(tree)];
  }
  function filter(nodes: DepartmentTreeNode[]): DepartmentTreeNode[] {
    return nodes
      .filter((n) => n.id !== excludeId)
      .map((n) => ({
        ...n,
        children: n.children?.length ? filter(n.children) : [],
      }));
  }
  return [
    { label: '顶级部门', value: 0 },
    ...mapDeptForTreeSelect(filter(tree)),
  ];
}
