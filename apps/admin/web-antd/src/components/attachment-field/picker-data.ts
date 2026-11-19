import type { OnActionClickFn, VxeTableGridColumns } from '#/adapter/vxe-table';
import type { VbenFormSchema } from '#/adapter/form';

import type { AttachmentRow } from '#/features/system/api/attachment';

import { $t } from '#/locales';
import {
  buildCategoryTagOptions,
  formatCategoryLabel,
  formatFileSize,
  getAttachmentFileIcon,
  getAttachmentFileIconClass,
  getFileExtTagColor,
  isImageAttachment,
} from '#/features/system/views/resource/attachment/data';

export function usePickerGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('resource.attachment.columns.fileName'),
      },
      fieldName: 'file_name',
      label: $t('resource.attachment.columns.fileName'),
    },
  ];
}

export function usePickerColumns(options: {
  categoryTagOptions?: ReturnType<typeof buildCategoryTagOptions>;
  onActionClick: OnActionClickFn<AttachmentRow>;
  showCategoryColumn?: boolean;
}): VxeTableGridColumns<AttachmentRow> {
  const {
    categoryTagOptions = [],
    onActionClick,
    showCategoryColumn = true,
  } = options;

  const columns: VxeTableGridColumns<AttachmentRow> = [
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    {
      align: 'center',
      field: 'file_url',
      title: $t('resource.attachment.columns.preview'),
      width: 72,
      cellRender: {
        name: 'CellImage',
        attrs: {
          getFileIcon: (row: AttachmentRow) => getAttachmentFileIcon(row),
          getFileIconClass: (row: AttachmentRow) =>
            getAttachmentFileIconClass(row),
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
      minWidth: 200,
      sortable: true,
      title: $t('resource.attachment.columns.fileName'),
    },
  ];

  if (showCategoryColumn) {
    columns.push({
      align: 'center',
      cellRender: {
        attrs: {
          getLabel: (row: AttachmentRow) => formatCategoryLabel(row),
        },
        name: 'CellTag',
        options: [...categoryTagOptions],
      },
      field: 'category',
      minWidth: 96,
      title: $t('resource.attachment.columns.category'),
    });
  }

  columns.push(
    {
      align: 'center',
      cellRender: {
        attrs: {
          getColor: (row: AttachmentRow) => getFileExtTagColor(row.file_ext),
        },
        name: 'CellTag',
      },
      field: 'file_ext',
      title: $t('resource.attachment.columns.extension'),
      width: 96,
    },
    {
      align: 'right',
      field: 'file_size',
      formatter: ({ cellValue }) => formatFileSize(cellValue as number),
      sortable: true,
      title: $t('resource.attachment.columns.size'),
      width: 96,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'file_name',
          nameTitle: $t('resource.attachment.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'open',
            show: (row: AttachmentRow) => !!row.file_url,
            text: $t('resource.attachment.field.open'),
            type: 'link',
          },
          {
            code: 'copy',
            show: (row: AttachmentRow) => !!row.file_url,
            text: $t('resource.attachment.columns.copyLink'),
            type: 'link',
          },
        ],
      },
      field: 'actions',
      title: $t('resource.attachment.columns.link'),
      width: 150,
    },
    {
      field: 'created_at',
      formatter: 'formatDateTime',
      minWidth: 170,
      sortable: true,
      title: $t('resource.attachment.columns.uploadedAt'),
    },
  );

  return columns;
}
