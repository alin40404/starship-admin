import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { PostRow } from '#/features/system/api/post';

import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import { POST_PERMISSIONS } from '#/features/system/constants/post-permissions';
import { $t } from '#/locales';

export type PostAccessChecker = {
  can: (code: string) => boolean;
  canAny: (codes: string[]) => boolean;
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
        placeholder: $t('org.post.namePlaceholder'),
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

export function buildPostMobileCrudConfig(params: {
  columns: CrudGridColumn<PostRow>[];
  query: MobileCrudListConfig<PostRow>['query'];
}): MobileCrudListConfig<PostRow> {
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
  onActionClick: OnActionClickFn<PostRow>,
  onStatusBeforeChange: (newVal: number, row: PostRow) => Promise<boolean>,
  access: PostAccessChecker,
): CrudGridColumn<PostRow>[] {
  const canUpdate = access.can(POST_PERMISSIONS.UPDATE);

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
      field: 'sort',
      sortable: true,
      title: $t('org.shared.sort'),
      width: 80,
    },
    {
      field: 'status',
      sortable: true,
      title: $t('org.shared.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: PostRow) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({
            disabled: !canUpdate,
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      field: 'remark',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: $t('org.shared.remark'),
    },
    {
      field: 'updated_at',
      formatter: ({ row }: { row: PostRow }) =>
        row.updated_at ? formatDateTime(row.updated_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      sortable: true,
      title: $t('org.shared.updatedAt'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('org.post.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(POST_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(POST_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 140,
    },
  ] as CrudGridColumn<PostRow>[];
}

export function buildPostFormSchema(readonly = false): VbenFormSchema[] {
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
