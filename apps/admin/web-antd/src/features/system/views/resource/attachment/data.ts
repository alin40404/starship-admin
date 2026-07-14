import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn, MobileCrudListConfig } from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type {
  OnActionClickFn,
} from '#/adapter/vxe-table';

import type { AttachmentRow } from '#/features/system/api/attachment';
import { ATTACHMENT_PERMISSIONS } from '#/features/system/constants/attachment-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { $t } from '#/locales';

import {
  DICT_TYPE_CODE,
  dictItemLabel,
  mapDictItemOptions,
} from '#/features/system/views/platform/dict/i18n';

export type AttachmentAccessChecker = {
  can: (code: string) => boolean;
};

export function getAttachmentStorageOptions() {
  return [
    { label: $t('resource.attachment.storage.public'), value: 'public' },
    { label: $t('resource.attachment.storage.local'), value: 'local' },
    { label: $t('resource.attachment.storage.oss'), value: 'oss' },
    { label: $t('resource.attachment.storage.cos'), value: 'cos' },
    { label: $t('resource.attachment.storage.s3'), value: 's3' },
  ] as const;
}

export function getAttachmentStorageTagOptions() {
  return [
    { color: 'processing', label: $t('resource.attachment.storage.public'), value: 'public' },
    { color: 'default', label: $t('resource.attachment.storage.local'), value: 'local' },
    { color: 'orange', label: $t('resource.attachment.storage.oss'), value: 'oss' },
    { color: 'blue', label: $t('resource.attachment.storage.cos'), value: 'cos' },
    { color: 'purple', label: $t('resource.attachment.storage.s3'), value: 's3' },
  ] as const;
}

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
  return mapDictItemOptions(DICT_TYPE_CODE.attachmentCategory, items).map(
    (item, index) => ({
      color: CATEGORY_TAG_COLORS[index % CATEGORY_TAG_COLORS.length]!,
      label: item.label,
      value: item.value,
    }),
  );
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

export function formatStorageLabel(value: string | null | undefined) {
  if (!value) {
    return TABLE_CELL_EMPTY;
  }
  const storageKeyMap: Record<string, string> = {
    public: 'resource.attachment.storage.public',
    local: 'resource.attachment.storage.local',
    oss: 'resource.attachment.storage.oss',
    cos: 'resource.attachment.storage.cos',
    s3: 'resource.attachment.storage.s3',
  };
  const key = storageKeyMap[value];
  return key ? $t(key) : value;
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
  if (!row.category) {
    return TABLE_CELL_EMPTY;
  }
  return dictItemLabel(DICT_TYPE_CODE.attachmentCategory, {
    value: row.category,
    label: row.category_label ?? row.category,
  });
}

export const ALL_ATTACHMENT_CATEGORY_KEY = '__all__';

export function resolveAttachmentCategoryFilter(category?: string | null) {
  if (!category || category === ALL_ATTACHMENT_CATEGORY_KEY) {
    return undefined;
  }
  return category;
}

export function useGridFormSchema(): VbenFormSchema[] {
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
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('resource.attachment.fileExtPlaceholder'),
      },
      fieldName: 'file_ext',
      label: $t('resource.attachment.columns.extension'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...getAttachmentStorageOptions()],
        placeholder: $t('resource.attachment.storagePlaceholder'),
      },
      fieldName: 'storage',
      label: $t('resource.attachment.columns.storage'),
    },
  ];
}

export function useMobileAttachmentFilterSchema(
  categoryOptions: { label: string; value: string }[] = [],
): VbenFormSchema[] {
  return [
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: categoryOptions,
        placeholder: $t('system.shared.all'),
      },
      fieldName: 'category',
      label: $t('resource.attachment.categoryFilter'),
    },
    ...useGridFormSchema(),
  ];
}

export function buildAttachmentMobileCrudConfig(params: {
  categoryOptions?: { label: string; value: string }[];
  columns: CrudGridColumn<AttachmentRow>[];
  query: MobileCrudListConfig<AttachmentRow>['query'];
}): MobileCrudListConfig<AttachmentRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useMobileAttachmentFilterSchema(params.categoryOptions ?? []),
      keywordFields: ['file_name'],
      placeholder: $t('resource.attachment.searchFileName'),
      submitOnChange: true,
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
      minWidth: 180,
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
      minWidth: 100,
      mobile: {
        format: (row) => formatCategoryLabel(row),
      },
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
      align: 'center',
      cellRender: {
        attrs: {
          getColor: (row: AttachmentRow) => getMimeTagColor(row.mime_type),
        },
        name: 'CellTag',
      },
      field: 'mime_type',
      minWidth: 148,
      title: $t('resource.attachment.columns.mime'),
      visible: false,
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
        name: 'CellTag',
        options: [...getAttachmentStorageTagOptions()],
      },
      field: 'storage',
      mobile: {
        format: (row) => formatStorageLabel(row.storage),
      },
      sortable: true,
      title: $t('resource.attachment.columns.storage'),
      width: 108,
    },
    {
      align: 'left',
      field: 'admin_id',
      formatter: ({ row }) => formatUploader(row as AttachmentRow),
      minWidth: 100,
      title: $t('resource.attachment.columns.uploader'),
    },
    {
      field: 'created_at',
      formatter: 'formatDateTime',
      minWidth: 170,
      sortable: true,
      title: $t('resource.attachment.columns.uploadedAt'),
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
          {
            code: 'delete',
            danger: true,
            show: () => access.can(ATTACHMENT_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('resource.attachment.columns.actions'),
      width: 180,
    },
  );

  return columns;
}
