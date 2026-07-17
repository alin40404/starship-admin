import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { GameRow } from '#/features/game-shop/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { GAME_SHOP_PERMISSIONS } from '#/features/game-shop/constants/game-shop-permissions';
import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

export type AccessChecker = { can: (code: string) => boolean };

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '游戏名称/编码' },
      fieldName: 'keyword',
      label: '关键词',
    },
  ];
}

export function buildGameFormSchema(): VbenFormSchema[] {
  return [
    { component: 'Input', fieldName: 'name', label: '游戏名称', rules: 'required' },
    { component: 'Input', fieldName: 'code', label: '编码' },
    { component: 'Input', fieldName: 'platform', label: '平台' },
    { component: 'Input', fieldName: 'publisher', label: '厂商' },
    { component: 'Input', fieldName: 'cover', label: '封面 URL' },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: '排序',
    },
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
          { label: '启用', value: 1 },
          { label: '停用', value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      label: '状态',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<GameRow>,
  access: AccessChecker,
): CrudGridColumn<GameRow>[] {
  return [
    { field: 'id', title: 'ID', width: 72 },
    { field: 'name', minWidth: 140, title: '游戏名称' },
    { field: 'code', minWidth: 100, title: '编码' },
    { field: 'platform', width: 100, title: '平台' },
    { field: 'publisher', minWidth: 120, title: '厂商' },
    {
      field: 'status',
      formatter: ({ cellValue }) => (Number(cellValue) === 1 ? '启用' : '停用'),
      title: '状态',
      width: 80,
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: GameRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '创建时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '游戏',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(GAME_SHOP_PERMISSIONS.GAMES_UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            show: () => access.can(GAME_SHOP_PERMISSIONS.GAMES_DELETE),
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
