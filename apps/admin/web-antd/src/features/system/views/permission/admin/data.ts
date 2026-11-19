import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type {
  OnActionClickFn,
} from '#/adapter/vxe-table';

import type { AdminRow } from '#/features/system/api/admin';
import type { DeptNode } from '#/features/system/api/department';

import { z } from '@vben/common-ui';
import { preferences } from '@vben/preferences';
import { formatDateTime } from '@vben/utils';

import { fetchDepartmentOptionsTree } from '#/features/system/api/department';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { ADMIN_PERMISSIONS } from '#/features/system/constants/admin-permissions';
import { $t } from '#/locales';

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

function formatAdminRoles(row: AdminRow) {
  return (row.roles ?? []).map((role) => role.name).join('、') || TABLE_CELL_EMPTY;
}

export function buildAdminMobileCrudConfig(params: {
  columns: CrudGridColumn<AdminRow>[];
  query: MobileCrudListConfig<AdminRow>['query'];
}): MobileCrudListConfig<AdminRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    defaultSort: { field: 'updated_at', order: 'desc' },
    query: params.query,
    rowKey: 'id',
    search: {
      fieldMappingTime: [
        [
          'updatedTime',
          ['updated_start', 'updated_end'],
          ['YYYY-MM-DD', 'YYYY-MM-DD'],
        ],
      ],
      placeholder: $t('permission.admin.searchKeyword'),
    },
    selection: {
      checkable: (row) => row.is_super !== 1,
      enabled: true,
    },
  });
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('permission.admin.searchUsername'),
      },
      fieldName: 'username',
      hideOnMobile: true,
      inKeywordSearch: true,
      label: $t('permission.admin.username'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('permission.admin.searchName'),
      },
      fieldName: 'name',
      hideOnMobile: true,
      inKeywordSearch: true,
      label: $t('permission.admin.name'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('permission.admin.searchEmail'),
      },
      fieldName: 'email',
      label: $t('permission.admin.email'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('permission.admin.searchMobile'),
      },
      fieldName: 'mobile',
      label: $t('permission.admin.mobile'),
    },
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: async () => mapDeptForTreeSelect(await fetchDepartmentOptionsTree()),
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        placeholder: $t('permission.admin.searchDept'),
        /** 关闭树连接线/叶节点装饰，避免下拉项左侧出现圆点等图标感样式 */
        treeIcon: false,
        treeLine: false,
      },
      fieldName: 'dept_id',
      label: $t('permission.admin.dept'),
    },
    {
      component: 'RangePicker',
      componentProps: {
        allowClear: true,
        class: 'w-full',
      },
      fieldName: 'updatedTime',
      inFilterPanel: true,
      label: $t('permission.admin.updatedAt'),
    },
  ];
}

export type AdminAccessChecker = {
  can: (code: string) => boolean;
};

export function useColumns(
  onActionClick: OnActionClickFn<AdminRow>,
  onStatusBeforeChange: (newVal: number, row: AdminRow) => Promise<boolean>,
  access: AdminAccessChecker,
): CrudGridColumn<AdminRow>[] {
  const canStatus = access.can(ADMIN_PERMISSIONS.STATUS);

  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', sortable: true, title: 'ID', width: 70 },
    {
      field: 'username',
      minWidth: 120,
      sortable: true,
      title: $t('permission.admin.username'),
    },
    {
      field: 'real_name',
      minWidth: 120,
      sortable: true,
      title: $t('permission.admin.realName'),
      visible: false,
    },
    {
      field: 'nickname',
      minWidth: 100,
      sortable: true,
      title: $t('permission.admin.nickname'),
    },
    {
      field: 'avatar',
      title: $t('permission.admin.avatar'),
      width: 90,
      cellRender: {
        attrs: {
          getSrc: (row: AdminRow) => row.avatar || preferences.app.defaultAvatar,
        },
        name: 'CellImage',
        props: {
          fallback: preferences.app.defaultAvatar,
          height: 32,
          preview: true,
          width: 32,
        },
      },
    },
    { field: 'mobile', minWidth: 130, sortable: true, title: $t('permission.admin.mobile') },
    {
      field: 'email',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 180,
      sortable: true,
      title: $t('permission.admin.email'),
    },
    {
      field: 'dept_id',
      formatter: ({ row }: { row: AdminRow }) =>
        row.department?.name ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      sortable: true,
      title: $t('permission.admin.dept'),
    },
    {
      field: 'roles',
      formatter: ({ row }: { row: AdminRow }) => formatAdminRoles(row),
      minWidth: 160,
      sortable: false,
      title: $t('permission.admin.roles'),
    },
    {
      field: 'updated_at',
      formatter: ({ row }: { row: AdminRow }) =>
        row.updated_at ? formatDateTime(row.updated_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      sortable: true,
      title: $t('permission.admin.updatedAt'),
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: AdminRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      sortable: true,
      title: $t('permission.admin.createdAt'),
      visible: false,
    },
    {
      field: 'status',
      sortable: true,
      title: $t('permission.admin.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: AdminRow) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: (row: AdminRow) => ({
            disabled: row.is_super === 1 || !canStatus,
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
          nameTitle: $t('permission.admin.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: (row: AdminRow) =>
              row.is_super !== 1 &&
              access.can(ADMIN_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: (row: AdminRow) =>
              row.is_super !== 1 &&
              access.can(ADMIN_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 160,
    },
  ] as CrudGridColumn<AdminRow>[];
}

export function buildAdminFormSchema(params: {
  deptTreeData: { label: string; value: number; children?: any[] }[];
  lockRoles?: boolean;
  roleOptions: { label: string; value: number }[];
  postOptions: { label: string; value: number }[];
  onAvatarUpload: (options: any) => Promise<void>;
  onAvatarChange: (event: any) => void;
  isEdit: boolean;
}): VbenFormSchema[] {
  const {
    deptTreeData,
    lockRoles = false,
    roleOptions,
    postOptions,
    onAvatarUpload,
    onAvatarChange,
    isEdit,
  } = params;
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: isEdit,
      },
      fieldName: 'username',
      label: $t('permission.admin.username'),
      rules: 'required',
    },
    {
      component: 'InputPassword',
      componentProps: {
        class: 'w-full',
        placeholder: isEdit
          ? $t('permission.admin.passwordKeepEmpty')
          : undefined,
      },
      fieldName: 'password',
      label: isEdit
        ? $t('permission.admin.newPassword')
        : $t('permission.admin.password'),
      rules: isEdit ? undefined : 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
      },
      fieldName: 'nickname',
      label: $t('permission.admin.nickname'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
      },
      fieldName: 'real_name',
      label: $t('permission.admin.realName'),
    },
    {
      component: 'PictureUploadField',
      componentProps: {
        customRequest: onAvatarUpload,
        handleChange: onAvatarChange,
        maxCount: 1,
      },
      fieldName: 'avatar',
      formItemClass: 'picture-upload-form-item',
      label: $t('permission.admin.avatar'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
      },
      fieldName: 'email',
      label: $t('permission.admin.email'),
      rules: z
        .string({ required_error: $t('permission.admin.emailRequired') })
        .min(1, { message: $t('permission.admin.emailRequired') })
        .email($t('permission.admin.emailInvalid')),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
      },
      fieldName: 'mobile',
      label: $t('permission.admin.mobile'),
      rules: z
        .string({ required_error: $t('permission.admin.mobileRequired') })
        .regex(/^1[3-9]\d{9}$/, $t('permission.admin.mobileInvalid')),
    },
    {
      component: 'TreeSelect',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        fieldNames: { children: 'children', label: 'label', value: 'value' },
        placeholder: $t('permission.admin.selectDept'),
        showSearch: true,
        treeData: deptTreeData,
        treeDefaultExpandAll: true,
        /** 之前 treeLine: true 会启用连线与叶节点装饰；关闭后下拉项仅保留文字 */
        treeIcon: false,
        treeLine: false,
      },
      fieldName: 'dept_id',
      label: $t('permission.admin.dept'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: !lockRoles,
        class: 'w-full',
        disabled: lockRoles,
        mode: 'multiple',
        options: roleOptions,
        placeholder: $t('permission.admin.selectRole'),
      },
      fieldName: 'role_ids',
      label: $t('permission.admin.roles'),
      rules: lockRoles ? undefined : 'selectRequired',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        mode: 'multiple',
        options: postOptions,
        placeholder: $t('system.shared.optional'),
      },
      fieldName: 'post_ids',
      label: $t('permission.admin.post'),
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        disabled: lockRoles,
        optionType: 'button',
        options: [
          { label: $t('system.shared.status.enable'), value: 1 },
          { label: $t('system.shared.status.disable'), value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      label: $t('permission.admin.status'),
    },
    {
      component: 'Textarea',
      componentProps: {
        class: 'w-full',
        rows: 2,
      },
      fieldName: 'remark',
      formItemClass: 'w-full items-start',
      label: $t('permission.admin.remark'),
    },
  ];
}
