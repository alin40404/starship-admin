import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { ProductRow, ProductSkuRow } from '#/features/shop/api';
import {
  SHOP_FULFILLMENT_TYPE,
  SHOP_PRODUCT_STATUS,
  SHOP_PRODUCT_TYPE,
} from '#/features/shop/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { SHOP_PERMISSIONS } from '#/features/shop/constants/shop-permissions';
import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

export type ShopAccessChecker = {
  can: (code: string) => boolean;
};

/** 多规格维度定义，存于 product.extra.variables */
export type ProductVariable = {
  name: string;
  values: string[];
};

/** 表单内 SKU 行（价格用元；图片/原价存后端 extra） */
export type SkuFormRow = {
  id?: number;
  key: string;
  specs: Record<string, string> | null;
  sku_code: string;
  /** 规格图 */
  image: string;
  price_yuan: number;
  /** 原价（元），前台划线展示用 */
  compare_at_price_yuan: number;
  stock: number;
  status: number;
};

export function createEmptySkuRow(
  partial?: Partial<SkuFormRow>,
): SkuFormRow {
  return {
    key: '',
    specs: null,
    sku_code: '',
    image: '',
    price_yuan: 0,
    compare_at_price_yuan: 0,
    stock: 0,
    status: 1,
    ...partial,
  };
}

function readSkuExtraNumber(
  extra: Record<string, unknown> | null | undefined,
  key: string,
): number {
  const raw = extra?.[key];
  const n = Number(raw ?? 0);
  return Number.isFinite(n) ? n : 0;
}

function readSkuExtraString(
  extra: Record<string, unknown> | null | undefined,
  key: string,
): string {
  const raw = extra?.[key];
  return raw == null ? '' : String(raw).trim();
}

/** 从表单行生成后端 skus[].extra */
export function buildSkuExtra(
  row: SkuFormRow,
): Record<string, unknown> | null {
  const image = row.image.trim();
  const compareAt = Math.round(Number(row.compare_at_price_yuan ?? 0) * 100);
  const extra: Record<string, unknown> = {};
  if (image) extra.image = image;
  if (compareAt > 0) extra.compare_at_price = compareAt;
  return Object.keys(extra).length > 0 ? extra : null;
}

function formatPrice(cents: number) {
  return `¥${(cents / 100).toFixed(2)}`;
}

export function getStatusOptions() {
  return [
    { label: '上架', value: SHOP_PRODUCT_STATUS.ON },
    { label: '下架', value: SHOP_PRODUCT_STATUS.OFF },
    { label: '草稿', value: SHOP_PRODUCT_STATUS.DRAFT },
  ];
}

export function getStatusTagOptions() {
  return [
    { color: 'success', label: '上架', value: SHOP_PRODUCT_STATUS.ON },
    { color: 'default', label: '下架', value: SHOP_PRODUCT_STATUS.OFF },
    { color: 'processing', label: '草稿', value: SHOP_PRODUCT_STATUS.DRAFT },
  ];
}

export function getProductTypeOptions() {
  return [
    { label: '实物商品', value: SHOP_PRODUCT_TYPE.PHYSICAL },
    { label: '虚拟商品', value: SHOP_PRODUCT_TYPE.VIRTUAL },
    { label: '数字资源', value: SHOP_PRODUCT_TYPE.DIGITAL },
  ];
}

export function getFulfillmentTypeOptions() {
  return [
    { label: '物流配送', value: SHOP_FULFILLMENT_TYPE.SHIPPING },
    { label: '自动发放', value: SHOP_FULFILLMENT_TYPE.DIGITAL },
    { label: '卡券发放', value: SHOP_FULFILLMENT_TYPE.CARD },
    { label: '兑换码', value: SHOP_FULFILLMENT_TYPE.CODE },
    { label: '人工处理', value: SHOP_FULFILLMENT_TYPE.MANUAL },
  ];
}

/** 与后端 ShopFulfillmentType::allowedForProductType 保持一致 */
export function allowedFulfillmentsForProductType(
  productType: string,
): string[] {
  switch (productType) {
    case SHOP_PRODUCT_TYPE.VIRTUAL:
      return [
        SHOP_FULFILLMENT_TYPE.DIGITAL,
        SHOP_FULFILLMENT_TYPE.CARD,
        SHOP_FULFILLMENT_TYPE.CODE,
        SHOP_FULFILLMENT_TYPE.MANUAL,
      ];
    case SHOP_PRODUCT_TYPE.DIGITAL:
      return [
        SHOP_FULFILLMENT_TYPE.DIGITAL,
        SHOP_FULFILLMENT_TYPE.CODE,
        SHOP_FULFILLMENT_TYPE.MANUAL,
      ];
    default:
      return [SHOP_FULFILLMENT_TYPE.SHIPPING, SHOP_FULFILLMENT_TYPE.MANUAL];
  }
}

export function defaultFulfillmentForProductType(productType: string): string {
  return allowedFulfillmentsForProductType(productType)[0]!;
}

export function specsKey(specs: Record<string, string> | null | undefined): string {
  if (!specs || Object.keys(specs).length === 0) return '';
  return Object.keys(specs)
    .sort()
    .map((k) => `${k}=${specs[k]}`)
    .join('|');
}

export function buildCartesianSpecs(
  variables: ProductVariable[],
): Record<string, string>[] {
  const dims = variables
    .map((item) => ({
      name: item.name.trim(),
      values: item.values.map((v) => v.trim()).filter(Boolean),
    }))
    .filter((item) => item.name && item.values.length > 0);

  if (dims.length === 0) return [];

  return dims.reduce<Record<string, string>[]>((acc, dim) => {
    if (acc.length === 0) {
      return dim.values.map((value) => ({ [dim.name]: value }));
    }
    const next: Record<string, string>[] = [];
    for (const row of acc) {
      for (const value of dim.values) {
        next.push({ ...row, [dim.name]: value });
      }
    }
    return next;
  }, []);
}

export function mergeSkuRows(
  specsList: Record<string, string>[],
  previous: SkuFormRow[],
  defaults?: Partial<
    Pick<SkuFormRow, 'price_yuan' | 'compare_at_price_yuan' | 'stock' | 'image'>
  >,
): SkuFormRow[] {
  const prevMap = new Map(previous.map((row) => [row.key, row]));
  return specsList.map((specs) => {
    const key = specsKey(specs);
    const existed = prevMap.get(key);
    if (existed) {
      return { ...existed, key, specs };
    }
    return createEmptySkuRow({
      key,
      specs,
      price_yuan: defaults?.price_yuan ?? 0,
      compare_at_price_yuan: defaults?.compare_at_price_yuan ?? 0,
      stock: defaults?.stock ?? 0,
      image: defaults?.image ?? '',
    });
  });
}

export function skuRowsFromProduct(
  skus: ProductSkuRow[] | undefined,
): SkuFormRow[] {
  if (!skus?.length) {
    return [createEmptySkuRow()];
  }
  return skus.map((sku) => {
    const specs = sku.specs ?? null;
    const extra = sku.extra ?? null;
    // 兼容短暂存在过的 original_price
    const compareAtCents =
      readSkuExtraNumber(extra, 'compare_at_price') ||
      readSkuExtraNumber(extra, 'original_price');
    return createEmptySkuRow({
      id: sku.id,
      key: specsKey(specs),
      specs,
      sku_code: sku.sku_code ?? '',
      image: readSkuExtraString(extra, 'image'),
      price_yuan: Number(sku.price ?? 0) / 100,
      compare_at_price_yuan: compareAtCents / 100,
      stock: Number(sku.stock ?? 0),
      status: sku.status ?? 1,
    });
  });
}

export function parseProductVariables(
  extra: Record<string, unknown> | null | undefined,
): ProductVariable[] {
  const raw = extra?.variables;
  if (!Array.isArray(raw)) return [];
  return raw
    .map((item) => {
      if (!item || typeof item !== 'object') return null;
      const row = item as { name?: unknown; values?: unknown };
      const name = String(row.name ?? '').trim();
      const values = Array.isArray(row.values)
        ? row.values.map((v) => String(v ?? '').trim()).filter(Boolean)
        : [];
      if (!name) return null;
      return { name, values };
    })
    .filter((item): item is ProductVariable => !!item);
}

/** 从 SKU.specs 反推规格维度（兼容未存 extra.variables 的旧数据） */
export function inferVariablesFromSkus(
  skus: ProductSkuRow[] | undefined,
): ProductVariable[] {
  if (!skus?.length) return [];
  const map = new Map<string, Set<string>>();
  for (const sku of skus) {
    const specs = sku.specs;
    if (!specs) continue;
    for (const [name, value] of Object.entries(specs)) {
      const key = String(name).trim();
      const val = String(value ?? '').trim();
      if (!key || !val) continue;
      if (!map.has(key)) map.set(key, new Set());
      map.get(key)!.add(val);
    }
  }
  return [...map.entries()].map(([name, values]) => ({
    name,
    values: [...values],
  }));
}

export function useGridFormSchema(
  categoryOptions: { label: string; value: number }[] = [],
): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '商品名称' },
      fieldName: 'keyword',
      label: '关键词',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: getProductTypeOptions(),
      },
      fieldName: 'product_type',
      label: '商品类型',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: categoryOptions,
      },
      fieldName: 'category_id',
      label: '分类',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: getStatusOptions(),
      },
      fieldName: 'status',
      label: $t('org.shared.status'),
    },
  ];
}

export function buildTitleFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '例如：夏季纯棉 T 恤',
      },
      fieldName: 'name',
      formItemClass: 'col-span-full',
      label: '商品名称',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '简短副标题，展示在名称下方',
      },
      fieldName: 'subtitle',
      formItemClass: 'col-span-full',
      label: '副标题',
    },
  ];
}

export function buildStatusFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full flex-wrap',
        optionType: 'button',
        options: getStatusOptions(),
      },
      defaultValue: SHOP_PRODUCT_STATUS.DRAFT,
      fieldName: 'status',
      formItemClass: 'col-span-full',
      hideLabel: true,
      label: $t('org.shared.status'),
    },
  ];
}

export function buildOrganizationFormSchema(params: {
  brandOptions?: { label: string; value: number }[];
  categoryOptions?: { label: string; value: number }[];
}): VbenFormSchema[] {
  const { brandOptions = [], categoryOptions = [] } = params;

  return [
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: categoryOptions,
        placeholder: '选择分类',
      },
      fieldName: 'category_id',
      formItemClass: 'col-span-full',
      label: '分类',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: brandOptions,
        placeholder: '选择品牌',
      },
      fieldName: 'brand_id',
      formItemClass: 'col-span-full',
      label: '品牌',
    },
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: getProductTypeOptions(),
      },
      defaultValue: SHOP_PRODUCT_TYPE.PHYSICAL,
      fieldName: 'product_type',
      formItemClass: 'col-span-full',
      label: '商品类型',
      rules: 'required',
    },
    {
      component: 'Select',
      componentProps: { class: 'w-full' },
      defaultValue: SHOP_FULFILLMENT_TYPE.SHIPPING,
      dependencies: {
        componentProps: (values) => {
          const allowed = allowedFulfillmentsForProductType(
            String(values.product_type ?? SHOP_PRODUCT_TYPE.PHYSICAL),
          );
          return {
            class: 'w-full',
            options: getFulfillmentTypeOptions().filter((item) =>
              allowed.includes(item.value),
            ),
          };
        },
        trigger(values, form) {
          const allowed = allowedFulfillmentsForProductType(
            String(values.product_type ?? SHOP_PRODUCT_TYPE.PHYSICAL),
          );
          if (!allowed.includes(String(values.fulfillment_type))) {
            form.setFieldValue('fulfillment_type', allowed[0]);
          }
        },
        triggerFields: ['product_type'],
      },
      fieldName: 'fulfillment_type',
      formItemClass: 'col-span-full',
      label: '交付方式',
      rules: 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      formItemClass: 'col-span-full',
      label: '排序',
    },
  ];
}

export function buildDetailFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'RichEditor',
      fieldName: 'description',
      formItemClass: 'col-span-full items-start',
      hideLabel: true,
      label: '商品详情',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<ProductRow>,
  access: ShopAccessChecker,
): CrudGridColumn<ProductRow>[] {
  const typeMap = Object.fromEntries(
    getProductTypeOptions().map((item) => [item.value, item.label]),
  );
  const fulfillmentMap = Object.fromEntries(
    getFulfillmentTypeOptions().map((item) => [item.value, item.label]),
  );

  return [
    { field: 'id', title: 'ID', width: 72 },
    { field: 'name', minWidth: 160, title: '商品名称' },
    {
      field: 'product_type',
      formatter: ({ cellValue }: { cellValue: unknown }) =>
        typeMap[String(cellValue)] ?? String(cellValue ?? TABLE_CELL_EMPTY),
      title: '类型',
      width: 100,
    },
    {
      field: 'fulfillment_type',
      formatter: ({ cellValue }: { cellValue: unknown }) =>
        fulfillmentMap[String(cellValue)] ??
        String(cellValue ?? TABLE_CELL_EMPTY),
      title: '交付',
      width: 100,
    },
    {
      field: 'price',
      formatter: ({ cellValue }: { cellValue: unknown }) =>
        formatPrice(Number(cellValue ?? 0)),
      title: '售价',
      width: 110,
    },
    { field: 'stock', title: '库存', width: 90 },
    {
      field: 'status',
      title: $t('org.shared.status'),
      width: 100,
      cellRender: {
        name: 'CellTag',
        options: getStatusTagOptions(),
      },
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: ProductRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      title: '创建时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '商品',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(SHOP_PERMISSIONS.PRODUCTS_UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            show: () => access.can(SHOP_PERMISSIONS.PRODUCTS_DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      field: 'operation',
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 140,
    },
  ];
}
