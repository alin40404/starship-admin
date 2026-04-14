import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { AdminRow } from '#/api/system/admin';
import type { DeptNode } from '#/api/system/department';

import { fetchDepartmentTree } from '#/api/system/department';

export function mapDeptForTreeSelect(
  nodes: DeptNode[],
): { label: string; value: number; children?: any[] }[] {
  return nodes.map((n) => ({
    label: n.name,
    value: n.id,
    children: n.children?.length
      ? mapDeptForTreeSelect(n.children)
      : undefined,
  }));
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '搜索用户名',
      },
      fieldName: 'username',
      label: '用户名',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '搜索真实姓名/昵称',
      },
      fieldName: 'name',
      label: '姓名',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '搜索邮箱',
      },
      fieldName: 'email',
      label: '邮箱',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '搜索手机',
      },
      fieldName: 'mobile',
      label: '手机',
    },
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: async () => mapDeptForTreeSelect(await fetchDepartmentTree()),
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        placeholder: '请选择部门',
        /** 关闭树连接线/叶节点装饰，避免下拉项左侧出现圆点等图标感样式 */
        treeIcon: false,
        treeLine: false,
      },
      fieldName: 'dept_id',
      label: '部门',
    },
    {
      component: 'RangePicker',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        showTime: true,
      },
      fieldName: 'updatedTime',
      label: '更新时间',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<AdminRow>,
  onStatusBeforeChange: (newVal: number, row: AdminRow) => Promise<boolean>,
): VxeTableGridColumns {
  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', sortable: true, title: 'ID', width: 70 },
    { field: 'username', minWidth: 120, sortable: true, title: '用户名' },
    {
      field: 'real_name',
      minWidth: 120,
      sortable: true,
      title: '真实姓名',
      visible: false,
    },
    { field: 'nickname', minWidth: 100, sortable: true, title: '昵称' },
    { field: 'mobile', minWidth: 130, sortable: true, title: '手机' },
    {
      field: 'email',
      minWidth: 180,
      sortable: true,
      title: '邮箱',
      visible: false,
    },
    {
      field: 'dept_id',
      formatter: ({ row }) =>
        (row as AdminRow).department?.name ?? '—',
      minWidth: 120,
      sortable: true,
      title: '部门',
    },
    {
      field: 'roles',
      formatter: ({ row }) =>
        ((row as AdminRow).roles ?? [])
          .map((r) => r.name)
          .join('、') || '—',
      minWidth: 160,
      sortable: false,
      title: '角色',
    },
    {
      field: 'updated_at',
      minWidth: 170,
      sortable: true,
      title: '更新时间',
    },
    {
      field: 'created_at',
      minWidth: 170,
      sortable: true,
      title: '创建时间',
      visible: false,
    },
    {
      field: 'status',
      sortable: true,
      title: '状态',
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: AdminRow) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: (row: AdminRow) => ({
            disabled: row.is_super === 1,
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'username',
          nameTitle: '管理员',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: (row: AdminRow) => row.is_super !== 1,
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: (row: AdminRow) => row.is_super !== 1,
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 160,
    },
  ];
}

export function buildAdminFormSchema(params: {
  deptTreeData: { label: string; value: number; children?: any[] }[];
  lockRoles?: boolean;
  roleOptions: { label: string; value: number }[];
  postOptions: { label: string; value: number }[];
  isEdit: boolean;
}): VbenFormSchema[] {
  const { deptTreeData, lockRoles = false, postOptions, isEdit } = params;
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: isEdit,
      },
      fieldName: 'username',
      label: '用户名',
      rules: 'required',
    },
    {
      component: 'InputPassword',
      componentProps: {
        class: 'w-full',
        placeholder: isEdit ? '留空则不修改密码' : undefined,
      },
      fieldName: 'password',
      label: isEdit ? '新密码' : '密码',
      rules: isEdit ? undefined : 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
      },
      fieldName: 'nickname',
      label: '昵称',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
      },
      fieldName: 'real_name',
      label: '真实姓名',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
      },
      fieldName: 'email',
      label: '邮箱',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
      },
      fieldName: 'mobile',
      label: '手机',
    },
    {
      component: 'TreeSelect',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        placeholder: '选择部门',
        showSearch: true,
        treeData: deptTreeData,
        treeDefaultExpandAll: true,
        /** 之前 treeLine: true 会启用连线与叶节点装饰；关闭后下拉项仅保留文字 */
        treeIcon: false,
        treeLine: false,
      },
      fieldName: 'dept_id',
      formItemClass: 'col-span-full',
      label: '部门',
    },
    /** 授权：与角色管理表单一致，由插槽渲染 a-tree */
    {
      component: 'Input',
      controlClass: 'w-full min-w-0',
      defaultValue: [],
      fieldName: 'role_ids',
      formItemClass: 'col-span-full w-full min-w-0 items-start',
      label: '授权',
      rules: lockRoles ? undefined : 'selectRequired',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        mode: 'multiple',
        options: postOptions,
        placeholder: '可选',
      },
      fieldName: 'post_ids',
      formItemClass: 'col-span-full',
      label: '岗位',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        disabled: lockRoles,
        optionType: 'button',
        options: [
          { label: '启用', value: 1 },
          { label: '禁用', value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      formItemClass: 'col-span-full',
      label: '状态',
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
