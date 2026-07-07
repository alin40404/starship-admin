import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface DemoArticleRow {

  title: {{ $column->ts_type === 'number' ? 'number' : ($column->ts_type === 'Record<string, unknown>' ? 'Record<string, unknown>' : 'string') }};

  author: {{ $column->ts_type === 'number' ? 'number' : ($column->ts_type === 'Record<string, unknown>' ? 'Record<string, unknown>' : 'string') }};

  content: {{ $column->ts_type === 'number' ? 'number' : ($column->ts_type === 'Record<string, unknown>' ? 'Record<string, unknown>' : 'string') }};

  status: {{ $column->ts_type === 'number' ? 'number' : ($column->ts_type === 'Record<string, unknown>' ? 'Record<string, unknown>' : 'string') }};

  sort: {{ $column->ts_type === 'number' ? 'number' : ($column->ts_type === 'Record<string, unknown>' ? 'Record<string, unknown>' : 'string') }};

  remark: {{ $column->ts_type === 'number' ? 'number' : ($column->ts_type === 'Record<string, unknown>' ? 'Record<string, unknown>' : 'string') }};

  created_at?: string;
  updated_at?: string;
}

export interface DemoArticlePageQuery {
  page: number;
  per_page: number;

  keyword?: string;


  title?: {{ $column->ts_type === 'number' ? 'number' : 'string' }};

  status?: {{ $column->ts_type === 'number' ? 'number' : 'string' }};

  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface DemoArticleSavePayload {

  title{{ str_contains(implode('|', $column->validation_rules ?? []), 'required') ? '' : '?' }}: {{ $column->ts_type === 'number' ? 'number' : 'string' }};

  author{{ str_contains(implode('|', $column->validation_rules ?? []), 'required') ? '' : '?' }}: {{ $column->ts_type === 'number' ? 'number' : 'string' }};

  content{{ str_contains(implode('|', $column->validation_rules ?? []), 'required') ? '' : '?' }}: {{ $column->ts_type === 'number' ? 'number' : 'string' }};

  status{{ str_contains(implode('|', $column->validation_rules ?? []), 'required') ? '' : '?' }}: {{ $column->ts_type === 'number' ? 'number' : 'string' }};

  sort{{ str_contains(implode('|', $column->validation_rules ?? []), 'required') ? '' : '?' }}: {{ $column->ts_type === 'number' ? 'number' : 'string' }};

  remark{{ str_contains(implode('|', $column->validation_rules ?? []), 'required') ? '' : '?' }}: {{ $column->ts_type === 'number' ? 'number' : 'string' }};

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
