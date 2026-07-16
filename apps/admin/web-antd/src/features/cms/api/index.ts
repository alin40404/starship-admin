import type { LaravelPaginate } from '#/features/system/api/types';

import { requestClient } from '#/api/request';

export interface CategoryRow {
  id: number;
  name: string;
  slug: string;
  parent_id: number;
  sort: number;
  status: number;
  created_at?: string;
  updated_at?: string;
}

export interface ArticleRow {
  id: number;
  category_id: number | null;
  title: string;
  slug: string;
  summary: string | null;
  content: string | null;
  cover: string | null;
  status: number;
  published_at: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface CategorySavePayload {
  name: string;
  slug: string;
  parent_id?: number;
  sort?: number;
  status?: number;
}

export interface ArticleSavePayload {
  category_id?: number | null;
  title: string;
  slug: string;
  summary?: string | null;
  content?: string | null;
  cover?: string | null;
  status?: number;
  published_at?: string | null;
}

export function fetchCategoryPage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<CategoryRow>>('/cms/categories', {
    params,
  });
}

export function fetchCategoryOptions() {
  return requestClient.get<CategoryRow[]>('/cms/categories/options');
}

export function fetchCategory(id: number) {
  return requestClient.get<CategoryRow>(`/cms/categories/${id}`);
}

export function createCategory(data: CategorySavePayload) {
  return requestClient.post<CategoryRow>('/cms/categories', data);
}

export function updateCategory(id: number, data: Partial<CategorySavePayload>) {
  return requestClient.put<CategoryRow>(`/cms/categories/${id}`, data);
}

export function updateCategoryStatus(id: number, status: number) {
  return requestClient.put<CategoryRow>(`/cms/categories/${id}/status`, {
    status,
  });
}

export function deleteCategory(id: number) {
  return requestClient.delete(`/cms/categories/${id}`);
}

export function fetchArticlePage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<ArticleRow>>('/cms/articles', {
    params,
  });
}

export function fetchArticle(id: number) {
  return requestClient.get<ArticleRow>(`/cms/articles/${id}`);
}

export function createArticle(data: ArticleSavePayload) {
  return requestClient.post<ArticleRow>('/cms/articles', data);
}

export function updateArticle(id: number, data: Partial<ArticleSavePayload>) {
  return requestClient.put<ArticleRow>(`/cms/articles/${id}`, data);
}

export function updateArticleStatus(id: number, status: number) {
  return requestClient.put<ArticleRow>(`/cms/articles/${id}/status`, {
    status,
  });
}

export function deleteArticle(id: number) {
  return requestClient.delete(`/cms/articles/${id}`);
}
