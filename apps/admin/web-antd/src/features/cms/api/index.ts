import type { LaravelPaginate } from '#/features/system/api/types';
import type {
  CategorySavePayload as SystemCategorySavePayload,
  CategoryStatus,
  CategoryTreeNode,
} from '#/features/system/api/category';

import { requestClient } from '#/api/request';
import { mapCategoryForTreeSelect } from '#/features/system/api/category';

/** 对应系统字典 category_type.article，与后端 CmsCategoryType::ARTICLE 一致 */
export const CMS_CATEGORY_TYPE = 'article' as const;

export type CmsCategoryTreeNode = CategoryTreeNode;
export type CmsCategoryStatus = CategoryStatus;

export type CmsCategorySavePayload = Omit<SystemCategorySavePayload, 'type'> & {
  type?: string;
};

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

export type CategoryOption = { label: string; value: number };

function flattenCategoryOptions(
  nodes: ReturnType<typeof mapCategoryForTreeSelect>,
): CategoryOption[] {
  const result: CategoryOption[] = [];

  const walk = (
    list: ReturnType<typeof mapCategoryForTreeSelect>,
    prefix = '',
  ) => {
    for (const node of list) {
      const label = prefix ? `${prefix} / ${node.label}` : node.label;
      result.push({ label, value: node.value });
      if (node.children?.length) {
        walk(node.children, label);
      }
    }
  };

  walk(nodes);
  return result;
}

/** 栏目选择器（登录即可，固定 type=article） */
export async function fetchCmsCategoryOptions(): Promise<CategoryOption[]> {
  const tree = await requestClient.get<CmsCategoryTreeNode[]>(
    '/cms/category-options/tree',
  );
  return flattenCategoryOptions(mapCategoryForTreeSelect(tree));
}

export function fetchCmsCategoryTree(params?: { search?: string }) {
  return requestClient.get<CmsCategoryTreeNode[]>('/cms/categories/tree', {
    params,
  });
}

export function fetchCmsCategory(id: number) {
  return requestClient.get<CmsCategoryTreeNode>(`/cms/categories/${id}`);
}

export function createCmsCategory(data: CmsCategorySavePayload) {
  return requestClient.post<CmsCategoryTreeNode>('/cms/categories', data);
}

export function updateCmsCategory(
  id: number,
  data: Partial<CmsCategorySavePayload>,
) {
  return requestClient.post<CmsCategoryTreeNode>(`/cms/categories/${id}`, data);
}

export function updateCmsCategoryStatus(id: number, status: CmsCategoryStatus) {
  return requestClient.post<CmsCategoryTreeNode>(
    `/cms/categories/${id}/status`,
    { status },
  );
}

export function updateCmsCategorySort(id: number, sort: number) {
  return requestClient.post<CmsCategoryTreeNode>(`/cms/categories/${id}/sort`, {
    sort,
  });
}

export function deleteCmsCategory(id: number) {
  return requestClient.post(`/cms/categories/${id}/delete`);
}

export function batchDeleteCmsCategories(ids: number[]) {
  return requestClient.post<{ deleted: number; skipped: number }>(
    '/cms/categories/batch-destroy',
    { ids },
  );
}

export function batchUpdateCmsCategoryStatus(
  ids: number[],
  status: CmsCategoryStatus,
) {
  return requestClient.post<{ updated: number }>(
    '/cms/categories/batch-status',
    { ids, status },
  );
}

export async function loadCmsCategoryTreeForParent(
  excludeId?: number,
): Promise<ReturnType<typeof mapCategoryForTreeSelect>> {
  const tree = await requestClient.get<CmsCategoryTreeNode[]>(
    '/cms/category-options/tree',
  );

  function filter(nodes: CmsCategoryTreeNode[]): CmsCategoryTreeNode[] {
    return nodes
      .filter((node) => node.id !== excludeId)
      .map((node) => ({
        ...node,
        children: node.children?.length ? filter(node.children) : [],
      }));
  }

  const filtered = excludeId ? filter(tree) : tree;

  return [
    { label: '无', value: 0 },
    ...mapCategoryForTreeSelect(filtered),
  ];
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
  return requestClient.post<ArticleRow>(`/cms/articles/${id}`, data);
}

export function updateArticleStatus(id: number, status: number) {
  return requestClient.post<ArticleRow>(`/cms/articles/${id}/status`, {
    status,
  });
}

export function deleteArticle(id: number) {
  return requestClient.post(`/cms/articles/${id}/delete`);
}
