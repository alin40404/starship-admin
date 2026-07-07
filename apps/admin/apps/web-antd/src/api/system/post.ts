import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface PostRow {
  id: number;
  name: string;
  code: string;
  sort: number;
  status: number;
  remark: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface PostPageQuery {
  page: number;
  per_page: number;
  keyword?: string;
  name?: string;
  code?: string;
  status?: number;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface PostSavePayload {
  name: string;
  code: string;
  sort?: number;
  status?: number;
  remark?: string;
}

export function fetchPostPage(params: PostPageQuery) {
  return requestClient.get<LaravelPaginate<PostRow>>('/system/posts', {
    params,
  });
}

export function createPost(data: PostSavePayload) {
  return requestClient.post<PostRow>('/system/posts', data);
}

export function updatePost(id: number, data: Partial<PostSavePayload>) {
  return requestClient.put<PostRow>(`/system/posts/${id}`, data);
}

export function deletePost(id: number) {
  return requestClient.delete(`/system/posts/${id}`);
}

export function batchDeletePosts(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/system/posts/batch-destroy',
    { ids },
  );
}

export function batchUpdatePostStatus(ids: number[], status: 0 | 1) {
  return requestClient.put<{ updated: number }>('/system/posts/batch-status', {
    ids,
    status,
  });
}
