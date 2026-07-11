import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { UserGroupRow } from '#/api/system/user-group';
import { fetchAdminPage } from '#/api/system/admin';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { USER_GROUP_PERMISSIONS } from '#/constants/user-group-permissions';
import { $t } from '#/locales';

export type UserGroupAccessChecker = {
  can: (code: string) => boolean;
};

function getStatusSelectOptions() {
  return [
    { label: $t('system.shared.status.enable'), value: 1 },
    { label: $t('system.shared.status.disable'), value: 0 },
  ];
}

function getStatusRadioOptions() {
  return getStatusSelectOptions();
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('org.userGroup.namePlaceholder'),
      },
      fieldName: 'name',
      label: $t('org.shared.name'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('org.shared.codePlaceholder'),
      },
      fieldName: 'code',
      label: $t('org.shared.code'),
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

export function buildUserGroupMobileCrudConfig(params: {
  columns: CrudGridColumn<UserGroupRow>[];
  query: MobileCrudListConfig<UserGroupRow>['query'];
}): MobileCrudListConfig<UserGroupRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useGridFormSchema(),
      keywordFields: ['name', 'code'],
      placeholder: $t('org.shared.searchNameCode'),
    },
    selection: {
      enabled: true,
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<UserGroupRow>,
  access: UserGroupAccessChecker,
): CrudGridColumn<UserGroupRow>[] {
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
      field: 'name',
      minWidth: 140,
      sortable: true,
      title: $t('org.shared.name'),
    },
    {
      align: 'left',
      field: 'code',
      minWidth: 140,
      sortable: true,
      title: $t('org.shared.code'),
    },
    {
      align: 'center',
      field: 'admins_count',
      formatter: ({ cellValue }: { cellValue: UserGroupRow['admins_count'] }) =>
        cellValue === undefined || cellValue === null
          ? TABLE_CELL_EMPTY
          : String(cellValue),
      title: $t('org.userGroup.membersCount'),
      width: 120,
    },
    {
      field: 'sort',
      sortable: true,
      title: $t('org.shared.sort'),
      width: 80,
    },
    {
      field: 'status',
      formatter: ({ cellValue }: { cellValue: UserGroupRow['status'] }) =>
        cellValue === 1
          ? $t('system.shared.status.enable')
          : $t('system.shared.status.disable'),
      sortable: true,
      title: $t('org.shared.status'),
      width: 90,
    },
    {
      field: 'remark',
      formatter: ({ cellValue }: { cellValue: UserGroupRow['remark'] }) =>
        cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: $t('org.shared.remark'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('org.userGroup.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(USER_GROUP_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(USER_GROUP_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 140,
    },
  ] as CrudGridColumn<UserGroupRow>[];
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
      label: $t('org.shared.name'),
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'code',
      label: $t('org.shared.code'),
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
        placeholder: $t('org.userGroup.membersPlaceholder'),
        showSearch: true,
      },
      defaultValue: [],
      fieldName: 'admin_ids',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('org.userGroup.members'),
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', disabled, min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: $t('org.shared.sort'),
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        disabled,
        optionType: 'button',
        options: getStatusRadioOptions(),
      },
      defaultValue: 1,
      fieldName: 'status',
      label: $t('org.shared.status'),
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', disabled, rows: 2 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('org.shared.remark'),
    },
  ];
}
