import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';

import type { GameOrderRow } from '#/features/game-shop/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { formatDateTime } from '@vben/utils';

const statusMap: Record<number, string> = {
  0: '待支付',
  1: '已支付',
  2: '已完成',
  3: '失败',
  4: '已退款',
};

function formatPrice(cents: number) {
  return `¥${(cents / 100).toFixed(2)}`;
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '订单号/商品/账号' },
      fieldName: 'keyword',
      label: '关键词',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [
          { label: '待支付', value: 0 },
          { label: '已支付', value: 1 },
          { label: '已完成', value: 2 },
          { label: '失败', value: 3 },
          { label: '已退款', value: 4 },
        ],
      },
      fieldName: 'status',
      label: '状态',
    },
  ];
}

export function useColumns(): CrudGridColumn<GameOrderRow>[] {
  return [
    { field: 'id', title: 'ID', width: 72 },
    { field: 'order_no', minWidth: 160, title: '订单号' },
    { field: 'product_name', minWidth: 140, title: '商品' },
    {
      field: 'game_id',
      formatter: ({ row }) => row.game?.name ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '游戏',
    },
    {
      field: 'amount',
      formatter: ({ cellValue }) => formatPrice(Number(cellValue ?? 0)),
      title: '金额',
      width: 100,
    },
    { field: 'player_account', minWidth: 120, title: '玩家账号' },
    { field: 'server_name', width: 100, title: '区服' },
    {
      field: 'status',
      formatter: ({ cellValue }) =>
        statusMap[Number(cellValue)] ?? String(cellValue ?? TABLE_CELL_EMPTY),
      title: '状态',
      width: 90,
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: GameOrderRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '创建时间',
    },
  ];
}
