import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface DemoArticleRow {
  title: string;
  author: string;
  content: string;
  status: number;
  sort: number;
  remark: string;
  created_at?: string;
  updated_at?: string;
}

export interface DemoArticlePageQuery {
  page: number;
  per_page: number;
  keyword?: string;

  title?: string;
  status?: number;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface DemoArticleSavePayload {
  title: string;
  author?: string;
  content?: string;
  status: number;
  sort: number;
  remark?: string;
}

export function fetchDemoArticlePage(params: DemoArticlePageQuery) {
  return requestClient.get<LaravelPaginate<DemoArticleRow>>('/system/demo_articles', {
    params,
  });
}

export function createDemoArticle(data: DemoArticleSavePayload) {
  return requestClient.post<DemoArticleRow>('/system/demo_articles', data);
}

export function updateDemoArticle(id: number, data: Partial<DemoArticleSavePayload>) {
  return requestClient.put<DemoArticleRow>(`/system/demo_articles/${id}`, data);
}

export function deleteDemoArticle(id: number) {
  return requestClient.delete(`/system/demo_articles/${id}`);
}
export function batchDeleteDemoArticles(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/system/demo_articles/batch-destroy',
    { ids },
  );
}

export function batchUpdateDemoArticleStatus(ids: number[], status: 0 | 1) {
  return requestClient.put<{ updated: number }>('/system/demo_articles/batch-status', {
    ids,
    status,
  });
}
