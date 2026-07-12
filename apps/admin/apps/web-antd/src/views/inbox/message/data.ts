import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { InboxMessageRow } from '#/api/inbox';

import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import {
  INBOX_CATEGORY_OPTIONS,
  INBOX_MESSAGE_TYPE_OPTIONS,
  INBOX_PRIORITY_OPTIONS,
  resolveInboxCategory,
} from '#/constants/inbox-permissions';
import { $t } from '#/locales';

function getTypeLabel(type: string) {
  const option = INBOX_MESSAGE_TYPE_OPTIONS.find((item) => item.value === type);
  return option ? $t(option.labelKey) : type;
}

function getCategoryLabel(row: InboxMessageRow) {
  const category = resolveInboxCategory(row.category, row.type);
  const option = INBOX_CATEGORY_OPTIONS.find((item) => item.value === category);
  return option ? $t(option.labelKey) : category;
}

function getPriorityLabel(priority: string) {
  const option = INBOX_PRIORITY_OPTIONS.find((item) => item.value === priority);
  return option ? $t(option.labelKey) : priority;
}

function getSenderLabel(row: InboxMessageRow) {
  if (row.sender?.real_name || row.sender?.username) {
    return row.sender.real_name || row.sender.username;
  }
  const category = resolveInboxCategory(row.category, row.type);
  if (category === 'alert' || category === 'notice') {
    return $t('inbox.sender.system');
  }
  return TABLE_CELL_EMPTY;
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
        options: INBOX_CATEGORY_OPTIONS.map((item) => ({
          label: $t(item.labelKey),
          value: item.value,
        })),
      },
      fieldName: 'category',
      label: $t('inbox.columns.category'),
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
            INBOX_CATEGORY_OPTIONS.find(
              (item) => item.value === resolveInboxCategory(row.category, row.type),
            )?.color ?? 'default',
          getLabel: (row: InboxMessageRow) => getCategoryLabel(row),
        },
        name: 'CellTag',
      },
      field: 'category',
      title: $t('inbox.columns.category'),
      width: 110,
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
      width: 110,
    },
    {
      align: 'center',
      formatter: ({ row }: { row: InboxMessageRow }) => getSenderLabel(row),
      field: 'sender_id',
      title: $t('inbox.columns.sender'),
      width: 120,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          getColor: (row: InboxMessageRow) =>
            INBOX_PRIORITY_OPTIONS.find((item) => item.value === row.priority)
              ?.color ?? 'default',
          getLabel: (row: InboxMessageRow) => getPriorityLabel(row.priority),
        },
        name: 'CellTag',
      },
      field: 'priority',
      title: $t('inbox.columns.priority'),
      width: 100,
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
      formatter: ({ cellValue }: { cellValue: string }) =>
        formatDateTime(cellValue) ?? TABLE_CELL_EMPTY,
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
            text: $t('common.delete'),
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
