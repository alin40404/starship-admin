import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn, MobileCrudListConfig } from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type {
  OnActionClickFn,
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
  { label: '阿里云 OSS', value: 'oss' },
  { label: '腾讯云 COS', value: 'cos' },
  { label: 'AWS S3', value: 's3' },
] as const;

export const ATTACHMENT_STORAGE_TAG_OPTIONS = [
  { color: 'processing', label: '公共存储', value: 'public' },
  { color: 'default', label: '本地存储', value: 'local' },
  { color: 'orange', label: '阿里云 OSS', value: 'oss' },
  { color: 'blue', label: '腾讯云 COS', value: 'cos' },
  { color: 'purple', label: 'AWS S3', value: 's3' },
] as const;

const CATEGORY_TAG_COLORS = [
  'blue',
  'cyan',
  'geekblue',
  'purple',
  'magenta',
  'volcano',
  'orange',
  'gold',
  'lime',
  'green',
] as const;

export type AttachmentTagOption = {
  color: string;
  label: string;
  value: string;
};

export function buildCategoryTagOptions(
  items: { label: string; value: string }[] = [],
): AttachmentTagOption[] {
  return items.map((item, index) => ({
    color: CATEGORY_TAG_COLORS[index % CATEGORY_TAG_COLORS.length]!,
    label: item.label,
    value: item.value,
  }));
}

export function getFileExtTagColor(ext: string | null | undefined) {
  if (!ext) {
    return 'default';
  }
  const normalized = ext.toLowerCase();
  if (['bmp', 'gif', 'ico', 'jpeg', 'jpg', 'png', 'svg', 'webp'].includes(normalized)) {
    return 'green';
  }
  if (['avi', 'mkv', 'mov', 'mp4', 'webm'].includes(normalized)) {
    return 'purple';
  }
  if (
    ['doc', 'docx', 'md', 'pdf', 'ppt', 'pptx', 'txt', 'xls', 'xlsx'].includes(
      normalized,
    )
  ) {
    return 'blue';
  }
  if (['7z', 'gz', 'rar', 'tar', 'zip'].includes(normalized)) {
    return 'orange';
  }
  return 'default';
}

export function getMimeTagColor(mime: string | null | undefined) {
  if (!mime) {
    return 'default';
  }
  if (mime.startsWith('image/')) {
    return 'green';
  }
  if (mime.startsWith('video/')) {
    return 'purple';
  }
  if (mime.startsWith('audio/')) {
    return 'magenta';
  }
  if (
    mime.includes('pdf') ||
    mime.includes('document') ||
    mime.includes('word') ||
    mime.includes('sheet') ||
    mime.includes('presentation')
  ) {
    return 'blue';
  }
  if (mime.startsWith('text/')) {
    return 'cyan';
  }
  return 'default';
}

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

/** 非图片附件按扩展名 / MIME 返回预览图标 */
export function getAttachmentFileIcon(
  row: Pick<AttachmentRow, 'file_ext' | 'mime_type'>,
) {
  const ext = (row.file_ext ?? '').toLowerCase();
  const mime = (row.mime_type ?? '').toLowerCase();

  if (
    mime.startsWith('video/') ||
    ['avi', 'mkv', 'mov', 'mp4', 'webm'].includes(ext)
  ) {
    return 'lucide:file-video';
  }
  if (
    mime.startsWith('audio/') ||
    ['aac', 'flac', 'mp3', 'ogg', 'wav'].includes(ext)
  ) {
    return 'lucide:file-audio';
  }
  if (ext === 'pdf' || mime.includes('pdf')) {
    return 'lucide:file-text';
  }
  if (
    ['doc', 'docx'].includes(ext) ||
    mime.includes('word') ||
    mime.includes('document')
  ) {
    return 'lucide:file-text';
  }
  if (
    ['csv', 'xls', 'xlsx'].includes(ext) ||
    mime.includes('sheet') ||
    mime.includes('excel')
  ) {
    return 'lucide:file-spreadsheet';
  }
  if (['ppt', 'pptx'].includes(ext) || mime.includes('presentation')) {
    return 'lucide:presentation';
  }
  if (
    ['7z', 'gz', 'rar', 'tar', 'zip'].includes(ext) ||
    mime.includes('zip') ||
    mime.includes('compressed')
  ) {
    return 'lucide:file-archive';
  }
  if (
    [
      'css',
      'go',
      'html',
      'java',
      'js',
      'json',
      'jsx',
      'php',
      'py',
      'rs',
      'ts',
      'tsx',
      'vue',
      'xml',
    ].includes(ext)
  ) {
    return 'lucide:file-code';
  }
  if (['log', 'md', 'txt'].includes(ext) || mime.startsWith('text/')) {
    return 'lucide:file-text';
  }
  return 'lucide:file';
}

export function getAttachmentFileIconClass(
  row: Pick<AttachmentRow, 'file_ext' | 'mime_type'>,
) {
  const ext = (row.file_ext ?? '').toLowerCase();
  const mime = (row.mime_type ?? '').toLowerCase();

  if (
    mime.startsWith('video/') ||
    ['avi', 'mkv', 'mov', 'mp4', 'webm'].includes(ext)
  ) {
    return 'text-purple-500';
  }
  if (mime.startsWith('audio/')) {
    return 'text-pink-500';
  }
  if (ext === 'pdf' || mime.includes('pdf')) {
    return 'text-red-500';
  }
  if (['doc', 'docx'].includes(ext) || mime.includes('word')) {
    return 'text-blue-600';
  }
  if (
    ['csv', 'xls', 'xlsx'].includes(ext) ||
    mime.includes('sheet') ||
    mime.includes('excel')
  ) {
    return 'text-green-600';
  }
  if (['ppt', 'pptx'].includes(ext) || mime.includes('presentation')) {
    return 'text-orange-500';
  }
  if (['7z', 'gz', 'rar', 'tar', 'zip'].includes(ext)) {
    return 'text-amber-600';
  }
  return 'text-muted-foreground';
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

export function useGridFormSchema(): VbenFormSchema[] {
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
        options: [...ATTACHMENT_STORAGE_OPTIONS],
        placeholder: '存储驱动',
      },
      fieldName: 'storage',
      label: '存储',
    },
  ];
}

export function buildAttachmentMobileCrudConfig(params: {
  columns: CrudGridColumn<AttachmentRow>[];
  query: MobileCrudListConfig<AttachmentRow>['query'];
}): MobileCrudListConfig<AttachmentRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useGridFormSchema(),
      keywordFields: ['file_name'],
      placeholder: '搜索文件名',
    },
    selection: {
      enabled: true,
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<AttachmentRow>,
  access: AttachmentAccessChecker,
  options: {
    categoryTagOptions?: AttachmentTagOption[];
    showCategoryColumn?: boolean;
  } = {},
): CrudGridColumn<AttachmentRow>[] {
  const { categoryTagOptions = [], showCategoryColumn = true } = options;

  const columns: CrudGridColumn<AttachmentRow>[] = [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', sortable: true, title: 'ID', visible: false, width: 72 },
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
      minWidth: 180,
      sortable: true,
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
      minWidth: 100,
      mobile: {
        format: (row) => formatCategoryLabel(row),
      },
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
      align: 'center',
      cellRender: {
        attrs: {
          getColor: (row: AttachmentRow) => getMimeTagColor(row.mime_type),
        },
        name: 'CellTag',
      },
      field: 'mime_type',
      minWidth: 148,
      title: 'MIME',
      visible: false,
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
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...ATTACHMENT_STORAGE_TAG_OPTIONS],
      },
      field: 'storage',
      mobile: {
        format: (row) => formatStorageLabel(row.storage),
      },
      sortable: true,
      title: '存储',
      width: 108,
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
      formatter: 'formatDateTime',
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
  );

  return columns;
}
