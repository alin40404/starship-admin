import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { UserGroupRow } from '#/api/system/user-group';
import { fetchAdminPage } from '#/api/system/admin';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { USER_GROUP_PERMISSIONS } from '#/constants/user-group-permissions';

export type UserGroupAccessChecker = {
  can: (code: string) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '用户组名称' },
      fieldName: 'name',
      label: '名称',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '编码' },
      fieldName: 'code',
      label: '编码',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [
          { label: '启用', value: 1 },
          { label: '禁用', value: 0 },
        ],
        placeholder: '状态',
      },
      fieldName: 'status',
      label: '状态',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<UserGroupRow>,
  access: UserGroupAccessChecker,
): VxeTableGridColumns {
  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    { field: 'name', minWidth: 140, sortable: true, title: '名称' },
    { field: 'code', minWidth: 140, sortable: true, title: '编码' },
    {
      field: 'admins_count',
      formatter: ({ cellValue }) =>
        cellValue === undefined || cellValue === null
          ? TABLE_CELL_EMPTY
          : String(cellValue),
      title: '成员数',
      width: 90,
    },
    { field: 'sort', sortable: true, title: '排序', width: 80 },
    {
      field: 'status',
      formatter: ({ cellValue }) => (cellValue === 1 ? '启用' : '禁用'),
      sortable: true,
      title: '状态',
      width: 90,
    },
    {
      field: 'remark',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '备注',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '用户组',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(USER_GROUP_PERMISSIONS.UPDATE),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(USER_GROUP_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 140,
    },
  ];
}

async function loadAdminOptions() {
  const page = await fetchAdminPage({ page: 1, per_page: 500 });
  return page.data.map((a) => ({
    label: `${a.nickname || a.username} (${a.username})`,
    value: a.id,
  }));
}

export function buildUserGroupFormSchema(readonly = false): VbenFormSchema[] {
  const disabled = readonly;
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'name',
      label: '名称',
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'code',
      label: '编码',
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'ApiSelect',
      componentProps: {
        api: loadAdminOptions,
        class: 'w-full',
        disabled,
        mode: 'multiple',
        optionFilterProp: 'label',
        placeholder: '选择组成员',
        showSearch: true,
      },
      defaultValue: [],
      fieldName: 'admin_ids',
      formItemClass: 'col-span-full w-full items-start',
      label: '组成员',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', disabled, min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: '排序',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        disabled,
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
      componentProps: { class: 'w-full', disabled, rows: 2 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: '备注',
    },
  ];
}
