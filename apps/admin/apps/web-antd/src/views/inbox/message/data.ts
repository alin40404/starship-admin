import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { InboxMessageRow } from '#/api/inbox';

import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import { INBOX_MESSAGE_TYPE_OPTIONS } from '#/constants/inbox-permissions';
import { $t } from '#/locales';

function getTypeLabel(type: string) {
  const option = INBOX_MESSAGE_TYPE_OPTIONS.find((item) => item.value === type);
  return option ? $t(option.labelKey) : type;
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('inbox.searchPlaceholder'),
      },
      fieldName: 'keyword',
      label: $t('monitor.shared.keyword'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: INBOX_MESSAGE_TYPE_OPTIONS.map((item) => ({
          label: $t(item.labelKey),
          value: item.value,
        })),
      },
      fieldName: 'type',
      label: $t('inbox.columns.type'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [
          { label: $t('inbox.readStatus.unread'), value: 0 },
          { label: $t('inbox.readStatus.read'), value: 1 },
        ],
      },
      fieldName: 'is_read',
      label: $t('inbox.columns.status'),
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<InboxMessageRow>,
): CrudGridColumn<InboxMessageRow>[] {
  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    {
      align: 'left',
      field: 'title',
      minWidth: 220,
      title: $t('inbox.columns.title'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          getColor: (row: InboxMessageRow) =>
            INBOX_MESSAGE_TYPE_OPTIONS.find((item) => item.value === row.type)
              ?.color ?? 'default',
          getLabel: (row: InboxMessageRow) => getTypeLabel(row.type),
        },
        name: 'CellTag',
      },
      field: 'type',
      title: $t('inbox.columns.type'),
      width: 120,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          getColor: (row: InboxMessageRow) =>
            row.is_read === 1 ? 'default' : 'processing',
          getLabel: (row: InboxMessageRow) =>
            row.is_read === 1
              ? $t('inbox.readStatus.read')
              : $t('inbox.readStatus.unread'),
        },
        name: 'CellTag',
      },
      field: 'is_read',
      title: $t('inbox.columns.status'),
      width: 100,
    },
    {
      align: 'center',
      field: 'created_at',
      formatter: ({ cellValue }) => formatDateTime(cellValue) ?? TABLE_CELL_EMPTY,
      title: $t('inbox.columns.createdAt'),
      width: 170,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'title',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            text: $t('system.shared.view'),
          },
          {
            code: 'markRead',
            disabled: (row: InboxMessageRow) => row.is_read === 1,
            text: $t('inbox.readStatus.read'),
          },
          {
            code: 'delete',
            text: $t('crud.delete'),
          },
        ],
      },
      field: 'operation',
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 180,
    },
  ] as CrudGridColumn<InboxMessageRow>[];
}
