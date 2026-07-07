import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface AttachmentCategoryOption {
  value: string;
  label: string;
  sort: number;
}

export interface AttachmentAdminBrief {
  id: number;
  nickname: string | null;
  username: string;
}

export interface AttachmentRow {
  id: number;
  file_name: string;
  file_path: string;
  file_url: string | null;
  file_ext: string | null;
  mime_type: string | null;
  file_size: number;
  storage: string;
  category: string | null;
  category_label?: string | null;
  hash: string | null;
  admin_id: number | null;
  admin?: AttachmentAdminBrief | null;
  created_at: string;
  updated_at: string;
}

export interface AttachmentPageQuery {
  page: number;
  per_page: number;
  keyword?: string;
  file_name?: string;
  file_ext?: string;
  mime_type?: string;
  category?: string;
  storage?: string;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export function fetchAttachmentCategories() {
  return requestClient.get<AttachmentCategoryOption[]>(
    '/system/attachments/categories',
  );
}

export function fetchAttachmentPage(params: AttachmentPageQuery) {
  return requestClient.get<LaravelPaginate<AttachmentRow>>(
    '/system/attachments',
    { params },
  );
}

/** 表单附件选择弹窗：仅需登录，不要求附件管理查询权限 */
export function fetchAttachmentPickerCategories() {
  return requestClient.get<AttachmentCategoryOption[]>(
    '/system/attachments/picker/categories',
  );
}

export function fetchAttachmentPickerPage(params: AttachmentPageQuery) {
  return requestClient.get<LaravelPaginate<AttachmentRow>>(
    '/system/attachments/picker',
    { params },
  );
}

export async function uploadAttachment(file: any) {
  const rawFile = file?.originFileObj ?? file;
  if (!(rawFile instanceof Blob)) {
    throw new Error('无效的上传文件');
  }
  const formData = new FormData();
  formData.append('file', rawFile, (rawFile as File).name ?? 'upload-file');
  return requestClient.post<AttachmentRow>('/system/attachments', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
}

export function deleteAttachment(id: number) {
  return requestClient.delete(`/system/attachments/${id}`);
}

export function batchDeleteAttachments(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/system/attachments/batch-destroy',
    { ids },
  );
}
