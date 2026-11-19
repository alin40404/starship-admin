import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { MemberRow } from '#/features/member/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { MEMBER_PERMISSIONS } from '#/features/member/constants/member-permissions';
import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

export type MemberAccessChecker = {
  can: (code: string) => boolean;
};

function getStatusSelectOptions() {
  return [
    { label: $t('system.shared.status.enable'), value: 1 },
    { label: $t('system.shared.status.disable'), value: 0 },
  ];
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '用户名',
      },
      fieldName: 'username',
      inKeywordSearch: true,
      label: '用户名',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '手机号',
      },
      fieldName: 'mobile',
      inKeywordSearch: true,
      label: '手机号',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: getStatusSelectOptions(),
        placeholder: $t('org.shared.statusPlaceholder'),
      },
      fieldName: 'status',
      label: $t('org.shared.status'),
    },
  ];
}

export function buildMemberFormSchema(isEdit: boolean): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      fieldName: 'username',
      label: '用户名',
      rules: 'required',
    },
    {
      component: 'InputPassword',
      componentProps: {
        class: 'w-full',
        placeholder: isEdit ? '留空不改' : undefined,
      },
      fieldName: 'password',
      help: isEdit ? '留空不改' : undefined,
      label: '密码',
      rules: isEdit ? undefined : 'required',
    },
    {
      component: 'Input',
      fieldName: 'nickname',
      label: '昵称',
    },
    {
      component: 'Input',
      fieldName: 'email',
      label: '邮箱',
    },
    {
      component: 'Input',
      fieldName: 'mobile',
      label: '手机号',
    },
    {
      component: 'Switch',
      componentProps: {
        checkedChildren: $t('system.shared.status.enable'),
        checkedValue: 1,
        class: 'w-auto',
        unCheckedChildren: $t('system.shared.status.disable'),
        unCheckedValue: 0,
      },
      defaultValue: 1,
      fieldName: 'status',
      label: $t('org.shared.status'),
    },
    {
      component: 'Textarea',
      componentProps: { rows: 4 },
      fieldName: 'remark',
      label: $t('org.shared.remark'),
      formItemClass: 'col-span-full',
    },
  ];
}

export function buildMemberMobileCrudConfig(params: {
  columns: CrudGridColumn<MemberRow>[];
  query: MobileCrudListConfig<MemberRow>['query'];
}): MobileCrudListConfig<MemberRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      keywordFields: ['username', 'nickname', 'mobile', 'email'],
      placeholder: '搜索用户名/昵称/手机/邮箱',
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<MemberRow>,
  access: MemberAccessChecker,
  onStatusBeforeChange: (newVal: number, row: MemberRow) => Promise<boolean>,
): CrudGridColumn<MemberRow>[] {
  const canStatus = access.can(MEMBER_PERMISSIONS.STATUS);

  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    {
      align: 'left',
      field: 'username',
      minWidth: 120,
      sortable: true,
      title: '用户名',
    },
    {
      field: 'nickname',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '昵称',
    },
    {
      field: 'mobile',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '手机号',
    },
    {
      field: 'email',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '邮箱',
    },
    {
      field: 'status',
      sortable: true,
      title: $t('org.shared.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: MemberRow) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({
            disabled: !canStatus,
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      field: 'last_login_at',
      formatter: ({ row }: { row: MemberRow }) =>
        row.last_login_at
          ? formatDateTime(row.last_login_at)
          : TABLE_CELL_EMPTY,
      minWidth: 170,
      sortable: true,
      title: '最后登录',
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: MemberRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      sortable: true,
      title: '创建时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'username',
          nameTitle: '会员',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(MEMBER_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            show: () => access.can(MEMBER_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      field: 'operation',
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 140,
    },
  ];
}
