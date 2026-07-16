import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { OrderRow } from '#/features/shop/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { SHOP_PERMISSIONS } from '#/features/shop/constants/shop-permissions';
import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

export type ShopAccessChecker = {
  can: (code: string) => boolean;
};

const ORDER_STATUS: Record<number, string> = {
  0: '待支付',
  1: '已支付',
  2: '已取消',
  3: '已退款',
};

function formatPrice(cents: number) {
  return `¥${(cents / 100).toFixed(2)}`;
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '订单号/商品名' },
      fieldName: 'keyword',
      label: '关键词',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: Object.entries(ORDER_STATUS).map(([value, label]) => ({
          label,
          value: Number(value),
        })),
      },
      fieldName: 'status',
      label: '订单状态',
    },
  ];
}

export function buildOrderFormSchema(productOptions: { label: string; value: number }[]): VbenFormSchema[] {
  return [
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: productOptions,
        showSearch: true,
        optionFilterProp: 'label',
      },
      fieldName: 'product_id',
      label: '商品',
      rules: 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 1, max: 99 },
      defaultValue: 1,
      fieldName: 'quantity',
      label: '数量',
      rules: 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 1 },
      fieldName: 'member_id',
      label: '会员 ID',
    },
    {
      component: 'Textarea',
      fieldName: 'remark',
      formItemClass: 'col-span-full',
      label: $t('org.shared.remark'),
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<OrderRow>,
  access: ShopAccessChecker,
): CrudGridColumn<OrderRow>[] {
  return [
    { field: 'id', title: 'ID', width: 72 },
    { field: 'order_no', minWidth: 180, title: '订单号' },
    { field: 'product_name', minWidth: 140, title: '商品' },
    {
      field: 'amount',
      formatter: ({ cellValue }) => formatPrice(Number(cellValue ?? 0)),
      title: '金额',
      width: 110,
    },
    { field: 'quantity', title: '数量', width: 80 },
    {
      field: 'status',
      formatter: ({ cellValue }) => ORDER_STATUS[Number(cellValue)] ?? TABLE_CELL_EMPTY,
      title: '状态',
      width: 100,
    },
    {
      field: 'pay_channel',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      title: '支付渠道',
      width: 110,
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: OrderRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      title: '创建时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'order_no',
          nameTitle: '订单',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'prepay',
            show: (row: OrderRow) =>
              row.status === 0 && access.can(SHOP_PERMISSIONS.ORDERS_PREPAY),
            text: '支付',
            type: 'link',
          },
          {
            code: 'sync',
            show: (row: OrderRow) =>
              row.status === 0 && access.can(SHOP_PERMISSIONS.ORDERS_PREPAY),
            text: '同步',
            type: 'link',
          },
          {
            code: 'cancel',
            show: (row: OrderRow) =>
              row.status === 0 && access.can(SHOP_PERMISSIONS.ORDERS_CANCEL),
            text: '取消',
            type: 'link',
          },
          {
            code: 'delete',
            show: (row: OrderRow) =>
              row.status === 0 && access.can(SHOP_PERMISSIONS.ORDERS_DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      field: 'operation',
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 220,
    },
  ];
}
