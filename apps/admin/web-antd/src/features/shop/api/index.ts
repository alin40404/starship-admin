import type { LaravelPaginate } from '#/features/system/api/types';
import type {
  CategorySavePayload as SystemCategorySavePayload,
  CategoryStatus,
  CategoryTreeNode,
} from '#/features/system/api/category';

import { requestClient } from '#/api/request';
import { mapCategoryForTreeSelect } from '#/features/system/api/category';

export const SHOP_CATEGORY_TYPE = 'product' as const;
export const SHOP_PRODUCT_TYPE = {
  PHYSICAL: 'physical',
  VIRTUAL: 'virtual',
  DIGITAL: 'digital',
} as const;

/** 0下架 / 1上架 / 2草稿 */
export const SHOP_PRODUCT_STATUS = {
  OFF: 0,
  ON: 1,
  DRAFT: 2,
} as const;

export const SHOP_FULFILLMENT_TYPE = {
  SHIPPING: 'shipping',
  DIGITAL: 'digital',
  CARD: 'card',
  CODE: 'code',
  MANUAL: 'manual',
} as const;

export type ShopCategoryTreeNode = CategoryTreeNode;
export type ShopCategoryStatus = CategoryStatus;
export type ShopCategorySavePayload = Omit<SystemCategorySavePayload, 'type'> & {
  type?: string;
};

export interface ProductSkuRow {
  id?: number;
  sku_code?: string;
  specs?: Record<string, string> | null;
  price: number;
  /** 原价/划线价（分），空表示无；有值须大于 price */
  compare_at_price?: number | null;
  stock: number;
  status?: number;
  extra?: Record<string, unknown> | null;
}

export interface ProductMediaItem {
  type: 'image' | 'video';
  url: string;
}

export interface ProductRow {
  id: number;
  category_id: number | null;
  brand_id: number | null;
  product_type: string;
  fulfillment_type: string;
  name: string;
  subtitle: string | null;
  price: number;
  stock: number;
  /** 由后端访问器从 media 派生，只读 */
  cover?: string | null;
  /** 由后端访问器从 media 派生，只读 */
  images?: string[] | null;
  media: ProductMediaItem[] | null;
  description: string | null;
  status: number;
  sort: number;
  extra: Record<string, unknown> | null;
  skus?: ProductSkuRow[];
  created_at?: string;
  updated_at?: string;
}

export interface BrandRow {
  id: number;
  name: string;
  logo: string | null;
  sort: number;
  status: number;
  created_at?: string;
  updated_at?: string;
}

export interface OrderRow {
  id: number;
  order_no: string;
  member_id: number | null;
  product_id: number;
  sku_id: number | null;
  product_name: string;
  amount: number;
  quantity: number;
  status: number;
  pay_channel: string | null;
  prepay_id: string | null;
  trade_no: string | null;
  paid_at: string | null;
  refunded_at: string | null;
  remark: string | null;
  fulfillment_type: string | null;
  fulfillment_status: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface ProductSavePayload {
  category_id?: number | null;
  brand_id?: number | null;
  product_type: string;
  fulfillment_type?: string;
  name: string;
  subtitle?: string | null;
  price?: number;
  stock?: number;
  media?: ProductMediaItem[] | null;
  description?: string | null;
  status?: number;
  sort?: number;
  extra?: Record<string, unknown> | null;
  skus?: ProductSkuRow[];
}

export interface BrandSavePayload {
  name: string;
  logo?: string | null;
  sort?: number;
  status?: number;
}

export interface OrderCreatePayload {
  product_id: number;
  sku_id?: number | null;
  quantity?: number;
  member_id?: number | null;
  remark?: string | null;
  fulfillment_payload?: Record<string, unknown>;
}

export interface PrepayResult {
  order: OrderRow;
  payment: Record<string, unknown>;
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
      if (node.children?.length) walk(node.children, label);
    }
  };
  walk(nodes);
  return result;
}

export async function fetchShopCategoryOptions(): Promise<CategoryOption[]> {
  const tree = await requestClient.get<ShopCategoryTreeNode[]>(
    '/shop/category-options/tree',
  );
  return flattenCategoryOptions(mapCategoryForTreeSelect(tree));
}

export function fetchShopCategoryTree(params?: { search?: string }) {
  return requestClient.get<ShopCategoryTreeNode[]>('/shop/categories/tree', {
    params,
  });
}

export function fetchShopCategory(id: number) {
  return requestClient.get<ShopCategoryTreeNode>(`/shop/categories/${id}`);
}

export function createShopCategory(data: ShopCategorySavePayload) {
  return requestClient.post<ShopCategoryTreeNode>('/shop/categories', data);
}

export function updateShopCategory(
  id: number,
  data: Partial<ShopCategorySavePayload>,
) {
  return requestClient.put<ShopCategoryTreeNode>(`/shop/categories/${id}`, data);
}

export function updateShopCategoryStatus(
  id: number,
  status: ShopCategoryStatus,
) {
  return requestClient.put<ShopCategoryTreeNode>(
    `/shop/categories/${id}/status`,
    { status },
  );
}

export function updateShopCategorySort(id: number, sort: number) {
  return requestClient.put<ShopCategoryTreeNode>(`/shop/categories/${id}/sort`, {
    sort,
  });
}

export function deleteShopCategory(id: number) {
  return requestClient.delete(`/shop/categories/${id}`);
}

export function batchDeleteShopCategories(ids: number[]) {
  return requestClient.post<{ deleted: number; skipped: number }>(
    '/shop/categories/batch-destroy',
    { ids },
  );
}

export function batchUpdateShopCategoryStatus(
  ids: number[],
  status: ShopCategoryStatus,
) {
  return requestClient.put<{ updated: number }>(
    '/shop/categories/batch-status',
    { ids, status },
  );
}

export async function loadShopCategoryTreeForParent(excludeId?: number) {
  const tree = await requestClient.get<ShopCategoryTreeNode[]>(
    '/shop/category-options/tree',
  );
  function filter(nodes: ShopCategoryTreeNode[]): ShopCategoryTreeNode[] {
    return nodes
      .filter((node) => node.id !== excludeId)
      .map((node) => ({
        ...node,
        children: node.children?.length ? filter(node.children) : [],
      }));
  }
  const filtered = excludeId ? filter(tree) : tree;
  return [{ label: '无', value: 0 }, ...mapCategoryForTreeSelect(filtered)];
}

export function fetchBrandPage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<BrandRow>>('/shop/brands', {
    params,
  });
}

export function fetchBrandOptions() {
  return requestClient.get<Pick<BrandRow, 'id' | 'name'>[]>('/shop/brand-options');
}

export function fetchBrand(id: number) {
  return requestClient.get<BrandRow>(`/shop/brands/${id}`);
}

export function createBrand(data: BrandSavePayload) {
  return requestClient.post<BrandRow>('/shop/brands', data);
}

export function updateBrand(id: number, data: Partial<BrandSavePayload>) {
  return requestClient.put<BrandRow>(`/shop/brands/${id}`, data);
}

export function deleteBrand(id: number) {
  return requestClient.delete(`/shop/brands/${id}`);
}

export function fetchProductPage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<ProductRow>>('/shop/products', {
    params,
  });
}

export function fetchProduct(id: number) {
  return requestClient.get<ProductRow>(`/shop/products/${id}`);
}

export function createProduct(data: ProductSavePayload) {
  return requestClient.post<ProductRow>('/shop/products', data);
}

export function updateProduct(id: number, data: Partial<ProductSavePayload>) {
  return requestClient.put<ProductRow>(`/shop/products/${id}`, data);
}

export function updateProductStatus(id: number, status: number) {
  return requestClient.put<ProductRow>(`/shop/products/${id}/status`, {
    status,
  });
}

export function deleteProduct(id: number) {
  return requestClient.delete(`/shop/products/${id}`);
}

export function fetchOrderPage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<OrderRow>>('/shop/orders', {
    params,
  });
}

export function createOrder(data: OrderCreatePayload) {
  return requestClient.post<OrderRow>('/shop/orders', data);
}

export function prepayOrder(id: number) {
  return requestClient.post<PrepayResult>(`/shop/orders/${id}/prepay`);
}

export function syncOrderStatus(id: number) {
  return requestClient.post<OrderRow>(`/shop/orders/${id}/sync-status`);
}

export function refundOrder(id: number, reason?: string) {
  return requestClient.post<{ order: OrderRow; refund: Record<string, unknown> }>(
    `/shop/orders/${id}/refund`,
    { reason },
  );
}

export function cancelOrder(id: number) {
  return requestClient.post<OrderRow>(`/shop/orders/${id}/cancel`);
}

export function deleteOrder(id: number) {
  return requestClient.delete(`/shop/orders/${id}`);
}
