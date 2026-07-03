import type { VbenFormSchema } from '#/adapter/form';
import type { VxeTableGridColumns } from '#/adapter/vxe-table';

import type { AttachmentRow } from '#/api/system/attachment';

import {
  buildCategoryTagOptions,
  formatCategoryLabel,
  formatFileSize,
  getAttachmentFileIcon,
  getAttachmentFileIconClass,
  getFileExtTagColor,
  isImageAttachment,
} from '#/views/resource/attachment/data';

export function usePickerGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '文件名' },
      fieldName: 'file_name',
      label: '文件名',
    },
  ];
}

export function usePickerColumns(options: {
  categoryTagOptions?: ReturnType<typeof buildCategoryTagOptions>;
  showCategoryColumn?: boolean;
}): VxeTableGridColumns<AttachmentRow> {
  const { categoryTagOptions = [], showCategoryColumn = true } = options;

  const columns: VxeTableGridColumns<AttachmentRow> = [
    { field: 'id', title: 'ID', width: 72 },
    {
      align: 'center',
      field: 'file_url',
      title: '预览',
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
      title: '文件名',
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
      title: '分类',
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
      title: '扩展名',
      width: 96,
    },
    {
      align: 'right',
      field: 'file_size',
      formatter: ({ cellValue }) => formatFileSize(cellValue as number),
      title: '大小',
      width: 96,
    },
    {
      align: 'left',
      field: 'file_url',
      minWidth: 180,
      title: '链接',
    },
  );

  return columns;
}
