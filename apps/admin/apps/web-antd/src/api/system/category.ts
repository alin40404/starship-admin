import { requestClient } from '#/api/request';

export type CategoryStatus = 'hidden' | 'normal';

export interface CategoryTypeOption {
  label: string;
  sort: number;
  value: string;
}

export interface CategoryTreeNode {
  id: number;
  parent_id: number;
  type: string;
  type_label?: string;
  name: string;
  nickname: string;
  flag: string[];
  flag_labels?: string[];
  image: string | null;
  keywords: string | null;
  description: string | null;
  diyname: string | null;
  sort: number;
  status: CategoryStatus;
  created_at?: string;
  updated_at?: string;
  children?: CategoryTreeNode[];
}

export interface CategoryTreeQuery {
  type?: string;
  search?: string;
}

export interface CategorySavePayload {
  parent_id?: number;
  type: string;
  name: string;
  nickname: string;
  flag?: string[];
  image?: string | null;
  keywords?: string | null;
  description?: string | null;
  diyname?: string | null;
  sort?: number;
  status?: CategoryStatus;
}

export function fetchCategoryTypes() {
  return requestClient.get<CategoryTypeOption[]>('/system/categories/types');
}

export function fetchCategoryTree(params?: CategoryTreeQuery) {
  return requestClient.get<CategoryTreeNode[]>('/system/categories/tree', {
    params,
  });
}

export function fetchCategory(id: number) {
  return requestClient.get<CategoryTreeNode>(`/system/categories/${id}`);
}

export function createCategory(data: CategorySavePayload) {
  return requestClient.post<CategoryTreeNode>('/system/categories', data);
}

export function updateCategory(
  id: number,
  data: Partial<CategorySavePayload>,
) {
  return requestClient.put<CategoryTreeNode>(`/system/categories/${id}`, data);
}

export function updateCategoryStatus(id: number, status: CategoryStatus) {
  return requestClient.put<CategoryTreeNode>(
    `/system/categories/${id}/status`,
    { status },
  );
}

export function updateCategorySort(id: number, sort: number) {
  return requestClient.put<CategoryTreeNode>(`/system/categories/${id}/sort`, {
    sort,
  });
}

export function deleteCategory(id: number) {
  return requestClient.delete(`/system/categories/${id}`);
}

export function batchDeleteCategories(ids: number[]) {
  return requestClient.post<{ deleted: number; skipped: number }>(
    '/system/categories/batch-destroy',
    { ids },
  );
}

export function batchUpdateCategoryStatus(
  ids: number[],
  status: CategoryStatus,
) {
  return requestClient.put<{ updated: number }>(
    '/system/categories/batch-status',
    { ids, status },
  );
}

export function mapCategoryForTreeSelect(
  nodes: CategoryTreeNode[],
): Array<{ label: string; value: number; children?: ReturnType<typeof mapCategoryForTreeSelect> }> {
  return nodes.map((node) => ({
    label: node.name,
    value: node.id,
    children: node.children?.length
      ? mapCategoryForTreeSelect(node.children)
      : undefined,
  }));
}
