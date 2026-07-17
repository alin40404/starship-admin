import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { ProductRow } from '#/features/shop/api';
import { SHOP_FULFILLMENT_TYPE, SHOP_PRODUCT_TYPE } from '#/features/shop/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { SHOP_PERMISSIONS } from '#/features/shop/constants/shop-permissions';
import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

export type ShopAccessChecker = {
  can: (code: string) => boolean;
};

function formatPrice(cents: number) {
  return `¥${(cents / 100).toFixed(2)}`;
}

function getStatusOptions() {
  return [
    { label: '上架', value: 1 },
    { label: '下架', value: 0 },
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

export function buildProductFormSchema(params: {
  brandOptions?: { label: string; value: number }[];
  categoryOptions?: { label: string; value: number }[];
}): VbenFormSchema[] {
  const { brandOptions = [], categoryOptions = [] } = params;

  return [
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: getProductTypeOptions(),
      },
      defaultValue: SHOP_PRODUCT_TYPE.PHYSICAL,
      fieldName: 'product_type',
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
      label: '交付方式',
      rules: 'required',
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
        options: brandOptions,
      },
      fieldName: 'brand_id',
      label: '品牌',
    },
    { component: 'Input', fieldName: 'name', label: '商品名称', rules: 'required' },
    { component: 'Input', fieldName: 'subtitle', label: '副标题' },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0, precision: 2, addonAfter: '元' },
      fieldName: 'price_yuan',
      label: '默认售价',
      rules: 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'stock',
      label: '默认库存',
      rules: 'required',
    },
    { component: 'Input', fieldName: 'cover', label: '封面图 URL' },
    {
      component: 'Textarea',
      fieldName: 'description',
      formItemClass: 'col-span-full',
      label: '描述',
    },
    {
      component: 'RadioGroup',
      componentProps: { options: getStatusOptions() },
      defaultValue: 1,
      fieldName: 'status',
      label: $t('org.shared.status'),
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<ProductRow>,
  access: ShopAccessChecker,
  onStatusBeforeChange: (newVal: number, row: ProductRow) => Promise<boolean>,
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
      formatter: ({ cellValue }) =>
        typeMap[String(cellValue)] ?? String(cellValue ?? TABLE_CELL_EMPTY),
      title: '类型',
      width: 100,
    },
    {
      field: 'fulfillment_type',
      formatter: ({ cellValue }) =>
        fulfillmentMap[String(cellValue)] ??
        String(cellValue ?? TABLE_CELL_EMPTY),
      title: '交付',
      width: 100,
    },
    {
      field: 'price',
      formatter: ({ cellValue }) => formatPrice(Number(cellValue ?? 0)),
      title: '售价',
      width: 110,
    },
    { field: 'stock', title: '库存', width: 90 },
    {
      field: 'status',
      title: $t('org.shared.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: ProductRow) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({
            disabled: !access.can(SHOP_PERMISSIONS.PRODUCTS_STATUS),
          }),
        },
        name: 'CellSwitch',
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
