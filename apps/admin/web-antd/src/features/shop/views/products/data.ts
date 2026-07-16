import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { ProductRow } from '#/features/shop/api';
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

export function useGridFormSchema(): VbenFormSchema[] {
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
        options: getStatusOptions(),
      },
      fieldName: 'status',
      label: $t('org.shared.status'),
    },
  ];
}

export function buildProductFormSchema(): VbenFormSchema[] {
  return [
    { component: 'Input', fieldName: 'name', label: '商品名称', rules: 'required' },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0, precision: 2, addonAfter: '元' },
      fieldName: 'price_yuan',
      label: '售价',
      rules: 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'stock',
      label: '库存',
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
  return [
    { field: 'id', title: 'ID', width: 72 },
    { field: 'name', minWidth: 160, title: '商品名称' },
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
