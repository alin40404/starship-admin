import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { GameProductRow } from '#/features/game-shop/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { GAME_SHOP_PERMISSIONS } from '#/features/game-shop/constants/game-shop-permissions';
import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

export type AccessChecker = { can: (code: string) => boolean };

const kindMap: Record<string, string> = {
  topup: '直充',
  cdkey: '卡密',
  item: '道具',
};

function formatPrice(cents: number) {
  return `¥${(cents / 100).toFixed(2)}`;
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '商品名称' },
      fieldName: 'keyword',
      label: '关键词',
    },
  ];
}

export function buildProductFormSchema(
  gameOptions: { label: string; value: number }[] = [],
): VbenFormSchema[] {
  return [
    {
      component: 'Select',
      componentProps: { class: 'w-full', options: gameOptions },
      fieldName: 'game_id',
      label: '所属游戏',
      rules: 'required',
    },
    { component: 'Input', fieldName: 'name', label: '商品名称', rules: 'required' },
    { component: 'Input', fieldName: 'sku_code', label: 'SKU 编码' },
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: [
          { label: '直充', value: 'topup' },
          { label: '卡密', value: 'cdkey' },
          { label: '道具', value: 'item' },
        ],
      },
      defaultValue: 'topup',
      fieldName: 'product_kind',
      label: '商品形态',
    },
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
    },
    { component: 'Input', fieldName: 'cover', label: '封面 URL' },
    {
      component: 'Textarea',
      fieldName: 'description',
      formItemClass: 'col-span-full',
      label: '描述',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        options: [
          { label: '上架', value: 1 },
          { label: '下架', value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      label: '状态',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<GameProductRow>,
  access: AccessChecker,
): CrudGridColumn<GameProductRow>[] {
  return [
    { type: 'checkbox', width: 40 },
    { field: 'id', title: 'ID', width: 72 },
    { field: 'name', minWidth: 140, title: '商品名称' },
    {
      field: 'game_id',
      formatter: ({ row }) => row.game?.name ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '游戏',
    },
    {
      field: 'product_kind',
      formatter: ({ cellValue }) =>
        kindMap[String(cellValue)] ?? String(cellValue ?? TABLE_CELL_EMPTY),
      title: '形态',
      width: 80,
    },
    {
      field: 'price',
      formatter: ({ cellValue }) => formatPrice(Number(cellValue ?? 0)),
      title: '售价',
      width: 100,
    },
    { field: 'stock', title: '库存', width: 80 },
    {
      field: 'status',
      formatter: ({ cellValue }) => (Number(cellValue) === 1 ? '上架' : '下架'),
      title: '状态',
      width: 80,
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: GameProductRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 160,
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
            show: () => access.can(GAME_SHOP_PERMISSIONS.PRODUCTS_UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            show: () => access.can(GAME_SHOP_PERMISSIONS.PRODUCTS_DELETE),
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
