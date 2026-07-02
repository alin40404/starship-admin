import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { AttachmentRow } from '#/api/system/attachment';
import { ATTACHMENT_PERMISSIONS } from '#/constants/attachment-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';

export type AttachmentAccessChecker = {
  can: (code: string) => boolean;
};

export const ATTACHMENT_STORAGE_OPTIONS = [
  { label: '公共存储', value: 'public' },
  { label: '本地存储', value: 'local' },
] as const;

const STORAGE_LABEL_MAP = Object.fromEntries(
  ATTACHMENT_STORAGE_OPTIONS.map((item) => [item.value, item.label]),
) as Record<string, string>;

export function formatStorageLabel(value: string | null | undefined) {
  if (!value) {
    return TABLE_CELL_EMPTY;
  }
  return STORAGE_LABEL_MAP[value] ?? value;
}

export function isImageAttachment(row: AttachmentRow) {
  const mime = row.mime_type ?? '';
  if (mime.startsWith('image/')) {
    return true;
  }
  const ext = (row.file_ext ?? '').toLowerCase();
  return ['bmp', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'webp'].includes(ext);
}

export function formatFileSize(bytes: number) {
  if (!bytes || bytes < 0) {
    return TABLE_CELL_EMPTY;
  }
  if (bytes < 1024) {
    return `${bytes} B`;
  }
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`;
  }
  if (bytes < 1024 * 1024 * 1024) {
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
  }
  return `${(bytes / 1024 / 1024 / 1024).toFixed(1)} GB`;
}

export function formatUploader(row: AttachmentRow) {
  const admin = row.admin;
  if (!admin) {
    return TABLE_CELL_EMPTY;
  }
  return admin.nickname?.trim() || admin.username || TABLE_CELL_EMPTY;
}

export function formatCategoryLabel(row: AttachmentRow) {
  return row.category_label ?? row.category ?? TABLE_CELL_EMPTY;
}

export function useGridFormSchema(
  categoryOptions: { label: string; value: string }[] = [],
): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '文件名' },
      fieldName: 'file_name',
      label: '文件名',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '扩展名，如 png' },
      fieldName: 'file_ext',
      label: '扩展名',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: categoryOptions,
        placeholder: '附件分类',
      },
      fieldName: 'category',
      label: '分类',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...ATTACHMENT_STORAGE_OPTIONS],
        placeholder: '存储驱动',
      },
      fieldName: 'storage',
      label: '存储',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<AttachmentRow>,
  access: AttachmentAccessChecker,
): VxeTableGridColumns<AttachmentRow> {
  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    {
      align: 'center',
      field: 'file_url',
      title: '预览',
      width: 72,
      cellRender: {
        name: 'CellImage',
        attrs: {
          getSrc: (row: AttachmentRow) =>
            isImageAttachment(row) ? (row.file_url ?? '') : '',
        },
        props: {
          height: 40,
          preview: true,
          width: 40,
        },
      },
    },
    {
      align: 'left',
      field: 'file_name',
      minWidth: 180,
      sortable: true,
      title: '文件名',
    },
    {
      align: 'left',
      field: 'category',
      formatter: ({ row }) => formatCategoryLabel(row as AttachmentRow),
      minWidth: 100,
      title: '分类',
    },
    {
      field: 'file_ext',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      title: '扩展名',
      width: 88,
    },
    {
      align: 'left',
      field: 'mime_type',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 140,
      title: 'MIME',
    },
    {
      align: 'right',
      field: 'file_size',
      formatter: ({ cellValue }) => formatFileSize(cellValue as number),
      sortable: true,
      title: '大小',
      width: 96,
    },
    {
      field: 'storage',
      formatter: ({ cellValue }) => formatStorageLabel(cellValue as string),
      sortable: true,
      title: '存储',
      width: 96,
    },
    {
      align: 'left',
      field: 'admin_id',
      formatter: ({ row }) => formatUploader(row as AttachmentRow),
      minWidth: 100,
      title: '上传者',
    },
    {
      field: 'created_at',
      minWidth: 170,
      sortable: true,
      title: '上传时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'file_name',
          nameTitle: '附件',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'open',
            show: (row: AttachmentRow) => !!row.file_url,
            text: '打开',
            type: 'link',
          },
          {
            code: 'copy',
            show: (row: AttachmentRow) => !!row.file_url,
            text: '复制链接',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(ATTACHMENT_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 180,
    },
  ];
}
