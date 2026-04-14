import { requestClient } from '#/api/request';

export interface AttachmentRow {
  id: number;
  file_name: string;
  file_path: string;
  file_url: string | null;
  file_ext: string | null;
  mime_type: string | null;
  file_size: number;
  storage: string;
  admin_id: number | null;
  created_at: string;
  updated_at: string;
}

export async function uploadAttachment(file: File) {
  const formData = new FormData();
  formData.append('file', file);
  return requestClient.post<AttachmentRow>('/system/attachments', formData);
}
