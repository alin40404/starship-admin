import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { AdminRow } from '#/api/system/admin';

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        placeholder: '模糊搜索用户名',
      },
      fieldName: 'username',
      label: '用户名',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<AdminRow>,
  onStatusToggle: (row: AdminRow, nextStatus: number) => Promise<void>,
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
    { field: 'nickname', minWidth: 100, sortable: true, title: '昵称' },
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
      align: 'center',
      cellRender: {
        attrs: {
          onToggle: onStatusToggle,
        },
        name: 'CellAdminStatusSwitch',
      },
      field: 'status',
      sortable: true,
      title: '状态',
      width: 148,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'username',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: (row: AdminRow) => row.is_super !== 1,
            text: '编辑',
            type: 'primary',
          },
          {
            code: 'delete',
            show: (row: AdminRow) => row.is_super !== 1,
            text: '删除',
            type: 'danger',
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
  const { deptTreeData, lockRoles = false, roleOptions, postOptions, isEdit } =
    params;
  return [
    {
      component: 'Input',
      componentProps: {
        disabled: isEdit,
      },
      fieldName: 'username',
      label: '用户名',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: {
        placeholder: isEdit ? '留空则不修改密码' : '',
        showPassword: true,
        type: 'password',
      },
      fieldName: 'password',
      label: isEdit ? '新密码' : '密码',
      rules: isEdit ? undefined : 'required',
    },
    {
      component: 'Input',
      fieldName: 'nickname',
      label: '昵称',
    },
    {
      component: 'Input',
      fieldName: 'real_name',
      label: '真实姓名',
    },
    {
      component: 'Input',
      fieldName: 'email',
      label: '邮箱',
    },
    {
      component: 'Input',
      fieldName: 'mobile',
      label: '手机',
    },
    {
      component: 'TreeSelect',
      componentProps: {
        checkStrictly: true,
        clearable: true,
        data: deptTreeData,
        placeholder: '选择部门',
      },
      fieldName: 'dept_id',
      label: '部门',
    },
    {
      component: 'Select',
      componentProps: {
        clearable: !lockRoles,
        disabled: lockRoles,
        multiple: true,
        options: roleOptions,
        placeholder: '选择角色',
      },
      fieldName: 'role_ids',
      label: '角色',
      rules: 'required',
    },
    {
      component: 'Select',
      componentProps: {
        clearable: true,
        multiple: true,
        options: postOptions,
        placeholder: '可选',
      },
      fieldName: 'post_ids',
      label: '岗位',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        disabled: lockRoles,
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
      component: 'Input',
      componentProps: {
        rows: 2,
        type: 'textarea',
      },
      fieldName: 'remark',
      label: '备注',
    },
  ];
}
